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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-person-badge text-primary me-2"></i>Student Biometric Enrollment</h3>
            <p class="text-muted mb-0 small">Map school student profiles to hardware Machine User IDs on M50 biometric terminals</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('biometric.enrollment.unmapped') }}" class="btn btn-outline-warning btn-sm shadow-sm">
                <i class="bi bi-question-circle me-1"></i> View Unmapped Punches
            </a>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#enrollModal">
                <i class="bi bi-person-plus me-1"></i> Enroll Student to Device
            </button>
        </div>
    </div>

    @include('session-messages')

    <!-- Filter by Device -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('biometric.enrollment.index') }}" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="small fw-semibold text-muted">Select Machine:</label>
                </div>
                <div class="col-12 col-sm-4 col-md-3">
                    <select name="device_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Active Devices --</option>
                        @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ $selectedDeviceId == $dev->id ? 'selected' : '' }}>{{ $dev->name }} ({{ $dev->ip_address }})</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Enrolled Students Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Student Details</th>
                        <th>Device / Location</th>
                        <th>Machine User ID</th>
                        <th>Status</th>
                        <th>Enrolled Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $map)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 34px; height: 34px;">
                                    {{ substr($map->student->first_name ?? 'S', 0, 1) }}
                                </div>
                                <div>
                                    <span class="fw-bold text-dark">{{ $map->student->first_name ?? '' }} {{ $map->student->last_name ?? '' }}</span>
                                    <small class="text-muted d-block">Software ID: #{{ $map->student_id }} &bull; {{ $map->student->email ?? '' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $map->device->name ?? 'Unknown Device' }}</span>
                            <small class="text-muted d-block">{{ $map->device->location ?: 'Main Entry' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border font-monospace fs-6">#{{ $map->device_user_id }}</span>
                        </td>
                        <td>
                            @if($map->enrollment_status === 'enrolled')
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Enrolled</span>
                            @elseif($map->enrollment_status === 'pending')
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($map->enrollment_status) }}</span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $map->enrolled_at ? $map->enrolled_at->format('Y-m-d H:i') : $map->created_at->format('Y-m-d') }}
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('biometric.enrollment.unenroll', ['id' => $map->id]) }}" onsubmit="return confirm('Remove student mapping from this device?');" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Unenroll from Device">
                                    <i class="bi bi-person-dash"></i> Unenroll
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-person-badge fs-2 d-block mb-2 text-secondary"></i>
                            No students enrolled on this biometric terminal yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($mappings->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $mappings->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Enroll Student Modal -->
<div class="modal fade" id="enrollModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('biometric.enrollment.enroll') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Enroll Student to Machine</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Target Biometric Machine <span class="text-danger">*</span></label>
                    <select name="device_id" class="form-select" required>
                        @foreach($devices as $dev)
                        <option value="{{ $dev->id }}">{{ $dev->name }} ({{ $dev->ip_address }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Select Student <span class="text-danger">*</span></label>
                    <select name="student_id" id="studentSelect" class="form-select" onchange="autoFillUserId(this.value)" required>
                        <option value="">-- Choose Student --</option>
                        @foreach($students as $st)
                        <option value="{{ $st->id }}" data-id="{{ $st->id }}">{{ $st->first_name }} {{ $st->last_name }} (ID: #{{ $st->id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Machine User ID (Enrollment Number) <span class="text-danger">*</span></label>
                    <input type="number" name="device_user_id" id="deviceUserIdInput" class="form-control" placeholder="Unique numeric Machine ID (e.g. 101)" required>
                    <small class="text-muted">Unique numeric identifier stored inside the biometric hardware.</small>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">RFID / Smart Card No</label>
                        <input type="number" name="card_number" class="form-control" placeholder="e.g. 10928374">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Device PIN Password</label>
                        <input type="number" name="user_password" class="form-control" value="1234">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Privilege Level</label>
                    <select name="privilege" class="form-select">
                        <option value="0">Normal Student (0)</option>
                        <option value="1">Administrator (1)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload me-1"></i> Push to Machine</button>
            </div>
        </form>
    </div>
</div>

<script>
function autoFillUserId(studentId) {
    if (studentId) {
        document.getElementById('deviceUserIdInput').value = studentId;
    }
}
</script>
        </div>
    </div>
</div>
@endsection
