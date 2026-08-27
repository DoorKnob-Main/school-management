@extends('layouts.app')

@section('content')
<style>
    .routine-report-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        padding: 28px 32px;
        margin-bottom: 30px;
    }
    .routine-table th, .routine-table td {
        vertical-align: middle;
    }
    .routine-day-badge {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 800;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 12px 14px;
        min-width: 120px;
        text-align: center;
        border: 1px solid #cbd5e1 !important;
        border-left: 4px solid var(--bs-primary, #0d6efd) !important;
        text-transform: uppercase;
    }
    .routine-slot-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 12px;
        transition: all 0.2s ease;
        min-width: 140px;
    }
    .routine-slot-card:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
    }
    .routine-course-name {
        font-weight: 700;
        font-size: 0.95rem;
        color: #1e293b;
    }
    .routine-teacher-name {
        font-size: 0.82rem;
        color: var(--bs-primary, #0d6efd);
        font-weight: 600;
        margin-top: 3px;
    }
    .routine-time-badge {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 4px;
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            background-color: #ffffff !important;
            color: #000000 !important;
            font-size: 11pt;
        }
        .no-print, nav, .navbar, .breadcrumb, .btn, .col-xs-1, .col-sm-1, .col-md-1, .col-lg-2, .border-rt-e6 {
            display: none !important;
        }
        .container-fluid, .row, .col-xs-11, .col-sm-11, .col-md-11, .col-lg-10, .col-xl-10, .col-xxl-10 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .routine-report-card {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .routine-table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .routine-table th, .routine-table td {
            border: 1px solid #94a3b8 !important;
            padding: 6px 8px !important;
        }
        .routine-day-badge {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
            border: 1px solid #94a3b8 !important;
        }
        .routine-slot-card {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            padding: 4px 6px !important;
        }
        .routine-teacher-name {
            color: #1e293b !important;
        }
        .report-signature-block, .report-footer {
            page-break-inside: avoid !important;
        }
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
</style>

<div class="container-fluid px-4">
    <div class="row justify-content-start">
        @include('layouts.left-menu')
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10">
            <div class="row pt-2">
                <div class="col ps-4">
                    <!-- Action Bar & Breadcrumb (Hidden during Print) -->
                    <div class="no-print mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                            <div>
                                <h1 class="display-6 mb-1"><i class="bi bi-calendar4-range text-primary me-2"></i>Class Routine</h1>
                                <p class="text-muted small mb-0">View, manage and print official weekly class timetable schedule</p>
                            </div>
                            <div class="d-flex gap-2 mt-2 mt-sm-0">
                                <button onclick="window.print()" class="btn btn-primary btn-sm shadow-sm">
                                    <i class="bi bi-printer me-1"></i> Print Routine
                                </button>
                                <a href="{{ route('class.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                                    <i class="bi bi-arrow-left me-1"></i> Back to Classes
                                </a>
                                @can('create routines')
                                <a href="{{ route('section.routine.create') }}" class="btn btn-outline-primary btn-sm shadow-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Create Routine
                                </a>
                                @endcan
                            </div>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                                <li class="breadcrumb-item"><a href="{{route('class.index')}}">Classes</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Section Routine</li>
                            </ol>
                        </nav>
                        @include('session-messages')
                    </div>

                    @php
                        $dayNames = [
                            1 => 'MONDAY',
                            2 => 'TUESDAY',
                            3 => 'WEDNESDAY',
                            4 => 'THURSDAY',
                            5 => 'FRIDAY',
                            6 => 'SATURDAY',
                            7 => 'SUNDAY',
                        ];
                        $className = $school_class->class_name ?? 'Class';
                        $sectionName = $section->section_name ?? 'Section';
                        $roomNo = $section->room_no ?? 'N/A';
                        $sessionName = $session->session_name ?? (date('Y') . ' - ' . (date('Y') + 1));
                    @endphp

                    <!-- Printable Routine Document Container -->
                    <div class="routine-report-card border">
                        <!-- Official School Report Header -->
                        <x-report-header 
                            title="CLASS TIMETABLE & ROUTINE"
                            :subtitle="$className . ' - ' . $sectionName . ' | Room: ' . $roomNo . ' | Session: ' . $sessionName"
                            :docNumber="'ROUTINE-' . ($class_id ?: '0') . '-' . ($section_id ?: '0')"
                            :date="date('d M Y')" 
                        />

                        <!-- Academic Meta Summary Banner -->
                        <div class="row bg-light p-3 rounded mb-4 border align-items-center">
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Class</small>
                                <span class="fw-bold text-dark fs-6">{{ $className }}</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Section / Room</small>
                                <span class="fw-bold text-dark fs-6">{{ $sectionName }} @if($roomNo !== 'N/A') (Room {{ $roomNo }}) @endif</span>
                            </div>
                            <div class="col-6 col-md-3 mt-2 mt-md-0">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Academic Session</small>
                                <span class="fw-bold text-dark fs-6">{{ $sessionName }}</span>
                            </div>
                            <div class="col-6 col-md-3 mt-2 mt-md-0 text-md-end">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.72rem;">Active Schedule</small>
                                <span class="badge bg-primary fs-7">{{ count($routines) }} Scheduled Days</span>
                            </div>
                        </div>

                        <!-- Routine Schedule Table -->
                        @if(count($routines) > 0)
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered routine-table mb-0 align-middle">
                                <tbody>
                                    @foreach($routines as $day => $courses)
                                        @php
                                            $sortedCourses = $courses->sortBy('start');
                                            $dayLabel = $dayNames[$day] ?? ('Day ' . $day);
                                        @endphp
                                        <tr>
                                            <th scope="row" class="routine-day-badge align-middle">
                                                {{ $dayLabel }}
                                            </th>
                                            <td class="p-2">
                                                <div class="d-flex flex-wrap gap-2 align-items-stretch">
                                                    @foreach($sortedCourses as $course)
                                                        @php
                                                            $teacher = $course->teacher;
                                                        @endphp
                                                        <div class="routine-slot-card flex-fill">
                                                            <div class="d-flex justify-content-between align-items-start gap-1">
                                                                <span class="routine-course-name">{{ $course->course->course_name ?? 'Subject' }}</span>
                                                                @if(isset($course->course->course_type))
                                                                    <span class="badge bg-white text-secondary border small">{{ $course->course->course_type }}</span>
                                                                @endif
                                                            </div>
                                                            <div class="routine-teacher-name">
                                                                @if($teacher)
                                                                    <i class="bi bi-person-badge me-1"></i>{{ $teacher->first_name }} {{ $teacher->last_name }}
                                                                @else
                                                                    <span class="text-muted fst-italic"><i class="bi bi-person me-1"></i>Not Assigned</span>
                                                                @endif
                                                            </div>
                                                            <div class="routine-time-badge">
                                                                <i class="bi bi-clock me-1"></i>{{ $course->start }} - {{ $course->end }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info py-4 text-center my-3">
                            <i class="bi bi-info-circle fs-3 d-block mb-2 text-primary"></i>
                            <h5 class="alert-heading fw-bold">No Routine Slots Found</h5>
                            <p class="mb-2 text-muted">No class routine schedule has been created yet for {{ $className }} - {{ $sectionName }}.</p>
                            @can('create routines')
                            <a href="{{ route('section.routine.create') }}" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-plus-circle me-1"></i> Create Routine Schedule
                            </a>
                            @endcan
                        </div>
                        @endif

                        <!-- Official School Report Footer -->
                        <x-report-footer 
                            :issuedBy="Auth::check() ? (Auth::user()->first_name . ' ' . Auth::user()->last_name) : 'Academic Department'" 
                            :showSignature="true" 
                            :showStamp="true" 
                        />
                    </div>
                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>
@endsection
