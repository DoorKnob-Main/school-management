@extends('reports.layouts.base')

@section('title', 'Fee Receipt - ' . $payment->receipt_number)

@section('document-title', setting('finance_receipt_title', 'OFFICIAL FEE RECEIPT'))
@section('document-subtitle', 'Payment Confirmation Document')
@section('document-number', $payment->receipt_number)
@section('document-date', date('d M Y', strtotime($payment->payment_date)))
@section('document-session', $payment->session->session_name ?? 'Current Session')

@section('content')

{{-- Student & Academic Info --}}
<div class="info-grid">
    <div class="info-box">
        <div class="info-box-title">Student Details</div>
        <div class="info-row">
            <span class="info-label">Student Name:</span>
            <span class="info-value">{{ $payment->student->first_name ?? '' }} {{ $payment->student->last_name ?? '' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Father's Name:</span>
            <span class="info-value">{{ $payment->student->parent_info->father_name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Contact:</span>
            <span class="info-value">{{ $payment->student->parent_info->father_phone ?? ($payment->student->phone ?? 'N/A') }}</span>
        </div>
    </div>
    <div class="info-box">
        <div class="info-box-title">Enrollment Details</div>
        <div class="info-row">
            <span class="info-label">Class:</span>
            <span class="info-value">{{ $payment->schoolClass->class_name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Session:</span>
            <span class="info-value">{{ $payment->session->session_name ?? 'Current Session' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fee Structure:</span>
            <span class="info-value">{{ $payment->feeStructure->name ?? 'Standard Fee' }}</span>
        </div>
    </div>
</div>

{{-- Payment Particulars Table --}}
<table class="report-table">
    <thead>
        <tr>
            <th>Description</th>
            <th>Payment Mode</th>
            <th>Reference / Txn #</th>
            <th class="text-right">Amount Paid</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>{{ setting('finance_receipt_particulars_label', 'School Fee Payment') }}</strong>
                @if($payment->notes)
                    <br><span class="text-muted">Notes: {{ $payment->notes }}</span>
                @endif
            </td>
            <td>{{ $payment->payment_mode }}</td>
            <td>{{ $payment->reference_number ?? 'N/A' }}</td>
            <td class="text-right fw-bold">{{ setting('currency_symbol', '₹') }}{{ number_format($payment->amount, 2) }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="fw-bold">Total Paid</td>
            <td class="text-right fw-bold text-success">{{ setting('currency_symbol', '₹') }}{{ number_format($payment->amount, 2) }}</td>
        </tr>
    </tfoot>
</table>

{{-- Account Balance Summary --}}
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-title">Total Allocated Fee</div>
        <div class="kpi-value">{{ setting('currency_symbol', '₹') }}{{ number_format($summary['total_fee'], 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Total Amount Paid</div>
        <div class="kpi-value text-success">{{ setting('currency_symbol', '₹') }}{{ number_format($summary['paid_amount'], 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Remaining Balance Due</div>
        <div class="kpi-value {{ $summary['remaining_due'] > 0 ? 'text-danger' : 'text-success' }}">{{ setting('currency_symbol', '₹') }}{{ number_format($summary['remaining_due'], 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Amount in Words</div>
        <div class="kpi-value" style="font-size: 11px; text-transform: capitalize;">{{ setting('default_currency', 'INR') }} {{ number_format($payment->amount, 2) }} Only</div>
    </div>
</div>

<div style="font-size: 10.5px; color: #64748b;">
    Issued by: <strong>{{ ($payment->creator->first_name ?? 'Admin') . ' ' . ($payment->creator->last_name ?? '') }}</strong>
</div>

@endsection
