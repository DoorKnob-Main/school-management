@extends('reports.layouts.base')

@section('title', 'Financial Analytics & Audit Report - ' . ($reportConfig['org_name'] ?? 'DoorKnob'))

@section('document-title', 'INSTITUTIONAL FINANCIAL AUDIT & REVENUE REPORT')
@section('document-subtitle', 'Consolidated Income, Expense, Class-Wise Collection & Outstanding Dues Statement')
@section('document-number', 'FIN-' . date('Ymd') . '-' . rand(100, 999))
@section('document-date', date('d M Y'))
@section('document-session', $sessionName ?? 'Current Session')

@section('content')

{{-- KPI Summary Metric Cards --}}
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-title">Total Fee Collection</div>
        <div class="kpi-value text-success">₹{{ number_format($analytics['total_collection'] ?? 0, 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Total Expenses</div>
        <div class="kpi-value text-danger">₹{{ number_format($analytics['total_expenses'] ?? 0, 2) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Net Operating Balance</div>
        <div class="kpi-value {{ ($analytics['net_balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
            ₹{{ number_format($analytics['net_balance'] ?? 0, 2) }}
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Total Outstanding Dues</div>
        <div class="kpi-value text-danger">₹{{ number_format($analytics['outstanding_fees'] ?? 0, 2) }}</div>
    </div>
</div>

{{-- Scope & Audit Info --}}
<div class="info-grid">
    <div class="info-box">
        <div class="info-box-title">Audit Statement Parameters</div>
        <div class="info-row">
            <span class="info-label">Academic Session:</span>
            <span class="info-value">{{ $sessionName ?? 'Academic Session' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date Filter Applied:</span>
            <span class="info-value">{{ $fromDate ? date('d M Y', strtotime($fromDate)) : 'Session Start' }} to {{ $toDate ? date('d M Y', strtotime($toDate)) : 'Present' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment Mode Filter:</span>
            <span class="info-value">{{ $payment_mode ? $payment_mode : 'All Modes Included' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total Transactions Recorded:</span>
            <span class="info-value">{{ number_format($analytics['transaction_count'] ?? 0) }} entries</span>
        </div>
    </div>

    <div class="info-box">
        <div class="info-box-title">Payment Channel Summary</div>
        @foreach($paymentModeBreakdown as $mode => $amount)
            @if($amount > 0)
                <div class="info-row">
                    <span class="info-label">{{ $mode }}:</span>
                    <span class="info-value">₹{{ number_format($amount, 2) }}</span>
                </div>
            @endif
        @endforeach
        <div class="info-row" style="border-top: 1px dashed #e2e8f0; margin-top: 2px; padding-top: 4px;">
            <span class="info-label">Pending Students:</span>
            <span class="info-value text-danger">{{ $analytics['pending_students'] ?? 0 }} Students</span>
        </div>
    </div>
</div>

{{-- Class-Wise Financial Comparison Table --}}
<div style="font-weight: 700; font-size: 12px; margin-bottom: 6px; text-transform: uppercase; color: var(--report-primary);">
    Class-Wise Fee Collection & Outstanding Dues Breakdown
</div>

<table class="report-table">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 25%;">Class Name</th>
            <th style="width: 14%; text-align: center;">Enrolled</th>
            <th style="width: 18%; text-align: right;">Total Expected</th>
            <th style="width: 18%; text-align: right;">Collected</th>
            <th style="width: 20%; text-align: right;">Remaining Due</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totStudents = 0;
            $totExp = 0;
            $totCol = 0;
            $totDue = 0;
        @endphp

        @forelse($classWiseComparison as $idx => $row)
            @php
                $totStudents += $row['student_count'];
                $totExp += $row['total_expected'];
                $totCol += $row['total_collected'];
                $totDue += $row['total_pending'];
            @endphp
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td class="fw-bold">{{ $row['class_name'] }}</td>
                <td class="text-center">{{ $row['student_count'] }}</td>
                <td class="text-right">₹{{ number_format($row['total_expected'], 2) }}</td>
                <td class="text-right text-success fw-bold">₹{{ number_format($row['total_collected'], 2) }}</td>
                <td class="text-right {{ $row['total_pending'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                    ₹{{ number_format($row['total_pending'], 2) }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-3">No class fee data available for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="fw-bold">Consolidated Totals</td>
            <td class="text-center fw-bold">{{ $totStudents }}</td>
            <td class="text-right fw-bold">₹{{ number_format($totExp, 2) }}</td>
            <td class="text-right text-success fw-bold">₹{{ number_format($totCol, 2) }}</td>
            <td class="text-right text-danger fw-bold">₹{{ number_format($totDue, 2) }}</td>
        </tr>
    </tfoot>
</table>

{{-- Institutional Compliance Disclaimer --}}
<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 12px; font-size: 10.5px; color: #475569; margin-top: 10px;">
    <strong>Financial Reconciliation Note:</strong> This report represents an official institutional record generated automatically by {{ $reportConfig['org_name'] }} ERP. Figures reflect transactions cleared up to {{ date('d M Y H:i:s') }}. For questions regarding fee reconciliation, contact the finance bursar at {{ $reportConfig['email'] ?: $reportConfig['phone'] }}.
</div>

@endsection
