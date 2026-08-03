<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt - {{$payment->receipt_number}}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: {{ setting('background_color', '#f8f9fa') }};
            font-family: {{ setting('font_family', "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif") }};
        }
        .receipt-card {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: {{ setting('card_radius', '10px') }};
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        @media print {
            body {
                background: #fff;
            }
            .receipt-card {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: 100%;
                padding: 10px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="no-print text-center mt-3">
        <button onclick="window.print()" class="btn btn-primary btn-lg me-2"><i class="bi bi-printer"></i> Print Receipt</button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Close</button>
    </div>

    <div class="receipt-card">
        <!-- Dynamic School Report Header -->
        <x-report-header 
            title="OFFICIAL FEE RECEIPT"
            subtitle="Payment Confirmation Document"
            :docNumber="$payment->receipt_number"
            :date="date('d M Y', strtotime($payment->payment_date))" 
        />

        <!-- Student & Academic Info -->
        <div class="row bg-light p-3 rounded mb-4 border">
            <div class="col-6">
                <p class="mb-1"><strong>Student Name:</strong> {{$payment->student->first_name}} {{$payment->student->last_name}}</p>
                <p class="mb-1"><strong>Father's Name:</strong> {{$payment->student->parent_info->father_name ?? 'N/A'}}</p>
                <p class="mb-0"><strong>Contact:</strong> {{$payment->student->parent_info->father_phone ?? ($payment->student->phone ?? 'N/A')}}</p>
            </div>
            <div class="col-6 text-end">
                <p class="mb-1"><strong>Class:</strong> {{$payment->schoolClass->class_name ?? 'N/A'}}</p>
                <p class="mb-1"><strong>Session:</strong> {{$payment->session->session_name ?? 'Current Session'}}</p>
                <p class="mb-0"><strong>Fee Structure:</strong> {{$payment->feeStructure->name ?? 'Standard Fee'}}</p>
            </div>
        </div>

        <!-- Payment Particulars Table -->
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-dark" style="background-color: {{ setting('primary_color', '#0d6efd') }};">
                <tr>
                    <th>Description</th>
                    <th>Payment Mode</th>
                    <th>Reference / Txn #</th>
                    <th class="text-end">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>School Fee Payment</strong><br>
                        <small class="text-muted">Notes: {{$payment->notes ?? 'None'}}</small>
                    </td>
                    <td><span class="badge bg-info text-dark">{{$payment->payment_mode}}</span></td>
                    <td>{{$payment->reference_number ?? 'N/A'}}</td>
                    <td class="text-end fs-5 fw-bold text-success">{{ setting('currency_symbol', '₹') }}{{number_format($payment->amount, 2)}}</td>
                </tr>
            </tbody>
        </table>

        <!-- Account Balance Summary -->
        <div class="row mb-5">
            <div class="col-6">
                <div class="p-3 border rounded bg-light">
                    <h6><i class="bi bi-info-circle"></i> Fee Account Overview</h6>
                    <small class="d-block">Total Allocated Fee: <strong>{{ setting('currency_symbol', '₹') }}{{number_format($summary['total_fee'], 2)}}</strong></small>
                    <small class="d-block text-success">Total Amount Paid: <strong>{{ setting('currency_symbol', '₹') }}{{number_format($summary['paid_amount'], 2)}}</strong></small>
                    <small class="d-block text-danger">Remaining Balance Due: <strong>{{ setting('currency_symbol', '₹') }}{{number_format($summary['remaining_due'], 2)}}</strong></small>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="p-3 border rounded bg-light">
                    <p class="mb-1 text-muted">Amount in words:</p>
                    <h6 class="fst-italic text-capitalize">{{ setting('default_currency', 'INR') }} {{number_format($payment->amount, 2)}} Only</h6>
                    <hr>
                    <h5 class="fw-bold">Total Paid: {{ setting('currency_symbol', '₹') }}{{number_format($payment->amount, 2)}}</h5>
                </div>
            </div>
        </div>

        <!-- Dynamic Signature & Report Footer -->
        <x-report-footer 
            :issuedBy="($payment->creator->first_name ?? 'Admin') . ' ' . ($payment->creator->last_name ?? '')" 
            :showSignature="true" 
        />
    </div>
</div>

</body>
</html>
