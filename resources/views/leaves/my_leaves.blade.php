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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>My Leave Applications</h3>
            <p class="text-muted mb-0 small">Submit and track your academic leave requests</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#applyStudentLeaveModal">
            <i class="bi bi-plus-lg me-1"></i> Apply for Leave
        </button>
    </div>

    @include('session-messages')

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Leave Type</th>
                        <th>Period</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $leave)
                    <tr>
                        <td>
                            <span class="badge bg-light text-primary border">{{ $leave->leaveType->name ?? 'General' }}</span>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">{{ $leave->start_date->format('M d, Y') }}</span>
                            <span class="text-muted">to</span>
                            <span class="fw-bold text-dark">{{ $leave->end_date->format('M d, Y') }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $leave->start_date->diffInDays($leave->end_date) + 1 }} day(s)</span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $leave->reason ?: 'None specified' }}</small>
                            @if($leave->status === 'rejected' && $leave->rejection_reason)
                                <small class="text-danger d-block"><strong>Reason:</strong> {{ $leave->rejection_reason }}</small>
                            @endif
                        </td>
                        <td>
                            @if($leave->status === 'approved')
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
                            @elseif($leave->status === 'pending')
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Review</span>
                            @else
                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $leave->approver ? $leave->approver->first_name . ' ' . $leave->approver->last_name : 'Staff / Admin' }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                            You have not submitted any leave applications yet.
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

<!-- Apply Modal for Student -->
<div class="modal fade" id="applyStudentLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('leaves.apply') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-envelope-plus text-primary me-2"></i>Apply for Leave</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
                    <label class="form-label small fw-semibold">Reason for Absence <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Provide reason for your leave request..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i> Submit Request</button>
            </div>
        </form>
    </div>
        </div>
    </div>
</div>
@endsection
