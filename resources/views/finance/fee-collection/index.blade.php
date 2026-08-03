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
                            <h1 class="display-6 mb-1"><i class="bi bi-cash-stack"></i> Fee Collection</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                                    <li class="breadcrumb-item">Payment</li>
                                    <li class="breadcrumb-item active">Fee Collection</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <a href="{{route('finance.fee-structure.index')}}" class="btn btn-outline-secondary me-2"><i class="bi bi-gear"></i> Fee Structures</a>
                            <a href="{{route('finance.fee-reminder.index')}}" class="btn btn-outline-warning me-2"><i class="bi bi-bell"></i> Send Reminder</a>
                            <button onclick="window.print()" class="btn btn-outline-primary me-2"><i class="bi bi-printer"></i> Print</button>
                            <button onclick="exportTableToCSV('fee-collection-table.csv')" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export CSV</button>
                        </div>
                    </div>

                    <!-- Search & Filter Card -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="{{route('finance.fee-collection.index')}}" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold"><i class="bi bi-diagram-3"></i> Class</label>
                                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                                        <option value="0">All Classes</option>
                                        @foreach($classes as $cls)
                                            <option value="{{$cls->id}}" {{$selected_class_id == $cls->id ? 'selected' : ''}}>{{$cls->class_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold"><i class="bi bi-layers"></i> Section</label>
                                    <select name="section_id" class="form-select" onchange="this.form.submit()">
                                        <option value="0">All Sections</option>
                                        @foreach($sections as $sec)
                                            <option value="{{$sec->id}}" {{$selected_section_id == $sec->id ? 'selected' : ''}}>{{$sec->section_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold"><i class="bi bi-search"></i> Search Student</label>
                                    <input type="text" name="search" class="form-control" placeholder="Name, Roll No, Admission No, Parent Phone..." value="{{$search}}">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Students Fee Collection Table -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="card-title mb-0 text-primary"><i class="bi bi-people"></i> Student Fee List ({{count($studentsData)}} Students)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="fee-collection-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th><input type="checkbox" id="selectAll"></th>
                                            <th>Photo</th>
                                            <th>Roll No</th>
                                            <th>Adm No</th>
                                            <th>Student Name</th>
                                            <th>Father Name</th>
                                            <th>Father Phone</th>
                                            <th>Class / Sec</th>
                                            <th>Fee Structure</th>
                                            <th>Total Fee</th>
                                            <th>Paid</th>
                                            <th>Remaining Due</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($studentsData as $data)
                                            <tr>
                                                <td><input type="checkbox" class="student-checkbox" value="{{$data['student']->id}}"></td>
                                                <td>
                                                    @if($data['student']->photo)
                                                        <img src="{{asset('storage/'.$data['student']->photo)}}" class="rounded-circle" width="40" height="40" alt="photo">
                                                    @else
                                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                                            {{strtoupper(substr($data['student']->first_name, 0, 1))}}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-light text-dark border">{{$data['promotion']->id_card_number ?? 'N/A'}}</span></td>
                                                <td><span class="badge bg-light text-dark border">{{$data['academic_info']->board_reg_no ?? 'N/A'}}</span></td>
                                                <td class="fw-bold">{{$data['student']->first_name}} {{$data['student']->last_name}}</td>
                                                <td>{{$data['parent_info']->father_name ?? 'N/A'}}</td>
                                                <td>{{$data['parent_info']->father_phone ?? 'N/A'}}</td>
                                                <td>{{$data['schoolClass']->class_name ?? ''}} - {{$data['section']->section_name ?? ''}}</td>
                                                <td>
                                                    @if($data['fee_structure'])
                                                        <span class="badge bg-info text-dark">{{$data['fee_structure']->name}}</span>
                                                    @else
                                                        <span class="badge bg-secondary">Default</span>
                                                    @endif
                                                </td>
                                                <td class="fw-bold">₹{{number_format($data['total_fee'], 2)}}</td>
                                                <td class="text-success fw-bold">₹{{number_format($data['paid_amount'], 2)}}</td>
                                                <td class="text-danger fw-bold">₹{{number_format($data['remaining_due'], 2)}}</td>
                                                <td>
                                                    @if($data['status'] == 'Paid')
                                                        <span class="badge bg-success">Paid</span>
                                                    @elseif($data['status'] == 'Partial')
                                                        <span class="badge bg-warning text-dark">Partial</span>
                                                    @elseif($data['status'] == 'Overdue')
                                                        <span class="badge bg-danger">Overdue</span>
                                                    @else
                                                        <span class="badge bg-secondary">No Fee Assigned</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($data['remaining_due'] > 0 || $data['total_fee'] == 0)
                                                        <button class="btn btn-sm btn-success text-white me-1 btn-collect"
                                                            data-student-id="{{$data['student']->id}}"
                                                            data-student-name="{{$data['student']->first_name}} {{$data['student']->last_name}}"
                                                            data-class-id="{{$data['schoolClass']->id ?? 0}}"
                                                            data-class-name="{{$data['schoolClass']->class_name ?? ''}}"
                                                            data-section-name="{{$data['section']->section_name ?? ''}}"
                                                            data-fee-structure-id="{{$data['fee_structure']->id ?? ''}}"
                                                            data-fee-structure-name="{{$data['fee_structure']->name ?? 'Standard Fee'}}"
                                                            data-total-fee="{{$data['total_fee']}}"
                                                            data-paid-amount="{{$data['paid_amount']}}"
                                                            data-remaining-due="{{$data['remaining_due']}}"
                                                            data-installments='@json($data["fee_structure"] ? $data["fee_structure"]->installments : [])'>
                                                            <i class="bi bi-cash"></i> Collect
                                                        </button>
                                                    @endif
                                                    <button class="btn btn-sm btn-outline-info me-1 btn-history"
                                                        data-student-id="{{$data['student']->id}}"
                                                        data-student-name="{{$data['student']->first_name}} {{$data['student']->last_name}}">
                                                        <i class="bi bi-clock-history"></i> History
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="15" class="text-center py-4 text-muted">No student records found for the selected filter.</td>
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

<!-- Collect Fee Modal -->
<div class="modal fade" id="collectFeeModal" tabindex="-1" aria-labelledby="collectFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{route('finance.fee-collection.collect')}}" id="collectFeeForm">
                @csrf
                <input type="hidden" name="session_id" value="{{$current_school_session_id}}">
                <input type="hidden" name="student_id" id="modal_student_id">
                <input type="hidden" name="class_id" id="modal_class_id">
                <input type="hidden" name="fee_structure_id" id="modal_fee_structure_id">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="collectFeeModalLabel"><i class="bi bi-cash-stack"></i> Collect Fee</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Student & Fee Summary Info Banner -->
                    <div class="row bg-light p-3 rounded mb-3 border">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Student Name:</strong> <span id="modal_student_name"></span></p>
                            <p class="mb-1"><strong>Class & Section:</strong> <span id="modal_class_section"></span></p>
                            <p class="mb-0"><strong>Fee Structure:</strong> <span id="modal_fee_structure_name"></span></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-1"><strong>Total Fee:</strong> ₹<span id="modal_total_fee">0.00</span></p>
                            <p class="mb-1 text-success"><strong>Already Paid:</strong> ₹<span id="modal_paid_amount">0.00</span></p>
                            <p class="mb-0 text-danger fs-5"><strong>Remaining Due:</strong> ₹<span id="modal_remaining_due">0.00</span></p>
                        </div>
                    </div>

                    <!-- Installments Breakdown if available -->
                    <div id="modal_installments_container" class="mb-3 d-none">
                        <h6 class="fw-bold mb-2"><i class="bi bi-list-nested"></i> Installments Schedule</h6>
                        <ul class="list-group" id="modal_installments_list"></ul>
                    </div>

                    <div id="overpayment_warning" class="alert alert-danger d-none" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Payment amount cannot exceed remaining due of <strong id="warning_due_amount">₹0.00</strong>.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="modal_payment_amount" class="form-control form-control-lg" placeholder="Enter amount to pay" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control form-control-lg" value="{{date('Y-m-d')}}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select form-select-lg" required>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Online">Online</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reference Number (Txn ID / Cheque No)</label>
                            <input type="text" name="reference_number" class="form-control form-control-lg" placeholder="Optional reference #">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Notes / Comments</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional payment details..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-lg" id="btn_submit_collect"><i class="bi bi-check-circle"></i> Collect Fee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="historyModalLabel"><i class="bi bi-clock-history"></i> Payment History - <span id="history_student_name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between">
                    <span><strong>Total Fee:</strong> ₹<span id="history_total_fee">0.00</span></span>
                    <span><strong>Total Paid:</strong> ₹<span id="history_paid_amount" class="text-success">0.00</span></span>
                    <span><strong>Remaining Due:</strong> ₹<span id="history_remaining_due" class="text-danger">0.00</span></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Receipt No</th>
                                <th>Date</th>
                                <th>Mode</th>
                                <th>Reference</th>
                                <th>Amount Paid</th>
                                <th>Due After</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="history_table_body">
                            <tr><td colspan="7" class="text-center py-3">Loading history...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Collect Fee button click handler
    document.querySelectorAll('.btn-collect').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var studentId = this.getAttribute('data-student-id');
            var studentName = this.getAttribute('data-student-name');
            var classId = this.getAttribute('data-class-id');
            var className = this.getAttribute('data-class-name');
            var sectionName = this.getAttribute('data-section-name');
            var feeStructureId = this.getAttribute('data-fee-structure-id');
            var feeStructureName = this.getAttribute('data-fee-structure-name');
            var totalFee = parseFloat(this.getAttribute('data-total-fee') || 0);
            var paidAmount = parseFloat(this.getAttribute('data-paid-amount') || 0);
            var remainingDue = parseFloat(this.getAttribute('data-remaining-due') || 0);
            var installments = JSON.parse(this.getAttribute('data-installments') || '[]');

            document.getElementById('modal_student_id').value = studentId;
            document.getElementById('modal_class_id').value = classId;
            document.getElementById('modal_fee_structure_id').value = feeStructureId;
            document.getElementById('modal_student_name').textContent = studentName;
            document.getElementById('modal_class_section').textContent = className + (sectionName ? ' - ' + sectionName : '');
            document.getElementById('modal_fee_structure_name').textContent = feeStructureName;
            document.getElementById('modal_total_fee').textContent = totalFee.toFixed(2);
            document.getElementById('modal_paid_amount').textContent = paidAmount.toFixed(2);
            document.getElementById('modal_remaining_due').textContent = remainingDue.toFixed(2);

            var amountInput = document.getElementById('modal_payment_amount');
            amountInput.value = remainingDue > 0 ? remainingDue : '';
            amountInput.setAttribute('max', remainingDue > 0 ? remainingDue : 999999);

            // Populate installments list
            var instContainer = document.getElementById('modal_installments_container');
            var instList = document.getElementById('modal_installments_list');
            instList.innerHTML = '';
            if (installments && installments.length > 0) {
                instContainer.classList.remove('d-none');
                installments.forEach(function(inst) {
                    var li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center py-1';
                    li.innerHTML = '<span>' + inst.name + (inst.due_date ? ' (Due: ' + inst.due_date + ')' : '') + '</span><span class="fw-bold">₹' + parseFloat(inst.amount).toFixed(2) + '</span>';
                    instList.appendChild(li);
                });
            } else {
                instContainer.classList.add('d-none');
            }

            document.getElementById('overpayment_warning').classList.add('d-none');
            document.getElementById('btn_submit_collect').disabled = false;

            var modal = new bootstrap.Modal(document.getElementById('collectFeeModal'));
            modal.show();
        });
    });

    // Client-side Overpayment Validation
    var amountInput = document.getElementById('modal_payment_amount');
    amountInput.addEventListener('input', function() {
        var entered = parseFloat(this.value || 0);
        var remainingDue = parseFloat(document.getElementById('modal_remaining_due').textContent || 0);
        var warningDiv = document.getElementById('overpayment_warning');
        var submitBtn = document.getElementById('btn_submit_collect');

        if (remainingDue > 0 && entered > remainingDue) {
            document.getElementById('warning_due_amount').textContent = '₹' + remainingDue.toFixed(2);
            warningDiv.classList.remove('d-none');
            submitBtn.disabled = true;
        } else {
            warningDiv.classList.add('d-none');
            submitBtn.disabled = false;
        }
    });

    // Payment History button click handler
    document.querySelectorAll('.btn-history').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var studentId = this.getAttribute('data-student-id');
            var studentName = this.getAttribute('data-student-name');
            document.getElementById('history_student_name').textContent = studentName;

            var tbody = document.getElementById('history_table_body');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3">Loading history...</td></tr>';

            var modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();

            fetch("{{url('/finance/fee-collection/history')}}/" + studentId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('history_total_fee').textContent = parseFloat(data.summary.total_fee).toFixed(2);
                    document.getElementById('history_paid_amount').textContent = parseFloat(data.summary.paid_amount).toFixed(2);
                    document.getElementById('history_remaining_due').textContent = parseFloat(data.summary.remaining_due).toFixed(2);

                    tbody.innerHTML = '';
                    if (data.history && data.history.length > 0) {
                        data.history.forEach(function(pay) {
                            var tr = document.createElement('tr');
                            tr.innerHTML = '<td><span class="badge bg-secondary">' + pay.receipt_number + '</span></td>' +
                                '<td>' + pay.payment_date + '</td>' +
                                '<td><span class="badge bg-info text-dark">' + pay.payment_mode + '</span></td>' +
                                '<td>' + (pay.reference_number ? pay.reference_number : '-') + '</td>' +
                                '<td class="fw-bold text-success">₹' + parseFloat(pay.amount).toFixed(2) + '</td>' +
                                '<td class="text-danger fw-bold">₹' + parseFloat(pay.remaining_due_after).toFixed(2) + '</td>' +
                                '<td class="text-end"><a href="{{url("/finance/fee-collection/receipt")}}/' + pay.id + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-receipt"></i> Receipt</a></td>';
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted">No payment history found for this student.</td></tr>';
                    }
                })
                .catch(err => {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Error loading payment history.</td></tr>';
                });
        });
    });

    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.student-checkbox');
        for (var cb of checkboxes) {
            cb.checked = this.checked;
        }
    });
});

function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll('#fee-collection-table tr');
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        for (var j = 0; j < cols.length - 1; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""').trim() + '"');
        }
        csv.push(row.join(','));
    }
    var csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    var downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>
@endsection
