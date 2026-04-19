<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of faculties — {{ config('app.name') }}</title>
    <style>
        /* Screen preview: roughly sheet width so layout matches print */
        html { background: #e2e8f0; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.45;
            color: #0f172a;
            margin: 1.25rem auto 2rem;
            padding: 1.25rem 1.5rem 1.5rem;
            max-width: 210mm;
            background: #fff;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08);
            border-radius: 4px;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.35rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .header img {
            width: 4.25rem;
            height: 4.25rem;
            object-fit: contain;
            flex-shrink: 0;
        }
        h1 {
            font-size: 1.35rem;
            margin: 0;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5pt;
        }
        .data-table thead {
            display: table-header-group;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #334155;
            padding: 0.55rem 0.65rem;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }
        .data-table th {
            background: #f1f5f9;
            font-weight: 600;
            font-size: 9.5pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #334155;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }
        .data-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        .toolbar {
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .toolbar button,
        .toolbar a {
            font: inherit;
            padding: 0.45rem 0.85rem;
            border-radius: 0.375rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .toolbar button { background: #1d4ed8; color: #fff; border: 1px solid #1d4ed8; }
        .toolbar a.back { background: #e5e7eb; color: #111; border: 1px solid #d1d5db; }

        /*
         * Print: use the paper size chosen in the browser (A4, Letter/short, Legal/long).
         * size: auto respects the printer dialog selection.
         */
        @page {
            size: auto;
            margin: 15mm 16mm 18mm 16mm;
        }

        @media print {
            html { background: #fff; }
            body {
                margin: 0;
                padding: 0;
                max-width: none;
                box-shadow: none;
                border-radius: 0;
                font-size: 10.5pt;
                line-height: 1.42;
            }
            .toolbar { display: none !important; }
            .header {
                margin-bottom: 1.1rem;
                padding-bottom: 0.85rem;
                border-bottom: 1px solid #cbd5e1;
            }
            .data-table th,
            .data-table td {
                padding: 0.5rem 0.6rem;
            }
            .data-table {
                font-size: 10pt;
            }
            .data-table th {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .data-table tbody tr:nth-child(even) td {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('admin.faculties.index', request()->except('page')) }}" class="back">Back to list</a>
    </div>

    <header class="header">
        <img src="/norsu.webp" alt="Logo">
        <h1>List of faculties{{ $isArchived ? ' (archived)' : '' }}</h1>
    </header>

    <table class="data-table">
        <colgroup>
            <col style="width: 26%">
            <col style="width: 13%">
            <col style="width: 28%">
            <col style="width: 18%">
            <col style="width: 15%">
        </colgroup>
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">ID no.</th>
                <th scope="col">Department</th>
                <th scope="col">Employment</th>
                <th scope="col">Account</th>
            </tr>
        </thead>
        <tbody>
            @forelse($faculties as $faculty)
                <tr>
                    <td>{{ $faculty->full_name }}</td>
                    <td>{{ $faculty->user_id }}</td>
                    <td>{{ $faculty->department?->name ?? '—' }}</td>
                    <td>{{ \App\Http\Controllers\Admin\FacultyController::employmentStatusLabel($faculty->employment_status) }}</td>
                    <td>{{ ucfirst($faculty->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 1.25rem;">No faculty match the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 200);
        });
    </script>
</body>
</html>
