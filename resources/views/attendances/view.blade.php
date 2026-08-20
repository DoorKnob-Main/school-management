@extends('layouts.app')

@php
    $isCourse = request()->query('course_name') ? true : false;
    $scopeName = $isCourse ? request()->query('course_name') : optional($school_section)->section_name;
    $takeBase = url('attendances/take?class_id='.request()->query('class_id')
        .'&class_name='.urlencode(request()->query('class_name', optional($school_class)->class_name))
        .($isCourse
            ? '&course_id='.$course_id.'&course_name='.urlencode(request()->query('course_name'))
            : '&section_id='.$section_id.'&section_name='.urlencode(request()->query('section_name', optional($school_section)->section_name))));
@endphp

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-start">
        @include('layouts.left-menu')
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10">
            <div class="row pt-2">
                <div class="col ps-4">

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Attendance</a></li>
                            <li class="breadcrumb-item active" aria-current="page">View</li>
                        </ol>
                    </nav>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h1 class="display-6 mb-0"><i class="bi bi-calendar2-week-fill"></i> View Attendance</h1>
                            <div class="text-muted mt-1">
                                <span class="badge bg-primary-subtle text-primary">{{ optional($school_class)->class_name }}</span>
                                @if($scopeName)
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="bi bi-{{ $isCourse ? 'journal-text' : 'people' }}"></i> {{ $scopeName }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <label for="attDate" class="text-muted small mb-0">Date</label>
                            <input type="date" id="attDate" class="form-control form-control-sm" style="max-width:180px;"
                                   value="{{ $attendance_date }}" max="{{ \Carbon\Carbon::today()->toDateString() }}">
                            <a href="{{ $takeBase.'&date='.$attendance_date }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil-square"></i> Take / Edit
                            </a>
                        </div>
                    </div>

                    @include('session-messages')

                    {{-- Summary --}}
                    <div class="row g-2 mb-3">
                        @php
                            $cards = [
                                ['Total', $summary['total'], 'secondary', 'people'],
                                ['Present', $summary['present'], 'success', 'check-circle'],
                                ['Late', $summary['late'], 'warning', 'clock-history'],
                                ['On Leave', $summary['on_leave'], 'info', 'envelope-paper'],
                                ['Absent', $summary['absent'], 'danger', 'x-circle'],
                            ];
                        @endphp
                        @foreach ($cards as $c)
                            <div class="col-6 col-md">
                                <div class="card border-0 shadow-sm text-center">
                                    <div class="card-body py-3">
                                        <div class="text-{{ $c[2] }} mb-1"><i class="bi bi-{{ $c[3] }} fs-4"></i></div>
                                        <div class="fs-4 fw-bold">{{ $c[1] }}</div>
                                        <div class="small text-muted text-uppercase">{{ $c[0] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th style="width:60px;">#</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                        <th>Source</th>
                                        <th class="text-end">Days Attended (session)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attendances as $i => $attendance)
                                        @php
                                            $total_attended = \App\Models\Attendance::where('student_id', $attendance->student_id)
                                                ->where('session_id', $attendance->session_id)
                                                ->whereIn('status', ['present', 'on', 'late'])
                                                ->count();
                                            $isPresent = in_array($attendance->status, ['present', 'on']);
                                        @endphp
                                        <tr>
                                            <td class="text-muted">{{ $i + 1 }}</td>
                                            <td class="fw-medium">{{ optional($attendance->student)->first_name }} {{ optional($attendance->student)->last_name }}</td>
                                            <td>
                                                @if ($attendance->status == 'late')
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>LATE</span>
                                                @elseif ($isPresent)
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>PRESENT</span>
                                                @elseif ($attendance->status == 'on_leave')
                                                    <span class="badge bg-info text-dark"><i class="bi bi-envelope-paper me-1"></i>ON LEAVE</span>
                                                @elseif ($attendance->status == 'holiday')
                                                    <span class="badge bg-secondary">HOLIDAY</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>ABSENT</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ ($attendance->attendance_source ?? 'manual') === 'biometric' ? 'bg-primary' : 'bg-secondary' }}">
                                                    {{ ucfirst($attendance->attendance_source ?? 'Manual') }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ $total_attended }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="bi bi-calendar-x"></i> No attendance recorded for this date.
                                                <a href="{{ $takeBase.'&date='.$attendance_date }}">Take it now.</a>
                                            </td>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var dateInput = document.getElementById('attDate');
    if (dateInput) {
        dateInput.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('date', this.value);
            window.location.href = url.toString();
        });
    }
});
</script>
@endsection
