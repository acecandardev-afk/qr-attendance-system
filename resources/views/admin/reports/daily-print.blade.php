<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily attendance — {{ $dailyStats['date'] ?? '' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif; margin: 0; padding: 1.25rem; color: #0f172a; }
        h1 { font-size: 1.35rem; margin: 0 0 0.25rem; }
        .meta { color: #64748b; font-size: 0.9rem; margin-bottom: 1.25rem; }
        .stats { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .stat { border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.65rem 1rem; min-width: 7rem; }
        .stat strong { display: block; font-size: 1.25rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { border: 1px solid #cbd5e1; padding: 0.45rem 0.5rem; text-align: left; }
        th { background: #f1f5f9; }
        .no-print { margin-bottom: 1rem; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" style="padding:0.5rem 1rem;font-weight:600;cursor:pointer;border-radius:8px;border:1px solid #0f3b8c;background:#0f3b8c;color:#fff;">
            Print this page
        </button>
        <a href="{{ route('admin.reports.index', ['date' => $dailyStats['date'] ?? null]) }}" style="margin-left:0.5rem;">Back to reports</a>
    </div>

    @if(!empty($dailyStats))
        <h1>Daily attendance report</h1>
        <p class="meta">{{ \Carbon\Carbon::parse($dailyStats['date'])->format('l, F j, Y') }}</p>

        <div class="stats">
            <div class="stat"><span style="color:#64748b;font-size:0.8rem;">Sessions</span><strong>{{ $dailyStats['total_sessions'] }}</strong></div>
            <div class="stat"><span style="color:#64748b;font-size:0.8rem;">Scans recorded</span><strong>{{ $dailyStats['total_attendance_marked'] }}</strong></div>
            <div class="stat"><span style="color:#64748b;font-size:0.8rem;">Present</span><strong>{{ $dailyStats['present'] }}</strong></div>
            <div class="stat"><span style="color:#64748b;font-size:0.8rem;">Late</span><strong>{{ $dailyStats['late'] }}</strong></div>
            <div class="stat"><span style="color:#64748b;font-size:0.8rem;">Absent</span><strong>{{ $dailyStats['absent'] }}</strong></div>
        </div>

        @if(count($dailyStats['sessions']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Faculty</th>
                        <th>Started</th>
                        <th>Status</th>
                        <th>Attendance count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyStats['sessions'] as $session)
                        <tr>
                            <td>{{ $session->schedule->course->code ?? '' }} {{ $session->schedule->course->name ?? '' }}</td>
                            <td>{{ $session->schedule->section->name ?? '—' }}</td>
                            <td>{{ $session->faculty->full_name ?? '—' }}</td>
                            <td>{{ optional($session->started_at)->format('g:i A') }}</td>
                            <td>{{ ucfirst($session->status) }}</td>
                            <td>{{ $session->attendanceRecords->count() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No sessions for this date.</p>
        @endif
    @else
        <p>No data.</p>
    @endif
</body>
</html>
