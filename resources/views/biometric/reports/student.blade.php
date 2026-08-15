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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i>Student Attendance Report</h3>
            <p class="text-muted mb-0 small">Individual student biometric punch logs, status breakdown, and attendance percentages</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('biometric.reports.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Reports Hub
            </a>
            @if($student)
            <a href="{{ route('biometric.reports.student', array_merge(request()->all(), ['export' => 'pdf'])) }}" target="_blank" class="btn btn-danger btn-sm shadow-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </a>
            @endif
        </div>
    </div>

    <!-- Filters Form -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('biometric.reports.student') }}" class="row g-2 align-items-center">
                @if(Auth::user()->effective_role !== 'student')
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">Select Student <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select form-select-sm" required>
                        <option value="">-- Choose Student --</option>
                        @foreach($students as $st)
                        <option value="{{ $st->id }}" {{ $studentId == $st->id ? 'selected' : '' }}>{{ $st->first_name }} {{ $st->last_name }} (ID: #{{ $st->id }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Generate</button>
                </div>
            </form>
        </div>
    </div>

    @if($student)
    <!-- Summary Metric Badges -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <span class="small text-muted text-uppercase fw-semibold d-block">Days Evaluated</span>
                    <h4 class="fw-bold mb-0 text-dark">{{ $summary['total_days'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-success">
                <div class="card-body p-3">
                    <span class="small text-muted text-uppercase fw-semibold d-block">Present</span>
                    <h4 class="fw-bold mb-0 text-success">{{ $summary['present'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-warning">
                <div class="card-body p-3">
                    <span class="small text-muted text-uppercase fw-semibold d-block">Late</span>
                    <h4 class="fw-bold mb-0 text-warning">{{ $summary['late'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-danger">
                <div class="card-body p-3">
                    <span class="small text-muted text-uppercase fw-semibold d-block">Absent</span>
                    <h4 class="fw-bold mb-0 text-danger">{{ $summary['absent'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-info">
                <div class="card-body p-3">
                    <span class="small text-muted text-uppercase fw-semibold d-block">On Leave</span>
                    <h4 class="fw-bold mb-0 text-info">{{ $summary['on_leave'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm border-0 border-start border-4 border-dark">
                <div class="card-body p-3">
                    <span class="small text-muted text-uppercase fw-semibold d-block">Attendance %</span>
                    <h4 class="fw-bold mb-0 text-primary">{{ $summary['percentage'] }}%</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Record Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0 text-dark">
                Attendance Timeline: <span class="text-primary">{{ $student->first_name }} {{ $student->last_name }}</span> ({{ $startDate }} to {{ $endDate }})
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Date</th>
                        <th>First IN Punch</th>
                        <th>Last OUT Punch</th>
                        <th>Status</th>
                        <th>Late Minutes</th>
                        <th>Early Leave</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                    <tr>
                        <td class="fw-bold text-dark">{{ Carbon\Carbon::parse($att->created_at)->format('Y-m-d (D)') }}</td>
                        <td>
                            @if($att->in_time)
                                <span class="fw-semibold text-success"><i class="bi bi-box-arrow-in-right me-1"></i>{{ Carbon\Carbon::parse($att->in_time)->format('h:i:s A') }}</span>
                            @else
                                <span class="text-muted">--:--</span>
                            @endif
                        </td>
                        <td>
                            @if($att->out_time)
                                <span class="fw-semibold text-danger"><i class="bi bi-box-arrow-right me-1"></i>{{ Carbon\Carbon::parse($att->out_time)->format('h:i:s A') }}</span>
                            @else
                                <span class="badge bg-light text-muted border">Missing</span>
                            @endif
                        </td>
                        <td>
                            @if($att->status === 'on' || $att->status === 'present')
                                @if($att->late_minutes > 0)
                                    <span class="badge bg-warning text-dark">Late</span>
                                @else
                                    <span class="badge bg-success">Present</span>
                                @endif
                            @elseif($att->status === 'late')
                                <span class="badge bg-warning text-dark">Late</span>
                            @elseif($att->status === 'on_leave')
                                <span class="badge bg-info text-dark">On Leave</span>
                            @elseif($att->status === 'holiday')
                                <span class="badge bg-secondary">Holiday</span>
                            @else
                                <span class="badge bg-danger">Absent</span>
                            @endif
                        </td>
                        <td>
                            @if($att->late_minutes > 0)
                                <span class="text-danger fw-semibold">+{{ $att->late_minutes }} min</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            @if($att->early_leave_minutes > 0)
                                <span class="text-warning fw-semibold">{{ $att->early_leave_minutes }} min early</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $att->attendance_source === 'biometric' ? 'bg-primary' : ($att->is_corrected ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                {{ ucfirst($att->attendance_source) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No attendance entries recorded for this student in the selected range.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
        </div>
        </div>
</div>
@endsection
