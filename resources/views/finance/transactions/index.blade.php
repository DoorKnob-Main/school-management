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
                            <h1 class="display-6 mb-1"><i class="bi bi-journal-text"></i> Finance Ledger / Transactions</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                                    <li class="breadcrumb-item">Payment</li>
                                    <li class="breadcrumb-item active">Transactions</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <button onclick="window.print()" class="btn btn-outline-primary me-2"><i class="bi bi-printer"></i> Print</button>
                            <button onclick="exportTableToCSV('transactions-ledger.csv')" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export CSV</button>
                        </div>
                    </div>

                    <!-- Analytics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 bg-success text-white shadow-sm">
                                <div class="card-body p-3">
                                    <h6 class="text-white-50"><i class="bi bi-arrow-down-left-circle"></i> Total Income / Collection</h6>
                                    <h3 class="fw-bold mb-0">₹{{number_format($summary['total_collection'], 2)}}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-danger text-white shadow-sm">
                                <div class="card-body p-3">
                                    <h6 class="text-white-50"><i class="bi bi-arrow-up-right-circle"></i> Total Expenses</h6>
                                    <h3 class="fw-bold mb-0">₹{{number_format($summary['total_expenses'], 2)}}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-primary text-white shadow-sm">
                                <div class="card-body p-3">
                                    <h6 class="text-white-50"><i class="bi bi-wallet2"></i> Net Balance</h6>
                                    <h3 class="fw-bold mb-0">₹{{number_format($summary['net_balance'], 2)}}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-dark text-white shadow-sm">
                                <div class="card-body p-3">
                                    <h6 class="text-white-50"><i class="bi bi-list-ol"></i> Total Transactions</h6>
                                    <h3 class="fw-bold mb-0">{{$summary['transaction_count']}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters Card -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="{{route('finance.transactions.index')}}" id="filterForm">
                                <div class="row g-3 mb-2">
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Class</label>
                                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                                            <option value="">All Classes</option>
                                            @foreach($classes as $cls)
                                                <option value="{{$cls->id}}" {{($filters['class_id'] ?? '') == $cls->id ? 'selected' : ''}}>{{$cls->class_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Type</label>
                                        <select name="transaction_type" class="form-select" onchange="this.form.submit()">
                                            <option value="">All Types</option>
                                            <option value="income" {{($filters['transaction_type'] ?? '') == 'income' ? 'selected' : ''}}>Income (Fee)</option>
                                            <option value="expense" {{($filters['transaction_type'] ?? '') == 'expense' ? 'selected' : ''}}>Expense</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Payment Mode</label>
                                        <select name="payment_mode" class="form-select" onchange="this.form.submit()">
                                            <option value="">All Modes</option>
                                            @foreach(['Cash','UPI','Cheque','Card','Bank Transfer','Online','Other'] as $m)
                                                <option value="{{$m}}" {{($filters['payment_mode'] ?? '') == $m ? 'selected' : ''}}>{{$m}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">From Date</label>
                                        <input type="date" name="from_date" id="from_date" class="form-control" value="{{$filters['from_date'] ?? ''}}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">To Date</label>
                                        <input type="date" name="to_date" id="to_date" class="form-control" value="{{$filters['to_date'] ?? ''}}">
                                    </div>
                                    <div class="col-md-2 d-grid">
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
                                    </div>
                                </div>

                                <!-- Quick Date Filters -->
                                <div class="d-flex align-items-center gap-1 flex-wrap pt-2 border-top">
                                    <span class="fw-bold me-2 text-muted small"><i class="bi bi-calendar-event"></i> Quick Dates:</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quick" onclick="setQuickDate('today')">Today</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quick" onclick="setQuickDate('yesterday')">Yesterday</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quick" onclick="setQuickDate('this_week')">This Week</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quick" onclick="setQuickDate('last_week')">Last Week</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quick" onclick="setQuickDate('this_month')">This Month</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quick" onclick="setQuickDate('last_month')">Last Month</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quick" onclick="setQuickDate('reset')">Current Session</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Ledger Table -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0 text-primary"><i class="bi bi-list-stars"></i> Transaction Ledger</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="transactions-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference #</th>
                                            <th>Type</th>
                                            <th>Student / Particulars</th>
                                            <th>Class / Sec</th>
                                            <th>Payment Mode</th>
                                            <th class="text-end">Amount</th>
                                            <th>Created By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions as $trx)
                                            <tr>
                                                <td>{{date('Y-m-d', strtotime($trx->date))}}</td>
                                                <td><span class="badge bg-light text-dark border">{{$trx->reference_number ?? 'N/A'}}</span></td>
                                                <td>
                                                    @if($trx->transaction_type == 'income')
                                                        <span class="badge bg-success"><i class="bi bi-arrow-down-left"></i> Income</span>
                                                    @else
                                                        <span class="badge bg-danger"><i class="bi bi-arrow-up-right"></i> Expense</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($trx->transaction_type == 'income' && $trx->student)
                                                        <strong>{{$trx->student->first_name}} {{$trx->student->last_name}}</strong>
                                                    @elseif($trx->expense)
                                                        <strong>{{$trx->expense->title}}</strong> <small class="text-muted">({{$trx->expense->category}})</small>
                                                    @else
                                                        <em>General Entry</em>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($trx->schoolClass)
                                                        {{$trx->schoolClass->class_name}}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-info text-dark">{{$trx->payment_mode}}</span></td>
                                                <td class="text-end fw-bold {{$trx->transaction_type == 'income' ? 'text-success' : 'text-danger'}}">
                                                    {{$trx->transaction_type == 'income' ? '+' : '-'}}₹{{number_format($trx->amount, 2)}}
                                                </td>
                                                <td><small>{{$trx->creator->first_name ?? 'Admin'}}</small></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">No transactions found matching your criteria.</td>
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

<script>
function setQuickDate(type) {
    var fromInput = document.getElementById('from_date');
    var toInput = document.getElementById('to_date');
    var today = new Date();

    function formatDate(d) {
        var month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }

    if (type === 'today') {
        fromInput.value = formatDate(today);
        toInput.value = formatDate(today);
    } else if (type === 'yesterday') {
        var y = new Date();
        y.setDate(y.getDate() - 1);
        fromInput.value = formatDate(y);
        toInput.value = formatDate(y);
    } else if (type === 'this_week') {
        var first = today.getDate() - today.getDay();
        var firstDay = new Date(today.setDate(first));
        var lastDay = new Date(today.setDate(first + 6));
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(lastDay);
    } else if (type === 'this_month') {
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(lastDay);
    } else if (type === 'last_month') {
        var firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        var lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(lastDay);
    } else if (type === 'reset') {
        fromInput.value = '';
        toInput.value = '';
    }
    document.getElementById('filterForm').submit();
}

function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll('#transactions-table tr');
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        for (var j = 0; j < cols.length; j++) {
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
