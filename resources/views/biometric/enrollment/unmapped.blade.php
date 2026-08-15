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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-question-circle text-warning me-2"></i>Unmapped Biometric Scans</h3>
            <p class="text-muted mb-0 small">Punches downloaded from M50 terminals with Machine User IDs not yet mapped to student profiles</p>
        </div>
        <a href="{{ route('biometric.enrollment.index') }}" class="btn btn-outline-primary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Enrollment
        </a>
    </div>

    @include('session-messages')

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Machine Terminal</th>
                        <th>Unknown User ID</th>
                        <th>Total Scans</th>
                        <th>First Seen</th>
                        <th>Last Seen</th>
                        <th class="text-end">Assign to Student</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unmappedPunches as $unmapped)
                    <tr>
                        <td>
                            <span class="fw-bold text-dark">{{ $unmapped->device->name ?? 'Device #' . $unmapped->device_id }}</span>
                            <small class="text-muted d-block">{{ $unmapped->device->location ?? '' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-warning text-dark font-monospace fs-6">User #{{ $unmapped->device_user_id }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $unmapped->punch_count }} punches</span>
                        </td>
                        <td class="text-muted small">{{ $unmapped->first_seen }}</td>
                        <td class="text-muted small">{{ $unmapped->last_seen }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#mapModal{{ $unmapped->device_id }}_{{ $unmapped->device_user_id }}">
                                <i class="bi bi-link-45deg me-1"></i> Link to Student
                            </button>
                        </td>
                    </tr>

                    <!-- Map User Modal -->
                    <div class="modal fade" id="mapModal{{ $unmapped->device_id }}_{{ $unmapped->device_user_id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('biometric.enrollment.map-user') }}" class="modal-content text-start">
                                @csrf
                                <input type="hidden" name="device_id" value="{{ $unmapped->device_id }}">
                                <input type="hidden" name="device_user_id" value="{{ $unmapped->device_user_id }}">
                                <div class="modal-header">
                                    <h6 class="modal-title fw-bold">Link Machine User #{{ $unmapped->device_user_id }}</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small">Select the student profile that corresponds to Machine User ID <strong>#{{ $unmapped->device_user_id }}</strong> on <strong>{{ $unmapped->device->name ?? 'this machine' }}</strong>.</p>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Select Student <span class="text-danger">*</span></label>
                                        <select name="student_id" class="form-select" required>
                                            <option value="">-- Choose Student --</option>
                                            @foreach($students as $st)
                                            <option value="{{ $st->id }}">{{ $st->first_name }} {{ $st->last_name }} (ID: #{{ $st->id }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="alert alert-info py-2 small mb-0">
                                        <i class="bi bi-info-circle me-1"></i> All {{ $unmapped->punch_count }} historical punches will automatically be attributed to this student and attendance recalculated.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2-circle me-1"></i> Confirm & Resolve</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-2 text-success d-block mb-2"></i>
                            All biometric punches are perfectly mapped to students!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($unmappedPunches->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $unmappedPunches->links() }}
        </div>
        @endif
    </div>
        </div>
    </div>
</div>
@endsection
