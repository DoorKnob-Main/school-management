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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-sliders text-primary me-2"></i>Biometric Attendance & Timing Rules</h3>
            <p class="text-muted mb-0 small">Configure school operating hours, present windows, late arrival limits, and automated absent rules</p>
        </div>
    </div>

    @include('session-messages')

    <form method="POST" action="{{ route('biometric.settings.update') }}">
        @csrf
        
        <div class="row g-4">
            <!-- Left Column: Timings & Windows -->
            <div class="col-12 col-lg-8">
                <!-- Super Admin Feature Toggle Card -->
                @if(Auth::user()->isSuperAdmin())
                <div class="card shadow-sm border-0 mb-4 border-start border-4 border-primary">
                    <div class="card-body p-4">
                        <input type="hidden" name="biometric_attendance_enabled_submitted" value="1">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>Super Admin Biometric Entitlement Flag</h6>
                                <p class="text-muted small mb-0">Enable or disable biometric attendance functionality system-wide across all school operations.</p>
                            </div>
                            <div class="form-check form-switch fs-4">
                                <input class="form-check-input" type="checkbox" name="biometric_attendance_enabled" value="1" id="biometricEnabledSwitch" {{ ($timing['biometric_attendance_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Core School Timings -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock text-primary me-2"></i>Official School Hours</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold">School Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="school_start_time" class="form-control" value="{{ $timing['school_start_time'] ?? '08:30' }}" required>
                                <small class="text-muted">Standard daily morning bell time.</small>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold">School Leave / End Time <span class="text-danger">*</span></label>
                                <input type="time" name="school_end_time" class="form-control" value="{{ $timing['school_end_time'] ?? '14:30' }}" required>
                                <small class="text-muted">Standard school dispersal bell time.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Present & Late Windows -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-hourglass-split text-primary me-2"></i>Arrival & Status Calculation Windows</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-success">Present Window Start</label>
                                <input type="time" name="present_window_start" class="form-control" value="{{ $timing['present_window_start'] ?? '08:00' }}" required>
                                <small class="text-muted">Early arrival check-in opening time.</small>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-success">Present Window Cutoff (Marked Present)</label>
                                <input type="time" name="present_window_end" class="form-control" value="{{ $timing['present_window_end'] ?? '08:45' }}" required>
                                <small class="text-muted">Punches before this time are recorded as Present.</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-warning">Late Period Start</label>
                                <input type="time" name="late_window_start" class="form-control" value="{{ $timing['late_window_start'] ?? '08:46' }}" required>
                                <small class="text-muted">Late punch window start.</small>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-warning">Late Period Cutoff</label>
                                <input type="time" name="late_window_end" class="form-control" value="{{ $timing['late_window_end'] ?? '09:15' }}" required>
                                <small class="text-muted">Students scanning after cutoff are marked absent.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Departure & Early Leave Thresholds -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-right text-primary me-2"></i>Departure & Dispersal Rules</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold">Dispersal Leave Time</label>
                                <input type="time" name="school_leave_time" class="form-control" value="{{ $timing['school_leave_time'] ?? '14:30' }}" required>
                                <small class="text-muted">Expected student OUT scan time.</small>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold">Early Departure Threshold</label>
                                <input type="time" name="early_leave_threshold" class="form-control" value="{{ $timing['early_leave_threshold'] ?? '14:15' }}" required>
                                <small class="text-muted">Scans before this time are flagged as Early Leave.</small>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-semibold text-danger">Automatic Absent Cutoff</label>
                                <input type="time" name="auto_absent_after" class="form-control" value="{{ $timing['auto_absent_after'] ?? '09:15' }}" required>
                                <small class="text-muted">Students with no punch by this time are marked Absent.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Punch Strategy & Working Days -->
            <div class="col-12 col-lg-4">
                <!-- Punch Processing Strategy -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Punch Aggregation Rules</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="use_first_punch_as_in" id="firstInSwitch" {{ ($timing['use_first_punch_as_in'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="firstInSwitch">First Valid Punch = IN Time</label>
                            <small class="text-muted d-block">The earliest valid scan of the day defines student arrival.</small>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="use_last_punch_as_out" id="lastOutSwitch" {{ ($timing['use_last_punch_as_out'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="lastOutSwitch">Last Valid Punch = OUT Time</label>
                            <small class="text-muted d-block">The latest valid scan of the day defines student departure.</small>
                        </div>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="bi bi-info-circle me-1"></i> Intermediate scans are preserved in the raw biometric log repository for audit tracking.
                        </div>
                    </div>
                </div>

                <!-- Working Days Selector -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar3 text-primary me-2"></i>School Working Days</h6>
                    </div>
                    <div class="card-body p-4">
                        @php
                            $workingDays = json_decode($timing['working_days'] ?? '["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"]', true) ?: ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
                            $allDays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                        @endphp
                        @foreach($allDays as $day)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $day }}" id="day_{{ $day }}" {{ in_array($day, $workingDays) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="day_{{ $day }}">{{ $day }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Save Attendance Rules
                    </button>
                </div>
            </div>
        </div>
    </form>
        </div>
    </div>
</div>
@endsection
