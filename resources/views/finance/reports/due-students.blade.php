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
                            <h1 class="display-6 mb-1"><i class="bi bi-exclamation-circle"></i> Due Students Report</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                                    <li class="breadcrumb-item">Payment</li>
                                    <li class="breadcrumb-item"><a href="{{route('finance.reports.index')}}">Reports</a></li>
                                    <li class="breadcrumb-item active">Due Students</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('finance.reports.due-students.preview', request()->query()) }}" target="_blank" class="btn btn-outline-secondary">
                                <i class="bi bi-eye"></i> Preview (All Filtered)
                            </a>
                            <a href="{{ route('finance.reports.due-students.pdf', request()->query()) }}" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf"></i> Download PDF (All Filtered)
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="{{route('finance.reports.due-students.index')}}" id="dueFilterForm">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold"><i class="bi bi-diagram-3"></i> Class</label>
                                        <select name="class_id" class="form-select" onchange="document.getElementById('dueFilterForm').submit()">
                                            <option value="">All Classes</option>
                                            @foreach($classes as $cls)
                                                <option value="{{$cls->id}}" {{ (string)$classId === (string)$cls->id ? 'selected' : '' }}>{{$cls->class_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold"><i class="bi bi-people"></i> Section</label>
                                        <select name="section_id" class="form-select" {{ $classId ? '' : 'disabled' }}>
                                            <option value="">All Sections</option>
                                            @foreach($sections as $sec)
                                                <option value="{{$sec->id}}" {{ (string)$sectionId === (string)$sec->id ? 'selected' : '' }}>{{$sec->section_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="only_due" value="1" id="onlyDue" {{ $onlyDue ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="onlyDue">Only students with dues</label>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Results + Individual Selection -->
                    <form method="GET" action="{{ route('finance.reports.due-students.preview') }}" target="_blank" id="selectForm">
                        <input type="hidden" name="class_id" value="{{ $classId }}">
                        <input type="hidden" name="section_id" value="{{ $sectionId }}">
                        <input type="hidden" name="only_due" value="{{ $onlyDue ? 1 : 0 }}">

                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <span class="fw-bold">{{ count($rows) }} student(s) — Total Outstanding: <span class="text-danger">₹{{ number_format($totalDue, 2) }}</span></span>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Preview Selected</button>
                                    <button type="submit" formaction="{{ route('finance.reports.due-students.pdf') }}" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-pdf"></i> PDF Selected</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:2%;"><input type="checkbox" onclick="document.querySelectorAll('.student-check').forEach(c=>c.checked=this.checked)"></th>
                                            <th>Student</th>
                                            <th>Class / Section</th>
                                            <th class="text-end">Total Fee</th>
                                            <th class="text-end">Paid</th>
                                            <th class="text-end">Due</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($rows as $row)
                                        <tr>
                                            <td><input type="checkbox" class="student-check" name="student_ids[]" value="{{ $row['student_id'] }}"></td>
                                            <td>{{ $row['student_name'] }}</td>
                                            <td>{{ $row['class_name'] }} {{ $row['section_name'] ? '- '.$row['section_name'] : '' }}</td>
                                            <td class="text-end">₹{{ number_format($row['total_fee'], 2) }}</td>
                                            <td class="text-end text-success">₹{{ number_format($row['paid_amount'], 2) }}</td>
                                            <td class="text-end {{ $row['remaining_due'] > 0 ? 'text-danger fw-bold' : '' }}">₹{{ number_format($row['remaining_due'], 2) }}</td>
                                            <td>
                                                <span class="badge {{ $row['status'] === 'Paid' ? 'bg-success' : ($row['status'] === 'Partial' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ $row['status'] }}</span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="7" class="text-center text-muted py-4">No students match the selected filters.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>
@endsection
