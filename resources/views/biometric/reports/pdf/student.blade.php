<div style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <div style="border-bottom: 2px solid #0d6efd; padding-bottom: 12px; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #0d6efd;">Student Attendance & Telemetry Report</h2>
        <p style="margin: 4px 0 0 0; font-size: 13px; color: #666;">Generated on {{ now()->format('F d, Y H:i:s') }}</p>
    </div>

    <!-- Student Metadata -->
    <table style="width: 100%; margin-bottom: 20px; font-size: 14px;">
        <tr>
            <td style="width: 50%;"><strong>Student Name:</strong> {{ $student->first_name }} {{ $student->last_name }}</td>
            <td style="width: 50%;"><strong>Student ID:</strong> #{{ $student->id }}</td>
        </tr>
        <tr>
            <td><strong>Reporting Period:</strong> {{ $startDate }} to {{ $endDate }}</td>
            <td><strong>Email:</strong> {{ $student->email }}</td>
        </tr>
    </table>

    <!-- Metric Summary Grid -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; text-align: center; font-size: 13px;">
        <tr>
            <td style="border: 1px solid #ddd; padding: 10px; background: #f8f9fa;">
                <div style="font-size: 11px; color: #666; text-transform: uppercase;">Evaluated</div>
                <div style="font-size: 18px; font-weight: bold;">{{ $summary['total_days'] }}</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 10px; background: #f8f9fa;">
                <div style="font-size: 11px; color: #198754; text-transform: uppercase;">Present</div>
                <div style="font-size: 18px; font-weight: bold; color: #198754;">{{ $summary['present'] }}</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 10px; background: #f8f9fa;">
                <div style="font-size: 11px; color: #ffc107; text-transform: uppercase;">Late</div>
                <div style="font-size: 18px; font-weight: bold; color: #d39e00;">{{ $summary['late'] }}</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 10px; background: #f8f9fa;">
                <div style="font-size: 11px; color: #dc3545; text-transform: uppercase;">Absent</div>
                <div style="font-size: 18px; font-weight: bold; color: #dc3545;">{{ $summary['absent'] }}</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 10px; background: #f8f9fa;">
                <div style="font-size: 11px; color: #0dcaf0; text-transform: uppercase;">Leave</div>
                <div style="font-size: 18px; font-weight: bold; color: #0aa2c0;">{{ $summary['on_leave'] }}</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 10px; background: #f8f9fa;">
                <div style="font-size: 11px; color: #0d6efd; text-transform: uppercase;">Attendance %</div>
                <div style="font-size: 18px; font-weight: bold; color: #0d6efd;">{{ $summary['percentage'] }}%</div>
            </td>
        </tr>
    </table>

    <!-- Attendance Table -->
    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
        <thead>
            <tr style="background: #0d6efd; color: #fff; text-align: left;">
                <th style="padding: 8px; border: 1px solid #0d6efd;">Date</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">First IN (Scan)</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Last OUT (Scan)</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Status</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Late (Mins)</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Early Leave</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Source</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $att)
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 7px; border: 1px solid #ddd;">{{ \Carbon\Carbon::parse($att->created_at)->format('Y-m-d (D)') }}</td>
                <td style="padding: 7px; border: 1px solid #ddd;">{{ $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('h:i:s A') : '--:--' }}</td>
                <td style="padding: 7px; border: 1px solid #ddd;">{{ $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('h:i:s A') : 'Missing' }}</td>
                <td style="padding: 7px; border: 1px solid #ddd; font-weight: bold;">
                    @if($att->status === 'on' || $att->status === 'present')
                        <span style="color: #198754;">Present</span>
                    @elseif($att->status === 'late')
                        <span style="color: #d39e00;">Late</span>
                    @elseif($att->status === 'on_leave')
                        <span style="color: #0aa2c0;">On Leave</span>
                    @elseif($att->status === 'holiday')
                        <span style="color: #6c757d;">Holiday</span>
                    @else
                        <span style="color: #dc3545;">Absent</span>
                    @endif
                </td>
                <td style="padding: 7px; border: 1px solid #ddd;">{{ $att->late_minutes > 0 ? "+{$att->late_minutes}m" : '-' }}</td>
                <td style="padding: 7px; border: 1px solid #ddd;">{{ $att->early_leave_minutes > 0 ? "{$att->early_leave_minutes}m early" : '-' }}</td>
                <td style="padding: 7px; border: 1px solid #ddd;">{{ ucfirst($att->attendance_source) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="padding: 15px; text-align: center; color: #999;">No attendance logs found in selected range.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
