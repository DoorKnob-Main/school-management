@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <!-- Sidebar Menu -->
        @include('layouts.left-menu')

        <!-- Main Content Area -->
        <div class="col-12 col-lg-10 ps-md-4">
            <!-- Header banner -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom">
                <div>
                    <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-fingerprint text-primary me-2"></i>Biometric Attendance Dashboard</h3>
                    <p class="text-muted mb-0 small">Real-time attendance telemetry, multi-device sync, and daily punch processing</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <form method="GET" action="{{ route('biometric.dashboard') }}" class="d-flex align-items-center gap-2">
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
                    </form>
                    @if(Auth::user()->isAdminOrSuperAdmin())
                    <form method="POST" action="{{ route('biometric.devices.sync-all') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary shadow-sm"><i class="bi bi-arrow-repeat me-1"></i> Sync All Devices</button>
                    </form>
                    <form method="POST" action="{{ route('biometric.recalculate') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="submit" class="btn btn-sm btn-outline-secondary shadow-sm" title="Recalculate rules for selected date"><i class="bi bi-calculator me-1"></i> Recalculate</button>
                    </form>
                    @endif
                </div>
            </div>

            @include('session-messages')

            <!-- Metric Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-sm-4 col-lg-2">
                    <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
                        <div class="card-body p-3">
                            <span class="text-muted text-uppercase fw-semibold small d-block mb-1" style="font-size: 11px;">Total Enrolled</span>
                            <h3 class="fw-bold mb-0 text-dark">{{ $attendanceStats['total_students'] ?? 0 }}</h3>
                            <small class="text-muted"><i class="bi bi-people me-1"></i>Students</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                        <div class="card-body p-3">
                            <span class="text-muted text-uppercase fw-semibold small d-block mb-1" style="font-size: 11px;">Present</span>
                            <h3 class="fw-bold mb-0 text-success">{{ $attendanceStats['present'] ?? 0 }}</h3>
                            <small class="text-success"><i class="bi bi-check-circle me-1"></i>On Time</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                        <div class="card-body p-3">
                            <span class="text-muted text-uppercase fw-semibold small d-block mb-1" style="font-size: 11px;">Late Arrivals</span>
                            <h3 class="fw-bold mb-0 text-warning">{{ $attendanceStats['late'] ?? 0 }}</h3>
                            <small class="text-warning"><i class="bi bi-clock-history me-1"></i>Past Cutoff</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <div class="card shadow-sm border-0 border-start border-4 border-danger h-100">
                        <div class="card-body p-3">
                            <span class="text-muted text-uppercase fw-semibold small d-block mb-1" style="font-size: 11px;">Absent</span>
                            <h3 class="fw-bold mb-0 text-danger">{{ $attendanceStats['absent'] ?? 0 }}</h3>
                            <small class="text-danger"><i class="bi bi-x-circle me-1"></i>No Punch</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
                        <div class="card-body p-3">
                            <span class="text-muted text-uppercase fw-semibold small d-block mb-1" style="font-size: 11px;">On Leave</span>
                            <h3 class="fw-bold mb-0 text-info">{{ $attendanceStats['on_leave'] ?? 0 }}</h3>
                            <small class="text-info"><i class="bi bi-envelope-paper me-1"></i>Approved</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <div class="card shadow-sm border-0 border-start border-4 border-secondary h-100">
                        <div class="card-body p-3">
                            <span class="text-muted text-uppercase fw-semibold small d-block mb-1" style="font-size: 11px;">Devices Online</span>
                            <h3 class="fw-bold mb-0 text-dark">{{ $onlineDevices }} / {{ $totalDevices }}</h3>
                            <small class="text-muted"><i class="bi bi-hdd-network me-1"></i>Terminals</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Today's Attendance Overview Table -->
                <div class="col-12 col-xl-7 col-xxl-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-event text-primary me-2"></i>Attendance for {{ Carbon\Carbon::parse($date)->format('M d, Y (l)') }}</h6>
                            <a href="{{ route('biometric.today', ['date' => $date]) }}" class="btn btn-sm btn-outline-primary">View Full List <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase">
                                        <th>Student</th>
                                        <th>Class / Sec</th>
                                        <th>First IN</th>
                                        <th>Last OUT</th>
                                        <th>Status</th>
                                        <th>Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($attendances as $att)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                                                    {{ substr($att->student->first_name ?? 'S', 0, 1) }}
                                                </div>
                                                <div>
                                                    <span class="fw-semibold text-dark">{{ $att->student->first_name ?? '' }} {{ $att->student->last_name ?? '' }}</span>
                                                    <small class="text-muted d-block">ID: #{{ $att->student_id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $att->schoolClass->class_name ?? 'Class ' . $att->class_id }}</span>
                                        </td>
                                        <td>
                                            @if($att->in_time)
                                                <span class="fw-semibold text-dark"><i class="bi bi-box-arrow-in-right text-success me-1"></i>{{ Carbon\Carbon::parse($att->in_time)->format('h:i A') }}</span>
                                            @else
                                                <span class="text-muted">--:--</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($att->out_time)
                                                <span class="fw-semibold text-dark"><i class="bi bi-box-arrow-right text-danger me-1"></i>{{ Carbon\Carbon::parse($att->out_time)->format('h:i A') }}</span>
                                            @else
                                                <span class="badge bg-light text-muted border">Missing</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($att->status === 'on' || $att->status === 'present')
                                                @if($att->late_minutes > 0)
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Late (+{{ $att->late_minutes }}m)</span>
                                                @else
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Present</span>
                                                @endif
                                            @elseif($att->status === 'late')
                                                <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Late (+{{ $att->late_minutes }}m)</span>
                                            @elseif($att->status === 'on_leave')
                                                <span class="badge bg-info text-dark"><i class="bi bi-envelope-paper me-1"></i>On Leave</span>
                                            @elseif($att->status === 'holiday')
                                                <span class="badge bg-secondary"><i class="bi bi-sun me-1"></i>Holiday</span>
                                            @else
                                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Absent</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($att->is_corrected)
                                                <span class="badge bg-warning text-dark" title="{{ $att->correction_reason }}">Corrected</span>
                                            @elseif($att->attendance_source === 'biometric')
                                                <span class="badge bg-primary">Biometric</span>
                                            @else
                                                <span class="badge bg-secondary">Manual</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                            No attendance records processed for this date yet.
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

                <!-- Right Column: Live Devices & Real-time Telemetry -->
                <div class="col-12 col-xl-5 col-xxl-4">
                    <!-- Hardware status -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-hdd-network text-primary me-2"></i>Biometric Machines</h6>
                            <a href="{{ route('biometric.devices.index') }}" class="btn btn-sm btn-link text-decoration-none">Manage</a>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($devices as $dev)
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="d-flex align-items-center">
                                        <span class="badge rounded-circle p-1 me-2 {{ $dev->status === 'online' ? 'bg-success' : 'bg-danger' }}"></span>
                                        <span class="fw-bold text-dark">{{ $dev->name }}</span>
                                    </div>
                                    <small class="text-muted d-block">{{ $dev->ip_address }}:{{ $dev->port }} &bull; {{ $dev->location ?: 'Main Entry' }}</small>
                                    <small class="text-muted d-block" style="font-size: 11px;">Last Sync: {{ $dev->last_sync_at ? $dev->last_sync_at->diffForHumans() : 'Never' }}</small>
                                </div>
                                <form method="POST" action="{{ route('biometric.devices.sync', ['id' => $dev->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Sync Logs Now"><i class="bi bi-arrow-repeat"></i></button>
                                </form>
                            </div>
                            @empty
                            <div class="p-3 text-center text-muted">
                                No biometric devices configured.
                                <a href="{{ route('biometric.devices.index') }}" class="d-block mt-1">Add Device</a>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Recent raw punch telemetry -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-activity text-primary me-2"></i>Recent Punches</h6>
                            <a href="{{ route('biometric.punches') }}" class="btn btn-sm btn-link text-decoration-none">All Logs</a>
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 380px; overflow-y: auto;">
                            @forelse($recentPunches as $punch)
                            <div class="list-group-item py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold text-dark small">{{ $punch->student ? $punch->student->first_name . ' ' . $punch->student->last_name : 'Machine User #' . $punch->device_user_id }}</span>
                                        <small class="text-muted d-block" style="font-size: 11px;">{{ $punch->device->name ?? 'Device' }} &bull; {{ $punch->verify_type_label }}</small>
                                    </div>
                                    <span class="badge bg-light text-dark border">{{ Carbon\Carbon::parse($punch->punch_time)->format('h:i:s A') }}</span>
                                </div>
                            </div>
                            @empty
                            <div class="p-3 text-center text-muted small">No punch events logged today.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
