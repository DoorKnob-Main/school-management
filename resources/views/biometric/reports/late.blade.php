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
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-clock-history text-warning me-2"></i>Late Arrival Telemetry Report</h3>
            <p class="text-muted mb-0 small">Audit list of students who scanned past the morning arrival cutoff</p>
        </div>
        <a href="{{ route('biometric.reports.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Reports Hub
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('biometric.reports.late') }}" class="row g-2 align-items-center">
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
                    <label class="form-label small text-muted mb-1">From Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">To Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Filter Late Logs</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Late Arrivals Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>First Punch IN</th>
                        <th>Late Minutes</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                    <tr>
                        <td class="fw-semibold text-dark">{{ Carbon\Carbon::parse($rec->created_at)->format('Y-m-d (D)') }}</td>
                        <td>
                            <span class="fw-bold text-dark">{{ $rec->student->first_name ?? '' }} {{ $rec->student->last_name ?? '' }}</span>
                            <small class="text-muted d-block">ID: #{{ $rec->student_id }}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $rec->schoolClass->class_name ?? 'Class ' . $rec->class_id }}</span></td>
                        <td>
                            <span class="fw-bold text-danger"><i class="bi bi-clock me-1"></i>{{ Carbon\Carbon::parse($rec->in_time)->format('h:i:s A') }}</span>
                        </td>
                        <td>
                            <span class="badge bg-danger text-white fs-6 font-monospace">+{{ $rec->late_minutes }} min</span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $rec->remarks ?: 'Scanned past morning cutoff' }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-2 text-success d-block mb-2"></i>
                            No late arrival records in the selected date range.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $records->links() }}
        </div>
        @endif
    </div>
        </div>
    </div>
</div>
@endsection
