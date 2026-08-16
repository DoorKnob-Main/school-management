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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-journal-text text-primary me-2"></i>Raw Biometric Punch Logs</h3>
            <p class="text-muted mb-0 small">Immutable audit log of all raw scan events received from M50 terminals</p>
        </div>
        <form method="POST" action="{{ route('biometric.devices.sync-all') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-arrow-repeat me-1"></i> Sync from Terminals
            </button>
        </form>
    </div>

    @include('session-messages')

    <!-- Search & Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('biometric.punches') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Terminal Device</label>
                    <select name="device_id" class="form-select form-select-sm">
                        <option value="">-- All Devices --</option>
                        @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ $deviceId == $dev->id ? 'selected' : '' }}>{{ $dev->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Student</label>
                    <select name="student_id" class="form-select form-select-sm">
                        <option value="">-- All Students --</option>
                        @foreach($students as $st)
                        <option value="{{ $st->id }}" {{ $studentId == $st->id ? 'selected' : '' }}>{{ $st->first_name }} {{ $st->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Scan Mode</label>
                    <select name="verify_type" class="form-select form-select-sm">
                        <option value="">-- All Modes --</option>
                        <option value="1" {{ $verifyType == '1' ? 'selected' : '' }}>Fingerprint</option>
                        <option value="3" {{ $verifyType == '3' ? 'selected' : '' }}>RFID Card</option>
                        <option value="4" {{ $verifyType == '4' ? 'selected' : '' }}>Face</option>
                        <option value="2" {{ $verifyType == '2' ? 'selected' : '' }}>Password</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('biometric.punches') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Punch Logs Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Timestamp</th>
                        <th>Student</th>
                        <th>Terminal</th>
                        <th>Machine User ID</th>
                        <th>Verification Type</th>
                        <th>Sensor #</th>
                        <th>Sync Batch</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <span class="fw-bold text-dark font-monospace">{{ $log->punch_time->format('Y-m-d H:i:s') }}</span>
                            <small class="text-muted d-block">{{ $log->punch_time->diffForHumans() }}</small>
                        </td>
                        <td>
                            @if($log->student)
                                <span class="fw-semibold text-dark">{{ $log->student->first_name }} {{ $log->student->last_name }}</span>
                                <small class="text-muted d-block">ID: #{{ $log->student_id }}</small>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-question-circle me-1"></i>Unmapped</span>
                                <small class="text-muted d-block">Machine User #{{ $log->device_user_id }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $log->device->name ?? 'Device #' . $log->device_id }}</span>
                            <small class="text-muted d-block">{{ $log->device->location ?? '' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace fs-6">#{{ $log->device_user_id }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border">
                                @if($log->verify_type == '1')
                                    <i class="bi bi-fingerprint me-1 text-primary"></i>Fingerprint
                                @elseif($log->verify_type == '3')
                                    <i class="bi bi-credit-card-2-front me-1 text-info"></i>RFID Card
                                @elseif($log->verify_type == '4')
                                    <i class="bi bi-person-bounding-box me-1 text-success"></i>Face
                                @elseif($log->verify_type == '2')
                                    <i class="bi bi-key me-1 text-warning"></i>PIN Password
                                @else
                                    <i class="bi bi-shield me-1"></i>{{ $log->verify_type_label }}
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="text-muted small">Sensor {{ $log->sensor_no }}</span>
                        </td>
                        <td>
                            <small class="text-muted font-monospace" style="font-size: 11px;">{{ $log->sync_batch ?: 'DIRECT' }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                            No raw biometric logs match your search criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} logs</small>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
        @endif
    </div>
        </div>
    </div>
</div>
@endsection
