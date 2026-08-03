@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-start">
        @include('layouts.left-menu')
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10">
            <div class="row pt-2">
                <div class="col ps-4">
                    @include('session-messages')

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h1 class="display-6 mb-1"><i class="bi bi-bell"></i> Fee Reminders Dispatcher</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                                    <li class="breadcrumb-item">Payment</li>
                                    <li class="breadcrumb-item active">Fee Reminder</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <button type="button" class="btn btn-warning btn-lg text-dark fw-bold" id="btnOpenReminderModal" disabled data-bs-toggle="modal" data-bs-target="#sendReminderModal">
                                <i class="bi bi-send-check"></i> Send Reminders (<span id="selectedCount">0</span> Selected)
                            </button>
                        </div>
                    </div>

                    <!-- Filter Card -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="{{route('finance.fee-reminder.index')}}" class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Class</label>
                                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                                        <option value="0">All Classes</option>
                                        @foreach($classes as $cls)
                                            <option value="{{$cls->id}}" {{$selected_class_id == $cls->id ? 'selected' : ''}}>{{$cls->class_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Section</label>
                                    <select name="section_id" class="form-select" onchange="this.form.submit()">
                                        <option value="0">All Sections</option>
                                        @foreach($sections as $sec)
                                            <option value="{{$sec->id}}" {{$selected_section_id == $sec->id ? 'selected' : ''}}>{{$sec->section_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter Pending Dues</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Pending Students Table -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 text-warning text-dark"><i class="bi bi-exclamation-circle"></i> Students with Pending Dues ({{count($pendingStudents)}} Found)</h5>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectAll">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">Deselect All</button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><input type="checkbox" id="selectAllCheckbox"></th>
                                            <th>Student Name</th>
                                            <th>Class & Sec</th>
                                            <th>Father Name</th>
                                            <th>Father Phone</th>
                                            <th>Total Fee</th>
                                            <th>Paid</th>
                                            <th>Remaining Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pendingStudents as $st)
                                            <tr>
                                                <td><input type="checkbox" class="student-select-cb" value="{{$st['student']->id}}"></td>
                                                <td class="fw-bold">{{$st['student']->first_name}} {{$st['student']->last_name}}</td>
                                                <td>{{$st['school_class']->class_name ?? ''}} - {{$st['section']->section_name ?? ''}}</td>
                                                <td>{{$st['father_name']}}</td>
                                                <td><span class="badge bg-light text-dark border">{{$st['father_phone']}}</span></td>
                                                <td>₹{{number_format($st['total_fee'], 2)}}</td>
                                                <td class="text-success">₹{{number_format($st['paid_amount'], 2)}}</td>
                                                <td class="text-danger fw-bold fs-6">₹{{number_format($st['due_amount'], 2)}}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">No pending fee dues found for the selected filter.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Past Reminders History Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0 text-primary"><i class="bi bi-clock-history"></i> Reminder Dispatch Logs</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Channel</th>
                                            <th>Message Template Preview</th>
                                            <th>Recipients Count</th>
                                            <th>Dispatched By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reminderHistory as $log)
                                            <tr>
                                                <td>{{$log->created_at->format('Y-m-d H:i')}}</td>
                                                <td><span class="badge bg-info text-dark">{{$log->channel}}</span></td>
                                                <td><small class="text-muted">{{Str::limit($log->message_template, 60)}}</small></td>
                                                <td><span class="badge bg-success">{{$log->recipients->count()}} Parents</span></td>
                                                <td><small>{{$log->creator->first_name ?? 'Admin'}}</small></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-3 text-muted">No fee reminders dispatched yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>

<!-- Send Reminder Modal -->
<div class="modal fade" id="sendReminderModal" tabindex="-1" aria-labelledby="sendReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{route('finance.fee-reminder.send')}}" id="sendReminderForm">
                @csrf
                <input type="hidden" name="session_id" value="{{$current_school_session_id}}">
                <div id="hiddenStudentIdsContainer"></div>

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="sendReminderModalLabel"><i class="bi bi-send"></i> Send Fee Reminders (<span id="modalSelectedCount">0</span> Parents)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notification Channel <span class="text-danger">*</span></label>
                        <select name="channel" class="form-select form-select-lg" required>
                            <option value="SMS">SMS</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Both">Both (SMS & WhatsApp)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Message Template <span class="text-danger">*</span></label>
                        <textarea name="message_template" id="message_template_input" class="form-control" rows="5" required>{{$defaultTemplate}}</textarea>
                    </div>

                    <!-- Placeholder Tags Helper Chips -->
                    <div class="mb-3">
                        <small class="fw-bold d-block text-muted mb-1"><i class="bi bi-tags"></i> Click to insert dynamic placeholders:</small>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary placeholder-tag" data-tag="{student_name}">{student_name}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary placeholder-tag" data-tag="{father_name}">{father_name}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary placeholder-tag" data-tag="{mother_name}">{mother_name}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary placeholder-tag" data-tag="{class_name}">{class_name}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary placeholder-tag" data-tag="{section_name}">{section_name}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary placeholder-tag" data-tag="{due_amount}">{due_amount}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary placeholder-tag" data-tag="{school_name}">{school_name}</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary placeholder-tag" data-tag="{due_date}">{due_date}</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg"><i class="bi bi-send-check"></i> Dispatch Reminders</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var cbs = document.querySelectorAll('.student-select-cb');
    var openBtn = document.getElementById('btnOpenReminderModal');
    var selectedCountSpan = document.getElementById('selectedCount');
    var modalSelectedCountSpan = document.getElementById('modalSelectedCount');
    var selectAllCb = document.getElementById('selectAllCheckbox');

    function updateSelection() {
        var selected = [];
        cbs.forEach(function(cb) {
            if (cb.checked) {
                selected.push(cb.value);
            }
        });

        selectedCountSpan.textContent = selected.length;
        modalSelectedCountSpan.textContent = selected.length;
        openBtn.disabled = selected.length === 0;

        // Sync hidden inputs inside form
        var hiddenContainer = document.getElementById('hiddenStudentIdsContainer');
        hiddenContainer.innerHTML = '';
        selected.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = id;
            hiddenContainer.appendChild(input);
        });
    }

    cbs.forEach(function(cb) {
        cb.addEventListener('change', updateSelection);
    });

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            cbs.forEach(function(cb) { cb.checked = selectAllCb.checked; });
            updateSelection();
        });
    }

    document.getElementById('btnSelectAll').addEventListener('click', function() {
        cbs.forEach(function(cb) { cb.checked = true; });
        if (selectAllCb) selectAllCb.checked = true;
        updateSelection();
    });

    document.getElementById('btnDeselectAll').addEventListener('click', function() {
        cbs.forEach(function(cb) { cb.checked = false; });
        if (selectAllCb) selectAllCb.checked = false;
        updateSelection();
    });

    // Click placeholder chip to insert tag into template input
    document.querySelectorAll('.placeholder-tag').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tag = this.getAttribute('data-tag');
            var input = document.getElementById('message_template_input');
            input.value += ' ' + tag;
        });
    });
});
</script>
@endsection
