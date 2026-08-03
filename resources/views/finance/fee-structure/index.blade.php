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
                            <h1 class="display-6 mb-1"><i class="bi bi-gear"></i> Fee Structure Configuration</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                                    <li class="breadcrumb-item">Payment</li>
                                    <li class="breadcrumb-item active">Fee Structure</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStructureModal"><i class="bi bi-plus-circle"></i> Create Fee Structure</button>
                        </div>
                    </div>

                    <!-- Fee Structures List -->
                    <div class="row">
                        @forelse($structures as $struct)
                            <div class="col-md-6 col-xl-4 mb-4">
                                <div class="card shadow-sm h-100 border-0">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                                        <h5 class="card-title mb-0 fw-bold">{{$struct->name}}</h5>
                                        <form method="POST" action="{{route('finance.fee-structure.destroy', $struct->id)}}" onsubmit="return confirm('Delete this fee structure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-light"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><strong>Class:</strong> {{$struct->schoolClass->class_name ?? 'All Classes (Default)'}}</p>
                                        <p class="mb-3"><strong>Total Fee:</strong> <span class="fs-4 fw-bold text-success">₹{{number_format($struct->total_amount, 2)}}</span></p>
                                        <p class="small text-muted mb-3">{{$struct->description ?? 'No description provided.'}}</p>

                                        <h6 class="fw-bold text-secondary border-bottom pb-1"><i class="bi bi-list-nested"></i> Installments Breakdown</h6>
                                        <ul class="list-group list-group-flush mb-0">
                                            @forelse($struct->installments as $inst)
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <span class="fw-bold">{{$inst->name}}</span>
                                                        @if($inst->due_date)
                                                            <small class="d-block text-muted">Due: {{$inst->due_date}}</small>
                                                        @endif
                                                    </div>
                                                    <span class="badge bg-light text-dark border fs-6">₹{{number_format($inst->amount, 2)}}</span>
                                                </li>
                                            @empty
                                                <li class="list-group-item px-0 text-muted small">Lump sum payment (No installments).</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info py-4 text-center" role="alert">
                                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                    <h5>No Fee Structures Defined Yet</h5>
                                    <p class="mb-3">Click below to create a fee structure with dynamic installments for your classes.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStructureModal"><i class="bi bi-plus-circle"></i> Create First Fee Structure</button>
                                </div>
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>

<!-- Create Fee Structure Modal -->
<div class="modal fade" id="createStructureModal" tabindex="-1" aria-labelledby="createStructureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{route('finance.fee-structure.store')}}">
                @csrf
                <input type="hidden" name="session_id" value="{{$current_school_session_id}}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createStructureModalLabel"><i class="bi bi-plus-circle"></i> Create Fee Structure</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Structure Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Annual Tuition Fee 2026" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Applicable Class</label>
                            <select name="class_id" class="form-select">
                                <option value="">All Classes (Default)</option>
                                @foreach($classes as $cls)
                                    <option value="{{$cls->id}}">{{$cls->class_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>

                    <!-- Dynamic Installments Section -->
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-list-stars"></i> Dynamic Installments Schedule</h6>
                            <button type="button" class="btn btn-sm btn-outline-success" id="btnAddInstallment"><i class="bi bi-plus"></i> Add Installment</button>
                        </div>

                        <div id="installments_wrapper">
                            <div class="row g-2 mb-2 installment-row">
                                <div class="col-md-5">
                                    <input type="text" name="installments[0][name]" class="form-control" placeholder="Installment 1 / Term 1" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" step="0.01" min="0" name="installments[0][amount]" class="form-control inst-amount" placeholder="Amount (₹)" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" name="installments[0][due_date]" class="form-control">
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-remove-inst" disabled><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center mt-3 pt-2 border-top">
                            <span class="fs-5 me-2">Calculated Total Fee:</span>
                            <span class="fs-4 fw-bold text-success" id="calculatedTotalFee">₹0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> Save Fee Structure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var instCount = 1;
    var wrapper = document.getElementById('installments_wrapper');

    function calculateTotal() {
        var total = 0;
        document.querySelectorAll('.inst-amount').forEach(function(inp) {
            total += parseFloat(inp.value || 0);
        });
        document.getElementById('calculatedTotalFee').textContent = '₹' + total.toFixed(2);
    }

    wrapper.addEventListener('input', function(e) {
        if (e.target.classList.contains('inst-amount')) {
            calculateTotal();
        }
    });

    document.getElementById('btnAddInstallment').addEventListener('click', function() {
        var div = document.createElement('div');
        div.className = 'row g-2 mb-2 installment-row';
        div.innerHTML = '<div class="col-md-5"><input type="text" name="installments[' + instCount + '][name]" class="form-control" placeholder="Installment ' + (instCount + 1) + '" required></div>' +
            '<div class="col-md-3"><input type="number" step="0.01" min="0" name="installments[' + instCount + '][amount]" class="form-control inst-amount" placeholder="Amount (₹)" required></div>' +
            '<div class="col-md-3"><input type="date" name="installments[' + instCount + '][due_date]" class="form-control"></div>' +
            '<div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-remove-inst"><i class="bi bi-trash"></i></button></div>';
        wrapper.appendChild(div);
        instCount++;

        div.querySelector('.btn-remove-inst').addEventListener('click', function() {
            div.remove();
            calculateTotal();
        });
    });
});
</script>
@endsection
