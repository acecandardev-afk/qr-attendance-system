<?php

namespace App\Services;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentExcelExportService
{
    public function export(?int $departmentId = null, ?string $yearLevel = null): StreamedResponse
    {
        $query = User::students()->with('department')->orderBy('last_name')->orderBy('first_name');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($yearLevel !== null && $yearLevel !== '') {
            $query->where('year_level', $yearLevel);
        }

        $students = $query->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');

        $headers = ['Student ID', 'Last name', 'First name', 'Middle name', 'Email', 'Department', 'Year level', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $col++;
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D4ED8'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $row = 2;
        foreach ($students as $s) {
            $sheet->setCellValue('A'.$row, $s->user_id);
            $sheet->setCellValue('B'.$row, $s->last_name);
            $sheet->setCellValue('C'.$row, $s->first_name);
            $sheet->setCellValue('D'.$row, $s->middle_name ?? '');
            $sheet->setCellValue('E'.$row, $s->email);
            $sheet->setCellValue('F'.$row, $s->department?->name ?? '');
            $sheet->setCellValue('G'.$row, $s->year_level ?? '');
            $sheet->setCellValue('H'.$row, ucfirst($s->status));
            $row++;
        }

        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $lastRow = max(1, $row - 1);
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:H'.$lastRow)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
            $sheet->getStyle('A2:H'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        $filename = 'students_'.now()->format('Y-m-d_His').'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
