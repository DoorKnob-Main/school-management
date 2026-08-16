@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/fullcalendar5.9.0.min.css') }}">
<script src="{{ asset('js/fullcalendar5.9.0.main.min.js') }}"></script>
<div class="container">
    <div class="row justify-content-start">
        @include('layouts.left-menu')
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10">
            <div class="row pt-2">
                <div class="col ps-4">
                    <h1 class="display-6 mb-3">
                        <i class="bi bi-calendar2-week"></i> View Attendance
                    </h1>

                    <h5><i class="bi bi-person"></i> Student Name: {{$student->first_name}} {{$student->last_name}}</h5>
                    <div class="row mt-3">
                        <div class="col bg-white p-3 border shadow-sm">
                            <div id="attendanceCalendar"></div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col bg-white border shadow-sm p-3 rounded">
                            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-list-check text-primary me-2"></i>Attendance Log History</h6>
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase">
                                        <th scope="col">Date</th>
                                        <th scope="col">First IN</th>
                                        <th scope="col">Last OUT</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Late / Early</th>
                                        <th scope="col">Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attendances as $attendance)
                                        <tr>
                                            <td class="fw-bold text-dark">{{ Carbon\Carbon::parse($attendance->created_at)->format('Y-m-d (D)') }}</td>
                                            <td>
                                                @if($attendance->in_time)
                                                    <span class="fw-semibold text-success"><i class="bi bi-box-arrow-in-right me-1"></i>{{ Carbon\Carbon::parse($attendance->in_time)->format('h:i A') }}</span>
                                                @else
                                                    <span class="text-muted">--:--</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($attendance->out_time)
                                                    <span class="fw-semibold text-danger"><i class="bi bi-box-arrow-right me-1"></i>{{ Carbon\Carbon::parse($attendance->out_time)->format('h:i A') }}</span>
                                                @else
                                                    <span class="badge bg-light text-muted border">Missing</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($attendance->status == "on" || $attendance->status == "present")
                                                    @if($attendance->late_minutes > 0)
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>LATE</span>
                                                    @else
                                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>PRESENT</span>
                                                    @endif
                                                @elseif($attendance->status == "late")
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>LATE</span>
                                                @elseif($attendance->status == "on_leave")
                                                    <span class="badge bg-info text-dark"><i class="bi bi-envelope-paper me-1"></i>ON LEAVE</span>
                                                @elseif($attendance->status == "holiday")
                                                    <span class="badge bg-secondary">HOLIDAY</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>ABSENT</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($attendance->late_minutes > 0)
                                                    <span class="text-danger small fw-semibold">+{{ $attendance->late_minutes }}m late</span>
                                                @endif
                                                @if($attendance->early_leave_minutes > 0)
                                                    <span class="text-warning small fw-semibold">{{ $attendance->early_leave_minutes }}m early</span>
                                                @endif
                                                @if($attendance->late_minutes == 0 && $attendance->early_leave_minutes == 0)
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $attendance->attendance_source === 'biometric' ? 'bg-primary' : 'bg-secondary' }}">
                                                    {{ ucfirst($attendance->attendance_source ?? 'Manual') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No attendance logs available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>
@php
$events = array();
if(count($attendances) > 0){
    foreach ($attendances as $attendance){
        $dateFormatted = Carbon\Carbon::parse($attendance->created_at)->toDateString();
        if($attendance->status == "on" || $attendance->status == "present"){
            if($attendance->late_minutes > 0) {
                $events[] = ['title'=> "Late (+{$attendance->late_minutes}m)", 'start' => $dateFormatted, 'color'=>'#ffc107', 'textColor' => '#000'];
            } else {
                $events[] = ['title'=> "Present", 'start' => $dateFormatted, 'color'=>'#198754'];
            }
        } elseif($attendance->status == "late") {
            $events[] = ['title'=> "Late (+{$attendance->late_minutes}m)", 'start' => $dateFormatted, 'color'=>'#ffc107', 'textColor' => '#000'];
        } elseif($attendance->status == "on_leave") {
            $events[] = ['title'=> "On Leave", 'start' => $dateFormatted, 'color'=>'#0dcaf0', 'textColor' => '#000'];
        } else {
            $events[] = ['title'=> "Absent", 'start' => $dateFormatted, 'color'=>'#dc3545'];
        }
    }
}
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('attendanceCalendar');
    var attEvents = @json($events);
                            
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 380,
        events: attEvents,
    });
    calendar.render();
});
</script>
@endsection
