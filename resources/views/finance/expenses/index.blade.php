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
                            <h1 class="display-6 mb-1"><i class="bi bi-credit-card"></i> Expense Management</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                                    <li class="breadcrumb-item">Payment</li>
                                    <li class="breadcrumb-item active">Expenses</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal"><i class="bi bi-plus-circle"></i> Add Expense</button>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-danger text-white shadow-sm border-0">
                                <div class="card-body p-3">
                                    <h6 class="text-white-50"><i class="bi bi-currency-dollar"></i> Filtered Total Expense</h6>
                                    <h3 class="fw-bold mb-0">₹{{number_format($totalAmount, 2)}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Card -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="{{route('finance.expenses.index')}}" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Category</label>
                                    <select name="category" class="form-select" onchange="this.form.submit()">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $cat)
                                            <option value="{{$cat}}" {{($filters['category'] ?? '') == $cat ? 'selected' : ''}}>{{$cat}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Payment Mode</label>
                                    <select name="payment_mode" class="form-select" onchange="this.form.submit()">
                                        <option value="">All Modes</option>
                                        @foreach(['Cash','UPI','Cheque','Card','Bank Transfer','Online','Other'] as $m)
                                            <option value="{{$m}}" {{($filters['payment_mode'] ?? '') == $m ? 'selected' : ''}}>{{$m}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Search</label>
                                    <input type="text" name="search" class="form-control" placeholder="Title, category, reference..." value="{{$filters['search'] ?? ''}}">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Expenses Table -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0 text-danger"><i class="bi bi-receipt"></i> Expenses List</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Category</th>
                                            <th>Title / Particulars</th>
                                            <th>Payment Mode</th>
                                            <th>Reference #</th>
                                            <th class="text-end">Amount</th>
                                            <th>Created By</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($expenses as $exp)
                                            <tr>
                                                <td>{{date('Y-m-d', strtotime($exp->date))}}</td>
                                                <td><span class="badge bg-secondary">{{$exp->category}}</span></td>
                                                <td class="fw-bold">{{$exp->title}}</td>
                                                <td><span class="badge bg-info text-dark">{{$exp->payment_mode}}</span></td>
                                                <td>{{$exp->reference_number ?? '-'}}</td>
                                                <td class="text-end fw-bold text-danger">₹{{number_format($exp->amount, 2)}}</td>
                                                <td><small>{{$exp->creator->first_name ?? 'Admin'}}</small></td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{route('finance.expenses.destroy', $exp->id)}}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this expense record?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">No expenses recorded yet.</td>
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

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{route('finance.expenses.store')}}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="addExpenseModalLabel"><i class="bi bi-plus-circle"></i> Add Outgoing Expense</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                        <input type="text" name="category" class="form-control" list="category_suggestions" placeholder="e.g., Salaries, Electricity, Maintenance, Stationary" required>
                        <datalist id="category_suggestions">
                            <option value="Salaries">
                            <option value="Electricity & Water">
                            <option value="Building Maintenance">
                            <option value="Stationary & Supplies">
                            <option value="Events & Sports">
                            <option value="Software & IT">
                            <option value="Miscellaneous">
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Expense Title / Particulars <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Short title describing the expense" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{date('Y-m-d')}}" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
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
                            <label class="form-label fw-bold">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="Optional bill / ref #">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Additional details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-check-circle"></i> Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
