<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Voucher - #{{$expense->id}}</title>
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
            body { background: #fff; }
            .receipt-card { box-shadow: none; margin: 0; width: 100%; max-width: 100%; padding: 10px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print text-center mt-3 d-flex justify-content-center gap-2">
        <a href="{{ route('finance.expenses.voucher-pdf', $expense->id) }}" class="btn btn-success btn-lg"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
        <button onclick="window.print()" class="btn btn-primary btn-lg"><i class="bi bi-printer"></i> Print Voucher</button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Close</button>
    </div>

    <div class="receipt-card">
        <x-report-header
            title="EXPENSE VOUCHER"
            subtitle="Expense Payment Confirmation"
            :docNumber="'EXP-' . str_pad($expense->id, 6, '0', STR_PAD_LEFT)"
            :date="date('d M Y', strtotime($expense->date))"
        />

        <div class="row bg-light p-3 rounded mb-4 border">
            <div class="col-6">
                <p class="mb-1"><strong>Category:</strong> {{$expense->category}}</p>
                <p class="mb-0"><strong>Title:</strong> {{$expense->title}}</p>
            </div>
            <div class="col-6 text-end">
                <p class="mb-1"><strong>Date:</strong> {{date('d M Y', strtotime($expense->date))}}</p>
                <p class="mb-0"><strong>Reference #:</strong> {{$expense->reference_number ?? 'N/A'}}</p>
            </div>
        </div>

        <table class="table table-bordered align-middle mb-4">
            <thead class="table-dark" style="background-color: {{ setting('primary_color', '#0d6efd') }};">
                <tr>
                    <th>Description</th>
                    <th>Payment Mode</th>
                    <th class="text-end">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{$expense->title}}</strong><br>
                        <small class="text-muted">Notes: {{$expense->description ?? 'None'}}</small>
                    </td>
                    <td><span class="badge bg-info text-dark">{{$expense->payment_mode}}</span></td>
                    <td class="text-end fs-5 fw-bold text-danger">{{ setting('currency_symbol', '₹') }}{{number_format($expense->amount, 2)}}</td>
                </tr>
            </tbody>
        </table>

        <div class="row mb-5">
            <div class="col-6 text-end offset-6">
                <div class="p-3 border rounded bg-light">
                    <p class="mb-1 text-muted">Amount in words:</p>
                    <h6 class="fst-italic text-capitalize">{{ setting('default_currency', 'INR') }} {{number_format($expense->amount, 2)}} Only</h6>
                    <hr>
                    <h5 class="fw-bold">Total Paid: {{ setting('currency_symbol', '₹') }}{{number_format($expense->amount, 2)}}</h5>
                </div>
            </div>
        </div>

        <x-report-footer
            :issuedBy="($expense->creator->first_name ?? 'Admin') . ' ' . ($expense->creator->last_name ?? '')"
            :showSignature="true"
        />
    </div>

</body>
</html>
