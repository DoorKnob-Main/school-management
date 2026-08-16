<div style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <div style="border-bottom: 2px solid #0d6efd; padding-bottom: 12px; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #0d6efd;">Class Attendance Register & Telemetry Sheet</h2>
        <p style="margin: 4px 0 0 0; font-size: 13px; color: #666;">Class: <strong>{{ $className }}</strong> | Date: <strong>{{ $date }}</strong></p>
    </div>

    <!-- Attendance Table -->
    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
        <thead>
            <tr style="background: #0d6efd; color: #fff; text-align: left;">
                <th style="padding: 8px; border: 1px solid #0d6efd;">Student Name</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Student ID</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">First IN</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Last OUT</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Status</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Late / Early</th>
                <th style="padding: 8px; border: 1px solid #0d6efd;">Source</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $att)
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 7px; border: 1px solid #ddd; font-weight: bold;">{{ $att->student->first_name ?? '' }} {{ $att->student->last_name ?? '' }}</td>
                <td style="padding: 7px; border: 1px solid #ddd;">#{{ $att->student_id }}</td>
                <td style="padding: 7px; border: 1px solid #ddd;">{{ $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('h:i A') : '--:--' }}</td>
                <td style="padding: 7px; border: 1px solid #ddd;">{{ $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('h:i A') : 'Missing' }}</td>
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
                <td style="padding: 7px; border: 1px solid #ddd;">
                    @if($att->late_minutes > 0)
                        <span style="color: #dc3545;">+{{ $att->late_minutes }}m</span>
                    @elseif($att->early_leave_minutes > 0)
                        <span style="color: #ffc107;">{{ $att->early_leave_minutes }}m early</span>
                    @else
                        -
                    @endif
                </td>
                <td style="padding: 7px; border: 1px solid #ddd;">{{ ucfirst($att->attendance_source) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="padding: 15px; text-align: center; color: #999;">No attendance records found for this class on {{ $date }}.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
