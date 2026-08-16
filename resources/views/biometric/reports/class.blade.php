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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-diagram-3 text-primary me-2"></i>Class Attendance Register</h3>
            <p class="text-muted mb-0 small">Class and Section daily attendance sheet with biometric punch telemetry</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('biometric.reports.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Reports Hub
            </a>
            @if($classId)
            <a href="{{ route('biometric.reports.class', array_merge(request()->all(), ['export' => 'pdf'])) }}" target="_blank" class="btn btn-danger btn-sm shadow-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </a>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('biometric.reports.class') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">Select Class <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select form-select-sm" required>
                        <option value="">-- Choose Class --</option>
                        @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Generate Sheet</button>
                </div>
            </form>
        </div>
    </div>

    @if($classId)
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Student Name</th>
                        <th>Roll / ID</th>
                        <th>First IN</th>
                        <th>Last OUT</th>
                        <th>Status</th>
                        <th>Late By</th>
                        <th>Early Leave</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                    <tr>
                        <td class="fw-bold text-dark">{{ $att->student->first_name ?? '' }} {{ $att->student->last_name ?? '' }}</td>
                        <td><span class="badge bg-light text-dark border">#{{ $att->student_id }}</span></td>
                        <td>
                            @if($att->in_time)
                                <span class="fw-semibold text-success"><i class="bi bi-box-arrow-in-right me-1"></i>{{ Carbon\Carbon::parse($att->in_time)->format('h:i A') }}</span>
                            @else
                                <span class="text-muted">--:--</span>
                            @endif
                        </td>
                        <td>
                            @if($att->out_time)
                                <span class="fw-semibold text-danger"><i class="bi bi-box-arrow-right me-1"></i>{{ Carbon\Carbon::parse($att->out_time)->format('h:i A') }}</span>
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
                                <span class="text-danger fw-semibold">+{{ $att->late_minutes }}m</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($att->early_leave_minutes > 0)
                                <span class="text-warning fw-semibold">{{ $att->early_leave_minutes }}m early</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $att->attendance_source === 'biometric' ? 'bg-primary' : 'bg-secondary' }}">
                                {{ ucfirst($att->attendance_source) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No attendance logs recorded for this class on {{ $date }}.</td>
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
