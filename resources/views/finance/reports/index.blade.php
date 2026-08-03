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
                            <h1 class="display-6 mb-1"><i class="bi bi-bar-chart-line"></i> Financial Analytics & Reports</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                                    <li class="breadcrumb-item">Payment</li>
                                    <li class="breadcrumb-item active">Reports</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <button onclick="window.print()" class="btn btn-outline-primary me-2"><i class="bi bi-printer"></i> Print Report</button>
                            <button onclick="exportTableToCSV('financial-reports.csv')" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export CSV</button>
                        </div>
                    </div>

                    <!-- Filter Card with Multi-Class Selection and Quick Date Presets -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="{{route('finance.reports.index')}}" id="reportForm">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold"><i class="bi bi-diagram-3"></i> Select Classes (Multi-Select)</label>
                                        <select name="class_ids[]" class="form-select" multiple style="height: 110px;">
                                            @foreach($classes as $cls)
                                                <option value="{{$cls->id}}" {{in_array($cls->id, $selectedClassIds) ? 'selected' : ''}}>{{$cls->class_name}}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Hold Ctrl / Cmd to select multiple classes, or leave unselected for all.</small>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold"><i class="bi bi-credit-card"></i> Payment Mode</label>
                                                <select name="payment_mode" class="form-select">
                                                    <option value="">All Payment Modes</option>
                                                    @foreach(['Cash','UPI','Cheque','Card','Bank Transfer','Online','Other'] as $m)
                                                        <option value="{{$m}}" {{$payment_mode == $m ? 'selected' : ''}}>{{$m}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold"><i class="bi bi-clock-history"></i> Quick Preset</label>
                                                <select name="quick_filter" id="quick_filter" class="form-select" onchange="toggleCustomDates(this.value)">
                                                    <option value="current_session" {{$quickFilter == 'current_session' ? 'selected' : ''}}>Full Session</option>
                                                    <option value="today" {{$quickFilter == 'today' ? 'selected' : ''}}>Today</option>
                                                    <option value="yesterday" {{$quickFilter == 'yesterday' ? 'selected' : ''}}>Yesterday</option>
                                                    <option value="this_week" {{$quickFilter == 'this_week' ? 'selected' : ''}}>This Week</option>
                                                    <option value="last_week" {{$quickFilter == 'last_week' ? 'selected' : ''}}>Last Week</option>
                                                    <option value="this_month" {{$quickFilter == 'this_month' ? 'selected' : ''}}>This Month</option>
                                                    <option value="last_month" {{$quickFilter == 'last_month' ? 'selected' : ''}}>Last Month</option>
                                                    <option value="custom" {{$quickFilter == 'custom' ? 'selected' : ''}}>Custom Date Range</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 custom-date-field {{$quickFilter == 'custom' ? '' : 'd-none'}}">
                                                <label class="form-label fw-bold">From Date</label>
                                                <input type="date" name="from_date" class="form-control" value="{{$fromDate}}">
                                            </div>
                                            <div class="col-md-6 custom-date-field {{$quickFilter == 'custom' ? '' : 'd-none'}}">
                                                <label class="form-label fw-bold">To Date</label>
                                                <input type="date" name="to_date" class="form-control" value="{{$toDate}}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{route('finance.reports.index')}}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply Analytics Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Analytics Key Performance Indicators -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-success text-white shadow-sm h-100">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Fee Collection</h6>
                                        <h2 class="display-6 fw-bold mb-0">₹{{number_format($analytics['total_collection'], 2)}}</h2>
                                    </div>
                                    <i class="bi bi-cash-stack fs-1 text-white-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-danger text-white shadow-sm h-100">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Expenses</h6>
                                        <h2 class="display-6 fw-bold mb-0">₹{{number_format($analytics['total_expenses'], 2)}}</h2>
                                    </div>
                                    <i class="bi bi-credit-card fs-1 text-white-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-primary text-white shadow-sm h-100">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Net Income / Balance</h6>
                                        <h2 class="display-6 fw-bold mb-0">₹{{number_format($analytics['net_balance'], 2)}}</h2>
                                    </div>
                                    <i class="bi bi-wallet2 fs-1 text-white-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-warning text-dark shadow-sm h-100">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase fw-bold mb-1">Total Outstanding Dues</h6>
                                        <h2 class="display-6 fw-bold mb-0">₹{{number_format($analytics['outstanding_fees'], 2)}}</h2>
                                    </div>
                                    <i class="bi bi-exclamation-octagon fs-1 text-dark-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-info text-dark shadow-sm h-100">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted text-uppercase fw-bold mb-1">Pending Students</h6>
                                        <h2 class="display-6 fw-bold mb-0">{{$analytics['pending_students']}} Students</h2>
                                    </div>
                                    <i class="bi bi-people fs-1 text-dark-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 bg-dark text-white shadow-sm h-100">
                                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50 text-uppercase fw-bold mb-1">Transaction Ledger Entries</h6>
                                        <h2 class="display-6 fw-bold mb-0">{{$analytics['transaction_count']}} Records</h2>
                                    </div>
                                    <i class="bi bi-journal-check fs-1 text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Mode Collection Breakdown -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0 text-primary"><i class="bi bi-pie-chart"></i> Collection Breakdown by Payment Mode</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                @foreach($paymentModeBreakdown as $mode => $sum)
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="p-3 border rounded bg-light">
                                            <span class="badge bg-secondary mb-2">{{$mode}}</span>
                                            <h4 class="fw-bold text-dark mb-0">₹{{number_format($sum, 2)}}</h4>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Class-Wise Financial Comparison Table -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 text-primary"><i class="bi bi-table"></i> Multi-Class Fee Collection & Outstanding Breakdown</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle mb-0" id="reports-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Class Name</th>
                                            <th>Total Enrolled Students</th>
                                            <th>Total Expected Fee</th>
                                            <th>Collected Amount</th>
                                            <th>Outstanding Dues</th>
                                            <th>Pending Students</th>
                                            <th>Collection Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $grandTotalFee = 0;
                                            $grandPaid = 0;
                                            $grandOutstanding = 0;
                                            $grandPendingStudents = 0;
                                        @endphp
                                        @forelse($classWiseComparison as $row)
                                            @php
                                                $grandTotalFee += $row['total_fee'];
                                                $grandPaid += $row['paid_amount'];
                                                $grandOutstanding += $row['outstanding'];
                                                $grandPendingStudents += $row['pending_count'];
                                                $pct = $row['total_fee'] > 0 ? round(($row['paid_amount'] / $row['total_fee']) * 100, 1) : 0;
                                            @endphp
                                            <tr>
                                                <td class="fw-bold">{{$row['class_name']}}</td>
                                                <td><span class="badge bg-light text-dark border">{{$row['student_count']}} Students</span></td>
                                                <td class="fw-bold">₹{{number_format($row['total_fee'], 2)}}</td>
                                                <td class="text-success fw-bold">₹{{number_format($row['paid_amount'], 2)}}</td>
                                                <td class="text-danger fw-bold">₹{{number_format($row['outstanding'], 2)}}</td>
                                                <td><span class="badge bg-warning text-dark">{{$row['pending_count']}}</span></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{$pct}}%;" aria-valuenow="{{$pct}}" aria-valuemin="0" aria-valuemax="100">{{$pct}}%</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">No class data found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-dark fw-bold">
                                        <tr>
                                            <td>Combined Totals</td>
                                            <td>-</td>
                                            <td>₹{{number_format($grandTotalFee, 2)}}</td>
                                            <td class="text-success">₹{{number_format($grandPaid, 2)}}</td>
                                            <td class="text-danger">₹{{number_format($grandOutstanding, 2)}}</td>
                                            <td>{{$grandPendingStudents}} Pending</td>
                                            <td>
                                                @php
                                                    $overallPct = $grandTotalFee > 0 ? round(($grandPaid / $grandTotalFee) * 100, 1) : 0;
                                                @endphp
                                                {{$overallPct}}% Overall
                                            </td>
                                        </tr>
                                    </tfoot>
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
function toggleCustomDates(val) {
    var fields = document.querySelectorAll('.custom-date-field');
    fields.forEach(function(f) {
        if (val === 'custom') {
            f.classList.remove('d-none');
        } else {
            f.classList.add('d-none');
        }
    });
}

function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll('#reports-table tr');
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
