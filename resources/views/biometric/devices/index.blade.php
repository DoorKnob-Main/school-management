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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-hdd-network text-primary me-2"></i>Biometric Device Management</h3>
            <p class="text-muted mb-0 small">Configure and monitor M50 / Realtime biometric terminals (TCP/IP Port 5005)</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                <i class="bi bi-plus-lg me-1"></i> Add Device
            </button>
            <form method="POST" action="{{ route('biometric.devices.sync-all') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm shadow-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync All
                </button>
            </form>
        </div>
    </div>

    @include('session-messages')

    <!-- Devices Grid -->
    <div class="row g-3 mb-4">
        @forelse($devices as $device)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100 position-relative">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge {{ $device->status === 'online' ? 'bg-success' : ($device->status === 'error' ? 'bg-warning text-dark' : 'bg-danger') }} mb-2">
                                <i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>{{ ucfirst($device->status) }}
                            </span>
                            <h5 class="fw-bold text-dark mb-1">{{ $device->name }}</h5>
                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $device->location ?: 'Not specified' }}</small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <button class="dropdown-item text-success fw-bold" onclick="openDeviceUsersModal({{ $device->id }}, '{{ addslashes($device->name) }}')">
                                        <i class="bi bi-people-fill me-2"></i> Manage Hardware Users
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item text-primary" onclick="testDevice({{ $device->id }})">
                                        <i class="bi bi-broadcast me-2"></i> Test Ping & Handshake
                                    </button>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('biometric.devices.sync-time', ['id' => $device->id]) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item"><i class="bi bi-clock me-2"></i> Sync Device Time</button>
                                    </form>
                                </li>
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#unlockModal{{ $device->id }}">
                                        <i class="bi bi-door-open me-2"></i> Trigger Door Unlock
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item text-info" data-bs-toggle="modal" data-bs-target="#simModal{{ $device->id }}">
                                        <i class="bi bi-play-circle me-2"></i> Simulate Punch Test
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#editDeviceModal{{ $device->id }}">
                                        <i class="bi bi-pencil me-2"></i> Edit Device
                                    </button>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('biometric.devices.destroy', ['id' => $device->id]) }}" onsubmit="return confirm('Delete this biometric device configuration?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Delete Device</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">IP Address:</span>
                            <span class="fw-semibold text-dark font-monospace">{{ $device->ip_address }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Port / Machine ID:</span>
                            <span class="fw-semibold text-dark">{{ $device->port }} / Machine #{{ $device->machine_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Enrolled Users:</span>
                            <span class="fw-semibold text-dark">{{ $device->user_mappings_count }} students</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Punches Logged:</span>
                            <span class="fw-semibold text-dark">{{ $device->punch_logs_count }} events</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <small class="text-muted" style="font-size: 11px;">
                            Last Sync: {{ $device->last_sync_at ? $device->last_sync_at->diffForHumans() : 'Never' }}
                        </small>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="openDeviceUsersModal({{ $device->id }}, '{{ addslashes($device->name) }}')">
                                <i class="bi bi-people me-1"></i> Users
                            </button>
                            <form method="POST" action="{{ route('biometric.devices.sync', ['id' => $device->id]) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-repeat me-1"></i> Sync
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Door Unlock Modal -->
        <div class="modal fade" id="unlockModal{{ $device->id }}" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <form method="POST" action="{{ route('biometric.devices.unlock', ['id' => $device->id]) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold">Unlock Door: {{ $device->name }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">Hold Open Duration (Seconds)</label>
                        <input type="number" name="delay_seconds" class="form-control" value="5" min="1" max="60">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-unlock me-1"></i> Send Unlock Pulse</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Simulation Test Modal -->
        <div class="modal fade" id="simModal{{ $device->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('biometric.devices.simulate', ['id' => $device->id]) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold"><i class="bi bi-play-circle text-primary me-2"></i>Simulate Punch on {{ $device->name }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Inject a test biometric scan event for testing attendance calculations without hardware.</p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Machine User ID / Student ID</label>
                            <input type="number" name="user_id" class="form-control" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Punch Timestamp</label>
                            <input type="datetime-local" name="timestamp" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Verification Mode</label>
                            <select name="verify_type" class="form-select">
                                <option value="1">Fingerprint (1)</option>
                                <option value="3">RFID Card (3)</option>
                                <option value="4">Face Recognition (4)</option>
                                <option value="2">PIN Password (2)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i> Inject & Recalculate</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Device Modal -->
        <div class="modal fade" id="editDeviceModal{{ $device->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('biometric.devices.update', ['id' => $device->id]) }}" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold">Edit Biometric Device</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Device Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $device->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Device Identifier (Unique Code)</label>
                            <input type="text" name="device_identifier" class="form-control" value="{{ $device->device_identifier }}" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label small fw-semibold">IP Address</label>
                                <input type="text" name="ip_address" class="form-control" value="{{ $device->ip_address }}" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Port</label>
                                <input type="number" name="port" class="form-control" value="{{ $device->port }}" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Machine Number</label>
                                <input type="number" name="machine_number" class="form-control" value="{{ $device->machine_number }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Comm Password</label>
                                <input type="password" name="communication_password" class="form-control" placeholder="Leave blank to keep current">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Physical Location</label>
                            <input type="text" name="location" class="form-control" value="{{ $device->location }}">
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeSwitch{{ $device->id }}" {{ $device->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small" for="activeSwitch{{ $device->id }}">Device Enabled for Sync</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card shadow-sm border-0 py-5 text-center">
                <i class="bi bi-hdd-network fs-1 text-muted mb-2"></i>
                <h5 class="fw-bold text-dark">No Biometric Devices Configured</h5>
                <p class="text-muted small mb-3">Add your first M50 biometric machine to start syncing automated attendance punches.</p>
                <div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                        <i class="bi bi-plus-lg me-1"></i> Add New Device
                    </button>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Audit Logs table -->
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Recent Biometric Audit Activity</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $audit)
                    <tr>
                        <td class="text-muted">{{ $audit->created_at->format('Y-m-d H:i:s') }}</td>
                        <td><span class="fw-semibold">{{ $audit->user ? $audit->user->first_name . ' ' . $audit->user->last_name : 'System' }}</span></td>
                        <td><span class="badge bg-light text-dark border">{{ $audit->action }}</span></td>
                        <td class="font-monospace text-muted small">{{ json_encode($audit->details) }}</td>
                        <td class="text-muted">{{ $audit->ip_address }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted">No audit logs recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Device Modal -->
<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('biometric.devices.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Add New Biometric Device</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Device Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Main Gate Terminal" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Device Identifier (Unique Code) <span class="text-danger">*</span></label>
                    <input type="text" name="device_identifier" class="form-control" placeholder="e.g. M50_MAIN_GATE" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label small fw-semibold">IP Address <span class="text-danger">*</span></label>
                        <input type="text" name="ip_address" class="form-control" placeholder="192.168.1.201" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Port <span class="text-danger">*</span></label>
                        <input type="number" name="port" class="form-control" value="5005" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Machine Number</label>
                        <input type="number" name="machine_number" class="form-control" value="1" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Comm Password</label>
                        <input type="text" name="communication_password" class="form-control" value="0" placeholder="Default: 0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Physical Location</label>
                    <input type="text" name="location" class="form-control" placeholder="e.g. Main Gate / Reception / Senior Block">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newActiveSwitch" checked>
                    <label class="form-check-label small" for="newActiveSwitch">Activate Device Immediately</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Register Device</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Hardware Users Modal -->
<div class="modal fade" id="deviceUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="deviceUsersModalTitle"><i class="bi bi-cpu text-primary me-2"></i>Users Stored in Device Machine</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="deviceUsersLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">Connecting to biometric hardware device... Fetching enrolled users list.</p>
                </div>

                <div id="deviceUsersContent" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="badge bg-light text-dark border" id="deviceUsersCountBadge">0 Users Enrolled</span>
                            <span class="badge bg-success ms-1" id="deviceHardwareStatusBadge">Online</span>
                        </div>
                        <a href="{{ route('biometric.enrollment.index') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-person-plus me-1"></i> Enroll Student
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Machine ID</th>
                                    <th>Display Name</th>
                                    <th>Card / Password</th>
                                    <th>Privilege</th>
                                    <th>Mapped Student</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="deviceUsersTableBody">
                                <!-- Populated dynamically via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function testDevice(deviceId) {
    alert('Connecting to device on Port 5005... Please wait.');
    fetch(`/biometric/devices/test/${deviceId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('SUCCESS: ' + data.message);
            location.reload();
        } else {
            alert('TEST RESULT: ' + data.message);
        }
    })
    .catch(err => alert('Network error: ' + err.message));
}

let currentOpenDeviceId = null;

function openDeviceUsersModal(deviceId, deviceName) {
    currentOpenDeviceId = deviceId;
    document.getElementById('deviceUsersModalTitle').innerHTML = `<i class="bi bi-cpu text-primary me-2"></i>Hardware Users on ${deviceName}`;
    document.getElementById('deviceUsersLoading').classList.remove('d-none');
    document.getElementById('deviceUsersContent').classList.add('d-none');
    
    var bsModal = new bootstrap.Modal(document.getElementById('deviceUsersModal'));
    bsModal.show();

    fetch(`/biometric/devices/users/${deviceId}`, {
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('deviceUsersLoading').classList.add('d-none');
        document.getElementById('deviceUsersContent').classList.remove('d-none');

        document.getElementById('deviceUsersCountBadge').innerText = `${data.count || 0} Users Enrolled`;
        const statusBadge = document.getElementById('deviceHardwareStatusBadge');
        if (data.hardware_status === 'online') {
            statusBadge.className = 'badge bg-success ms-1';
            statusBadge.innerText = 'Device Online';
        } else {
            statusBadge.className = 'badge bg-warning text-dark ms-1';
            statusBadge.innerText = 'Local DB Mappings (Hardware Offline)';
        }

        const tbody = document.getElementById('deviceUsersTableBody');
        tbody.innerHTML = '';

        if (!data.users || data.users.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No enrolled users found on this biometric device.</td></tr>`;
            return;
        }

        data.users.forEach(user => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold text-primary">#${user.device_user_id}</td>
                <td><span class="fw-semibold text-dark">${user.name || 'Machine User'}</span></td>
                <td><span class="text-muted font-monospace">${user.card_no ? 'Card: ' + user.card_no : 'N/A'}</span></td>
                <td><span class="badge bg-light text-dark border">${user.privilege == 1 ? 'Admin' : 'Normal User'}</span></td>
                <td>
                    ${user.is_mapped ? 
                        `<span class="badge bg-info text-dark"><i class="bi bi-person-check me-1"></i>${user.student_name}</span>` : 
                        `<span class="badge bg-light text-muted border">Unmapped</span>`
                    }
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 12px;" onclick="deleteUserFromDevice(${deviceId}, ${user.device_user_id}, '${(user.name || 'User #' + user.device_user_id).replace(/'/g, "\\'")}')">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(err => {
        document.getElementById('deviceUsersLoading').classList.add('d-none');
        document.getElementById('deviceUsersContent').classList.remove('d-none');
        document.getElementById('deviceUsersTableBody').innerHTML = `<tr><td colspan="6" class="text-center py-3 text-danger">Error loading users: ${err.message}</td></tr>`;
    });
}

function deleteUserFromDevice(deviceId, deviceUserId, userName) {
    if (!confirm(`Are you sure you want to delete user "${userName}" (Machine ID #${deviceUserId}) from the biometric hardware device and remove mapping?`)) {
        return;
    }

    fetch(`/biometric/devices/delete-user/${deviceId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ device_user_id: deviceUserId })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            openDeviceUsersModal(deviceId, 'Biometric Machine');
        }
    })
    .catch(err => alert('Network error: ' + err.message));
}
</script>
        </div>
    </div>
</div>
@endsection
