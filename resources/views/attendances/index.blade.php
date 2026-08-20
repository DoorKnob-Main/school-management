@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-start">
        @include('layouts.left-menu')
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10">
            <div class="row pt-2">
                <div class="col ps-4">

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                        <h1 class="display-6 mb-0">
                            <i class="bi bi-calendar2-check"></i> Manual Attendance
                        </h1>
                        <div class="d-flex align-items-center gap-2">
                            <label for="attDate" class="text-muted small mb-0">Date</label>
                            <input type="date" id="attDate" class="form-control form-control-sm" style="max-width: 180px;"
                                   value="{{ request('date', $today) }}" max="{{ $today }}">
                        </div>
                    </div>
                    <p class="text-muted">
                        Pick a class and
                        {{ $academic_setting->attendance_type == 'course' ? 'course' : 'section' }}
                        to take or view attendance for the selected date.
                    </p>

                    @include('session-messages')

                    @forelse ($classes as $school_class)
                        <div class="card shadow-sm border-0 mb-3">
                            <div class="card-header bg-white d-flex align-items-center border-bottom">
                                <span class="badge rounded-pill bg-primary-subtle text-primary me-2">
                                    <i class="bi bi-mortarboard"></i>
                                </span>
                                <h5 class="mb-0 fw-semibold">{{ $school_class->class_name }}</h5>
                            </div>
                            <div class="card-body">
                                @if ($academic_setting->attendance_type == 'course')
                                    @php $items = $school_class->courses; @endphp
                                    @if ($items->isEmpty())
                                        <div class="text-muted small fst-italic">No courses in this class.</div>
                                    @else
                                        <div class="row g-2">
                                            @foreach ($items as $course)
                                                <div class="col-12 col-md-6 col-xl-4">
                                                    <div class="border rounded p-2 d-flex justify-content-between align-items-center h-100">
                                                        <span class="fw-medium text-truncate me-2">
                                                            <i class="bi bi-journal-text text-secondary me-1"></i>{{ $course->course_name }}
                                                        </span>
                                                        <span class="btn-group btn-group-sm flex-shrink-0">
                                                            <a class="btn btn-primary att-link"
                                                               data-href="{{ url('attendances/take?class_id='.$school_class->id.'&class_name='.urlencode($school_class->class_name).'&course_id='.$course->id.'&course_name='.urlencode($course->course_name)) }}">
                                                                <i class="bi bi-pencil-square"></i> Take
                                                            </a>
                                                            <a class="btn btn-outline-secondary att-link"
                                                               data-href="{{ url('attendances/view?class_id='.$school_class->id.'&class_name='.urlencode($school_class->class_name).'&course_id='.$course->id.'&course_name='.urlencode($course->course_name)) }}">
                                                                <i class="bi bi-eye"></i> View
                                                            </a>
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    @php $items = $school_class->sections; @endphp
                                    @if ($items->isEmpty())
                                        <div class="text-muted small fst-italic">No sections in this class.</div>
                                    @else
                                        <div class="row g-2">
                                            @foreach ($items as $section)
                                                <div class="col-12 col-md-6 col-xl-4">
                                                    <div class="border rounded p-2 d-flex justify-content-between align-items-center h-100">
                                                        <span class="fw-medium text-truncate me-2">
                                                            <i class="bi bi-people text-secondary me-1"></i>{{ $section->section_name }}
                                                        </span>
                                                        <span class="btn-group btn-group-sm flex-shrink-0">
                                                            <a class="btn btn-primary att-link"
                                                               data-href="{{ url('attendances/take?class_id='.$school_class->id.'&class_name='.urlencode($school_class->class_name).'&section_id='.$section->id.'&section_name='.urlencode($section->section_name)) }}">
                                                                <i class="bi bi-pencil-square"></i> Take
                                                            </a>
                                                            <a class="btn btn-outline-secondary att-link"
                                                               data-href="{{ url('attendances/view?class_id='.$school_class->id.'&class_name='.urlencode($school_class->class_name).'&section_id='.$section->id.'&section_name='.urlencode($section->section_name)) }}">
                                                                <i class="bi bi-eye"></i> View
                                                            </a>
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> No classes available for the current session.
                        </div>
                    @endforelse

                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>

<script>
// Keep every Take/View link in sync with the chosen date.
document.addEventListener('DOMContentLoaded', function () {
    var dateInput = document.getElementById('attDate');
    function applyDate() {
        var d = dateInput.value;
        document.querySelectorAll('.att-link').forEach(function (a) {
            var base = a.getAttribute('data-href');
            a.setAttribute('href', d ? base + '&date=' + encodeURIComponent(d) : base);
        });
    }
    dateInput.addEventListener('change', applyDate);
    applyDate();
});
</script>
@endsection
