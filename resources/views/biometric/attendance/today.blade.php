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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-calendar-check text-primary me-2"></i>Daily Biometric Attendance Record</h3>
            <p class="text-muted mb-0 small">Processed daily attendance evaluated against configured school timings and leave records</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('biometric.recalculate') }}" class="d-inline">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <button type="submit" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="bi bi-calculator me-1"></i> Recalculate Attendance
                </button>
            </form>
        </div>
    </div>

    @include('session-messages')

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('biometric.today') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Class</label>
                    <select name="class_id" class="form-select form-select-sm">
                        <option value="">-- All Classes --</option>
                        @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- All Statuses --</option>
                        <option value="on" {{ $statusFilter == 'on' ? 'selected' : '' }}>Present (On Time)</option>
                        <option value="late" {{ $statusFilter == 'late' ? 'selected' : '' }}>Late Arrival</option>
                        <option value="off" {{ $statusFilter == 'off' ? 'selected' : '' }}>Absent</option>
                        <option value="on_leave" {{ $statusFilter == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                        <option value="holiday" {{ $statusFilter == 'holiday' ? 'selected' : '' }}>Holiday</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Apply Filter</button>
                    <a href="{{ route('biometric.today') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Student</th>
                        <th>Class & Section</th>
                        <th>First IN (Punch)</th>
                        <th>Last OUT (Punch)</th>
                        <th>Status</th>
                        <th>Late / Early</th>
                        <th>Source</th>
                        @if(Auth::user()->isAdminOrSuperAdmin())
                        <th class="text-end">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 34px; height: 34px;">
                                    {{ substr($att->student->first_name ?? 'S', 0, 1) }}
                                </div>
                                <div>
                                    <span class="fw-bold text-dark">{{ $att->student->first_name ?? '' }} {{ $att->student->last_name ?? '' }}</span>
                                    <small class="text-muted d-block">ID: #{{ $att->student_id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $att->schoolClass->class_name ?? 'Class ' . $att->class_id }}</span>
                            <small class="text-muted d-block">{{ $att->section->section_name ?? '' }}</small>
                        </td>
                        <td>
                            @if($att->in_time)
                                <span class="fw-bold text-success"><i class="bi bi-box-arrow-in-right me-1"></i>{{ Carbon\Carbon::parse($att->in_time)->format('h:i:s A') }}</span>
                            @else
                                <span class="text-muted">--:--</span>
                            @endif
                        </td>
                        <td>
                            @if($att->out_time)
                                <span class="fw-bold text-danger"><i class="bi bi-box-arrow-right me-1"></i>{{ Carbon\Carbon::parse($att->out_time)->format('h:i:s A') }}</span>
                            @else
                                <span class="badge bg-light text-muted border">Missing Checkout</span>
                            @endif
                        </td>
                        <td>
                            @if($att->status === 'on' || $att->status === 'present')
                                @if($att->late_minutes > 0)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Late</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Present</span>
                                @endif
                            @elseif($att->status === 'late')
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Late</span>
                            @elseif($att->status === 'on_leave')
                                <span class="badge bg-info text-dark"><i class="bi bi-envelope-paper me-1"></i>On Leave</span>
                            @elseif($att->status === 'holiday')
                                <span class="badge bg-secondary"><i class="bi bi-sun me-1"></i>Holiday</span>
                            @else
                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Absent</span>
                            @endif
                        </td>
                        <td>
                            @if($att->late_minutes > 0)
                                <span class="badge bg-light text-danger border">+{{ $att->late_minutes }} min late</span>
                            @endif
                            @if($att->early_leave_minutes > 0)
                                <span class="badge bg-light text-warning border">{{ $att->early_leave_minutes }} min early</span>
                            @endif
                            @if($att->late_minutes == 0 && $att->early_leave_minutes == 0)
                                <span class="text-muted small">Normal</span>
                            @endif
                        </td>
                        <td>
                            @if($att->is_corrected)
                                <span class="badge bg-warning text-dark" title="{{ $att->correction_reason }}"><i class="bi bi-pencil-square me-1"></i>Corrected</span>
                            @elseif($att->attendance_source === 'biometric')
                                <span class="badge bg-primary"><i class="bi bi-fingerprint me-1"></i>Biometric</span>
                            @else
                                <span class="badge bg-secondary">Manual</span>
                            @endif
                        </td>
                        @if(Auth::user()->isAdminOrSuperAdmin())
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#correctModal{{ $att->id }}" title="Correct Attendance">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                        @endif
                    </tr>

                    <!-- Manual Correction Modal -->
                    @if(Auth::user()->isAdminOrSuperAdmin())
                    <div class="modal fade" id="correctModal{{ $att->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('biometric.correct') }}" class="modal-content text-start">
                                @csrf
                                <input type="hidden" name="attendance_id" value="{{ $att->id }}">
                                <div class="modal-header">
                                    <h6 class="modal-title fw-bold">Manual Attendance Correction: {{ $att->student->first_name ?? '' }} {{ $att->student->last_name ?? '' }}</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-warning py-2 small mb-3">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Raw biometric punches will be preserved for audit compliance.
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select" required>
                                            <option value="on" {{ in_array($att->status, ['on', 'present']) ? 'selected' : '' }}>Present (On)</option>
                                            <option value="late" {{ $att->status === 'late' ? 'selected' : '' }}>Late</option>
                                            <option value="off" {{ in_array($att->status, ['off', 'absent']) ? 'selected' : '' }}>Absent (Off)</option>
                                            <option value="on_leave" {{ $att->status === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                                            <option value="half_day" {{ $att->status === 'half_day' ? 'selected' : '' }}>Half Day</option>
                                        </select>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Adjusted IN Time</label>
                                            <input type="datetime-local" name="in_time" class="form-control" value="{{ $att->in_time ? Carbon\Carbon::parse($att->in_time)->format('Y-m-d\TH:i') : '' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Adjusted OUT Time</label>
                                            <input type="datetime-local" name="out_time" class="form-control" value="{{ $att->out_time ? Carbon\Carbon::parse($att->out_time)->format('Y-m-d\TH:i') : '' }}">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Reason for Correction <span class="text-danger">*</span></label>
                                        <input type="text" name="correction_reason" class="form-control" placeholder="e.g. Device missed finger scan / Approved principal permission" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Save Correction</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar2-x fs-2 d-block mb-2 text-secondary"></i>
                            No attendance records found for {{ $date }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($attendances->hasPages())
        <div class="card-footer bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">Showing {{ $attendances->firstItem() }} to {{ $attendances->lastItem() }} of {{ $attendances->total() }} records</small>
            <div>
                {{ $attendances->links() }}
            </div>
        </div>
        @endif
    </div>
        </div>
    </div>
</div>
@endsection
