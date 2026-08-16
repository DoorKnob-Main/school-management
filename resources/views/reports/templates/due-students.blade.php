@extends('reports.layouts.base')

@section('title', 'Due Students Report - ' . ($reportConfig['org_name'] ?? 'DoorKnob'))

@section('document-title', 'STUDENT FEE DUES STATEMENT')
@section('document-subtitle', 'Outstanding Fee Breakdown by Student')
@section('document-number', 'DUE-' . date('Ymd') . '-' . rand(100, 999))
@section('document-date', date('d M Y'))
@section('document-session', $sessionName ?? 'Current Session')

@section('content')

<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-title">Students Listed</div>
        <div class="kpi-value">{{ count($rows) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Total Outstanding Dues</div>
        <div class="kpi-value text-danger">₹{{ number_format($totalDue, 2) }}</div>
    </div>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 20%;">Student</th>
            <th style="width: 16%;">Class / Section</th>
            <th style="width: 22%;">Fee Breakup</th>
            <th style="width: 12%; text-align: right;">Total Fee</th>
            <th style="width: 12%; text-align: right;">Paid</th>
            <th style="width: 14%; text-align: right;">Due</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $idx => $row)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td class="fw-bold">{{ $row['student_name'] }}</td>
                <td>{{ $row['class_name'] }} {{ $row['section_name'] ? '- '.$row['section_name'] : '' }}</td>
                <td style="font-size: 10px;">
                    @forelse($row['breakup'] as $comp)
                        {{ $comp['name'] }}: {{ $comp['is_percentage'] ? number_format($comp['amount'], 2).'%' : '₹'.number_format($comp['amount'], 2) }}<br>
                    @empty
                        —
                    @endforelse
                </td>
                <td class="text-right">₹{{ number_format($row['total_fee'], 2) }}</td>
                <td class="text-right text-success">₹{{ number_format($row['paid_amount'], 2) }}</td>
                <td class="text-right {{ $row['remaining_due'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">₹{{ number_format($row['remaining_due'], 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-3">No students match the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="fw-bold text-end">Total Outstanding Due</td>
            <td class="text-right fw-bold text-danger">₹{{ number_format($totalDue, 2) }}</td>
        </tr>
    </tfoot>
</table>

@endsection
