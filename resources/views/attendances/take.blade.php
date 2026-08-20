@extends('layouts.app')

@php
    // Values match the biometric vocabulary: 'on' = present, 'off' = absent.
    $statusMeta = [
        'on'       => ['label' => 'Present', 'btn' => 'success',  'icon' => 'check-circle'],
        'off'      => ['label' => 'Absent',  'btn' => 'danger',   'icon' => 'x-circle'],
        'late'     => ['label' => 'Late',    'btn' => 'warning',  'icon' => 'clock-history'],
        'on_leave' => ['label' => 'Leave',   'btn' => 'info',     'icon' => 'envelope-paper'],
    ];
    $isCourse = $academic_setting->attendance_type == 'course';
    $scopeName = $isCourse ? request()->query('course_name') : optional($school_section)->section_name;
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
                            <li class="breadcrumb-item active" aria-current="page">Take</li>
                        </ol>
                    </nav>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h1 class="display-6 mb-0"><i class="bi bi-pencil-square"></i> Take Attendance</h1>
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
                        </div>
                    </div>

                    @include('session-messages')

                    @if($attendance_count > 0)
                        <div class="alert alert-info d-flex align-items-center py-2">
                            <i class="bi bi-info-circle me-2"></i>
                            Attendance already recorded for this date — you're editing it. Saving updates the existing records.
                        </div>
                    @endif

                    @if(count($student_list) == 0)
                        <div class="alert alert-warning"><i class="bi bi-people"></i> No students enrolled here.</div>
                    @else
                    <form action="{{ route('attendances.store') }}" method="POST" id="attForm">
                        @csrf
                        <input type="hidden" name="session_id" value="{{ $current_school_session_id }}">
                        <input type="hidden" name="class_id" value="{{ request()->query('class_id') }}">
                        <input type="hidden" name="attendance_date" value="{{ $attendance_date }}">
                        @if($isCourse)
                            <input type="hidden" name="course_id" value="{{ $course_id }}">
                            <input type="hidden" name="section_id" value="0">
                        @else
                            <input type="hidden" name="course_id" value="0">
                            <input type="hidden" name="section_id" value="{{ $section_id }}">
                        @endif

                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white">
                                <div class="row g-2 align-items-center">
                                    <div class="col-12 col-lg-5">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                            <input type="text" id="studentSearch" class="form-control" placeholder="Search student by name or ID…">
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-7 text-lg-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-success bulk-btn" data-status="on"><i class="bi bi-check-all"></i> All Present</button>
                                            <button type="button" class="btn btn-outline-danger bulk-btn" data-status="off"><i class="bi bi-x-octagon"></i> All Absent</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="rosterTable">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-muted">
                                            <th style="width:60px;">#</th>
                                            <th>ID Card</th>
                                            <th>Student Name</th>
                                            <th style="width:420px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($student_list as $i => $student)
                                            @php
                                                $existing = $existing_attendance[$student->student_id] ?? null;
                                                if (is_null($existing)) {
                                                    $sel = 'on'; // no record yet => default present
                                                } elseif (in_array($existing, ['on', 'present'])) {
                                                    $sel = 'on';
                                                } elseif (in_array($existing, ['off', 'absent'])) {
                                                    $sel = 'off';
                                                } elseif (in_array($existing, ['late', 'on_leave'])) {
                                                    $sel = $existing;
                                                } else {
                                                    $sel = 'off'; // holiday/pending/unknown => absent when editing manually
                                                }
                                            @endphp
                                            <tr class="student-row" data-name="{{ strtolower($student->student->first_name.' '.$student->student->last_name.' '.$student->id_card_number) }}">
                                                <td class="text-muted">{{ $i + 1 }}</td>
                                                <td><input type="hidden" name="student_ids[]" value="{{ $student->student_id }}">{{ $student->id_card_number }}</td>
                                                <td class="fw-medium">{{ $student->student->first_name }} {{ $student->student->last_name }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm w-100" role="group" aria-label="status">
                                                        @foreach ($statusMeta as $key => $meta)
                                                            <input type="radio" class="btn-check status-radio"
                                                                   name="status[{{ $student->student_id }}]"
                                                                   id="st_{{ $student->student_id }}_{{ $key }}"
                                                                   value="{{ $key }}" {{ $sel === $key ? 'checked' : '' }}>
                                                            <label class="btn btn-outline-{{ $meta['btn'] }}" for="st_{{ $student->student_id }}_{{ $key }}">
                                                                <i class="bi bi-{{ $meta['icon'] }}"></i>
                                                                <span class="d-none d-xl-inline">{{ $meta['label'] }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex flex-wrap gap-2 small">
                                    <span class="badge bg-success">Present: <span id="cnt-on">0</span></span>
                                    <span class="badge bg-danger">Absent: <span id="cnt-off">0</span></span>
                                    <span class="badge bg-warning text-dark">Late: <span id="cnt-late">0</span></span>
                                    <span class="badge bg-info text-dark">Leave: <span id="cnt-on_leave">0</span></span>
                                    <span class="badge bg-secondary">Total: {{ count($student_list) }}</span>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Attendance</button>
                            </div>
                        </div>
                    </form>
                    @endif

                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Date change reloads the page for that date (re-loads existing marks).
    var dateInput = document.getElementById('attDate');
    if (dateInput) {
        dateInput.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('date', this.value);
            window.location.href = url.toString();
        });
    }

    // Live status counters.
    function recount() {
        var counts = {on:0, off:0, late:0, on_leave:0};
        document.querySelectorAll('.status-radio:checked').forEach(function (r) {
            if (counts.hasOwnProperty(r.value)) counts[r.value]++;
        });
        Object.keys(counts).forEach(function (k) {
            var el = document.getElementById('cnt-' + k);
            if (el) el.textContent = counts[k];
        });
    }
    document.querySelectorAll('.status-radio').forEach(function (r) {
        r.addEventListener('change', recount);
    });

    // Bulk set (only visible/filtered rows).
    document.querySelectorAll('.bulk-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var status = this.getAttribute('data-status');
            document.querySelectorAll('#rosterTable tbody tr.student-row').forEach(function (row) {
                if (row.style.display === 'none') return;
                var radio = row.querySelector('.status-radio[value="' + status + '"]');
                if (radio) radio.checked = true;
            });
            recount();
        });
    });

    // Search filter.
    var search = document.getElementById('studentSearch');
    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('#rosterTable tbody tr.student-row').forEach(function (row) {
                row.style.display = row.getAttribute('data-name').indexOf(q) > -1 ? '' : 'none';
            });
        });
    }

    recount();
});
</script>
@endsection
