@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <!-- Sidebar Menu -->
        @include('layouts.left-menu')

        <!-- Main Content Area -->
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10 ps-md-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-envelope-paper text-primary me-2"></i>Student Leave Applications</h3>
            <p class="text-muted mb-0 small">Review, approve, and track student leave requests with automatic attendance integration</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('leaves.types') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-tags me-1"></i> Leave Types
            </a>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
                <i class="bi bi-plus-lg me-1"></i> Apply Leave
            </button>
        </div>
    </div>

    @include('session-messages')

    <!-- Status Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('leaves.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-sm-4 col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>-- All Statuses --</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-md-4">
                    <label class="form-label small text-muted mb-1">Student</label>
                    <select name="student_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Students --</option>
                        @foreach($students as $st)
                        <option value="{{ $st->id }}" {{ $studentId == $st->id ? 'selected' : '' }}>{{ $st->first_name }} {{ $st->last_name }} (ID: #{{ $st->id }})</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Leaves Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Student</th>
                        <th>Leave Type</th>
                        <th>Period</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $leave)
                    <tr>
                        <td>
                            <span class="fw-bold text-dark">{{ $leave->student->first_name ?? '' }} {{ $leave->student->last_name ?? '' }}</span>
                            <small class="text-muted d-block">ID: #{{ $leave->student_id }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border">{{ $leave->leaveType->name ?? 'General' }}</span>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $leave->start_date->format('M d, Y') }}</span>
                            <span class="text-muted">to</span>
                            <span class="fw-semibold text-dark">{{ $leave->end_date->format('M d, Y') }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $leave->start_date->diffInDays($leave->end_date) + 1 }} day(s)</span>
                        </td>
                        <td>
                            <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;">{{ $leave->reason ?: 'None specified' }}</small>
                        </td>
                        <td>
                            @if($leave->status === 'approved')
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
                            @elseif($leave->status === 'pending')
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                            @else
                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($leave->status === 'pending')
                            <form method="POST" action="{{ route('leaves.approve', ['id' => $leave->id]) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Approve Leave">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}" title="Reject Leave">
                                <i class="bi bi-x-lg"></i>
                            </button>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('leaves.reject', ['id' => $leave->id]) }}" class="modal-content text-start">
                                        @csrf
                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold">Reject Leave Application</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label small fw-semibold">Rejection Reason</label>
                                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Explain why this leave request is rejected..."></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @else
                            <small class="text-muted">By: {{ $leave->approver ? $leave->approver->first_name : 'Admin' }}</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-envelope-open fs-2 d-block mb-2 text-secondary"></i>
                            No leave applications found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leaves->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $leaves->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('leaves.apply') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-envelope-plus text-primary me-2"></i>Apply Student Leave</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Select Student <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Choose Student --</option>
                        @foreach($students as $st)
                        <option value="{{ $st->id }}">{{ $st->first_name }} {{ $st->last_name }} (ID: #{{ $st->id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Leave Category <span class="text-danger">*</span></label>
                    <select name="leave_type_id" class="form-select" required>
                        @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason / Description</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Provide reason for student leave application..."></textarea>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="auto_approve" id="autoApproveCheck" checked>
                    <label class="form-check-label small" for="autoApproveCheck">Auto-approve as Administrator</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Submit Application</button>
            </div>
        </form>
    </div>
        </div>
    </div>
</div>
@endsection
