@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <!-- Sidebar Menu -->
        @include('layouts.left-menu')

        <!-- Main Content Area -->
        <div class="col-12 col-lg-10 ps-md-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-bar-chart-line text-primary me-2"></i>Biometric Attendance Reports</h3>
            <p class="text-muted mb-0 small">Comprehensive attendance analytics, telemetry reports, and PDF exports with school letterhead</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Student Detailed Attendance Report -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle me-3">
                            <i class="bi bi-person-lines-fill fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Student Attendance Report</h5>
                            <small class="text-muted">Individual student punch log & percentages</small>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">Detailed breakdown of first scan IN, last scan OUT, late minutes, leaves, and total monthly attendance percentage for any student.</p>
                    <a href="{{ route('biometric.reports.student') }}" class="btn btn-outline-primary btn-sm w-100 mt-2">Generate Report <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Class Attendance Report -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle me-3">
                            <i class="bi bi-diagram-3 fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Class Attendance Sheet</h5>
                            <small class="text-muted">Class & Section daily/monthly register</small>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">Export complete attendance roster for an entire class or section on any selected date, printable as PDF with official letterhead.</p>
                    <a href="{{ route('biometric.reports.class') }}" class="btn btn-outline-success btn-sm w-100 mt-2">Generate Report <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Late Arrival Report -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle me-3">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Late Arrivals Report</h5>
                            <small class="text-muted">Students arriving after cutoff window</small>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">Audit list of students who scanned past the configured morning cutoff with exact minutes late and first punch timestamps.</p>
                    <a href="{{ route('biometric.reports.late') }}" class="btn btn-outline-warning btn-sm w-100 mt-2">Generate Report <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Early Departure Report -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle me-3">
                            <i class="bi bi-box-arrow-right fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Early Departure Report</h5>
                            <small class="text-muted">Students leaving before dispersal time</small>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">Track students whose last punch occurred prior to the authorized school dispersal bell or early leave threshold.</p>
                    <a href="{{ route('biometric.reports.early') }}" class="btn btn-outline-danger btn-sm w-100 mt-2">Generate Report <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Missing Checkout Report -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle me-3">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Missing Checkout Report</h5>
                            <small class="text-muted">Valid IN punch with missing OUT scan</small>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">Identify students who scanned into school in the morning but did not register a departure scan at the exit turnstile or gate.</p>
                    <a href="{{ route('biometric.reports.missing-checkout') }}" class="btn btn-outline-info btn-sm w-100 mt-2">Generate Report <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <!-- Raw Punch Audit Log -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-secondary bg-opacity-10 text-secondary rounded-circle me-3">
                            <i class="bi bi-journal-text fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Raw Punch Log Repository</h5>
                            <small class="text-muted">Complete telemetry audit history</small>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">Search and filter every raw biometric scan event with sensor ID, device location, and verification method details.</p>
                    <a href="{{ route('biometric.punches') }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">View Raw Logs <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>
@endsection
