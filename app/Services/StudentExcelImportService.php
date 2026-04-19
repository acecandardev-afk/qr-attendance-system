<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentExcelImportService
{
    public const MAX_DATA_ROWS = 500;

    /**
     * @return array{created: int, skipped: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return ['created' => 0, 'skipped' => 0, 'errors' => ['Could not read the uploaded file.']];
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headerRow = 1;
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestColumn($headerRow);
        $colCount = Coordinate::columnIndexFromString($highestColumn);

        $columnMap = [];
        for ($c = 1; $c <= $colCount; $c++) {
            $letter = Coordinate::stringFromColumnIndex($c);
            $raw = $sheet->getCell($letter.$headerRow)->getValue();
            $key = $this->normalizeHeaderKey((string) $raw);
            if ($key !== '' && ! isset($columnMap[$key])) {
                $columnMap[$key] = $c;
            }
        }

        $hasUserId = isset($columnMap['user_id']);
        $hasSplitName = isset($columnMap['first_name']) && isset($columnMap['last_name']);
        $hasFullName = isset($columnMap['full_name']);
        if (! $hasUserId || (! $hasSplitName && ! $hasFullName)) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => [
                    'The first row must be headers. Include a student ID column (e.g. Student ID, ID No, LRN) and names: either First name + Last name columns, or one column named Name / Full name. Other columns (email, address, course, etc.) are optional; anything else is ignored.',
                ],
            ];
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $seenUserIds = [];
        $dataRowCount = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $userId = $this->cellString($sheet, $row, $columnMap['user_id']);
            $firstName = isset($columnMap['first_name']) ? $this->cellString($sheet, $row, $columnMap['first_name']) : '';
            $lastName = isset($columnMap['last_name']) ? $this->cellString($sheet, $row, $columnMap['last_name']) : '';
            if (($firstName === '' || $lastName === '') && isset($columnMap['full_name'])) {
                $full = $this->cellString($sheet, $row, $columnMap['full_name']);
                if ($full !== '') {
                    [$f, $l] = $this->splitFullName($full);
                    if ($firstName === '') {
                        $firstName = $f;
                    }
                    if ($lastName === '') {
                        $lastName = $l;
                    }
                }
            }

            if ($userId === '' && $lastName === '' && $firstName === '') {
                continue;
            }

            $dataRowCount++;
            if ($dataRowCount > self::MAX_DATA_ROWS) {
                $errors[] = 'Import stopped: more than '.self::MAX_DATA_ROWS.' data rows. Split into smaller files.';

                break;
            }

            $middleName = isset($columnMap['middle_name']) ? $this->cellString($sheet, $row, $columnMap['middle_name']) : '';
            $email = isset($columnMap['email']) ? $this->cellString($sheet, $row, $columnMap['email']) : '';
            $deptName = isset($columnMap['department']) ? $this->cellString($sheet, $row, $columnMap['department']) : '';
            $yearLevel = isset($columnMap['year_level']) ? $this->cellString($sheet, $row, $columnMap['year_level']) : '';
            $statusRaw = isset($columnMap['status']) ? $this->cellString($sheet, $row, $columnMap['status']) : '';
            $address = isset($columnMap['address']) ? $this->cellString($sheet, $row, $columnMap['address']) : '';
            $ageVal = isset($columnMap['age']) ? $sheet->getCell(Coordinate::stringFromColumnIndex($columnMap['age']).$row)->getValue() : null;
            $birthdayCell = isset($columnMap['birthday']) ? $sheet->getCell(Coordinate::stringFromColumnIndex($columnMap['birthday']).$row) : null;

            $status = strtolower($statusRaw) === 'inactive' ? 'inactive' : 'active';

            $departmentId = null;
            if ($deptName !== '') {
                $dept = Department::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($deptName)])
                    ->first();
                if (! $dept) {
                    $errors[] = "Row {$row}: No course matches “{$deptName}”. Leave blank or use the exact name from your course list.";

                    continue;
                }
                $departmentId = $dept->id;
            }

            $emailNorm = $email !== '' ? Str::lower($email) : Str::lower($userId).'@school.edu';

            $birthday = null;
            if ($birthdayCell !== null) {
                $birthday = $this->parseBirthday($birthdayCell->getValue());
            }

            $age = null;
            if ($ageVal !== null && $ageVal !== '') {
                $age = is_numeric($ageVal) ? (int) $ageVal : null;
            }

            $uidKey = Str::lower($userId);
            if (isset($seenUserIds[$uidKey])) {
                $errors[] = "Row {$row}: Duplicate Student ID in this file (same as row {$seenUserIds[$uidKey]}).";

                continue;
            }
            $seenUserIds[$uidKey] = $row;

            if (User::withTrashed()->where('user_id', $userId)->whereNotNull('deleted_at')->exists()) {
                $errors[] = "Row {$row}: Student ID “{$userId}” belongs to an archived account. Restore it or use a different ID.";

                continue;
            }

            if (User::query()->where('user_id', $userId)->whereNull('deleted_at')->exists()) {
                $skipped++;
                $errors[] = "Row {$row}: Student ID “{$userId}” is already in the system — skipped.";

                continue;
            }

            if (User::query()->where('email', $emailNorm)->whereNull('deleted_at')->exists()) {
                $errors[] = "Row {$row}: This email is already used by another account.";

                continue;
            }

            $validator = Validator::make([
                'user_id' => $userId,
                'email' => $emailNorm,
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'department_id' => $departmentId,
                'year_level' => $yearLevel !== '' ? $yearLevel : null,
                'address' => $address !== '' ? $address : null,
                'age' => $age,
                'birthday' => $birthday,
                'status' => $status,
            ], [
                'user_id' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'department_id' => 'nullable|exists:departments,id',
                'year_level' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:255',
                'age' => 'nullable|integer|min:1|max:150',
                'birthday' => 'nullable|date',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                $errors[] = 'Row '.$row.': '.$validator->errors()->first();

                continue;
            }

            User::create([
                'user_id' => $userId,
                'email' => $emailNorm,
                'password' => 'password',
                'role' => 'student',
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'department_id' => $departmentId,
                'year_level' => $yearLevel !== '' ? $yearLevel : null,
                'address' => $address !== '' ? $address : null,
                'age' => $age,
                'birthday' => $birthday,
                'status' => $status,
                'email_verified_at' => now(),
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function normalizeHeaderKey(string $header): string
    {
        $h = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $header)));
        $h = rtrim($h, " \t\n\r\0\x0B.:");

        return match ($h) {
            'student id', 'student id no', 'student id number', 'student number', 'student no',
            'id number', 'id no', 'id #', 'id#', 'school id', 'user id', 'username', 'userid',
            'lrn', 'learner reference number', "learner's reference number" => 'user_id',
            'last name', 'lastname', 'surname', 'family name' => 'last_name',
            'first name', 'firstname', 'given name' => 'first_name',
            'middle name', 'middlename', 'middle initial', 'm.i.', 'mi' => 'middle_name',
            'name', 'full name', 'fullname', 'student name', 'complete name', 'display name' => 'full_name',
            'e-mail', 'email', 'email address' => 'email',
            'department', 'course', 'program' => 'department',
            'year level', 'yr level', 'year', 'level', 'grade level', 'grade' => 'year_level',
            'address', 'home address', 'residential address' => 'address',
            'age' => 'age',
            'birthday', 'birth date', 'date of birth', 'dob', 'birthdate' => 'birthday',
            'status', 'account status' => 'status',
            default => $h,
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitFullName(string $full): array
    {
        $full = trim(preg_replace('/\s+/u', ' ', $full));
        if ($full === '') {
            return ['', ''];
        }
        $parts = preg_split('/\s+/u', $full) ?: [];
        if ($parts === []) {
            return ['', ''];
        }
        if (count($parts) === 1) {
            return [$parts[0], '-'];
        }
        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }

    private function cellString($sheet, int $row, int $colIndex): string
    {
        $letter = Coordinate::stringFromColumnIndex($colIndex);
        $val = $sheet->getCell($letter.$row)->getValue();

        if ($val === null) {
            return '';
        }
        if (is_int($val) || (is_float($val) && floor($val) == $val && abs($val) < 1e15)) {
            return trim((string) (int) $val);
        }

        return trim((string) $val);
    }

    private function parseBirthday(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);

                return $dt->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
