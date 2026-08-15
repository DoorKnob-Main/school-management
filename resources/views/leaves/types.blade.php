@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <!-- Sidebar Menu -->
        @include('layouts.left-menu')

        <!-- Main Content Area -->
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10 ps-md-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-tags text-primary me-2"></i>Leave Categories & Policies</h3>
            <p class="text-muted mb-0 small">Configure student leave types (Medical, Casual, Emergency, Family)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Applications
            </a>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                <i class="bi bi-plus-lg me-1"></i> Add Leave Type
            </button>
        </div>
    </div>

    @include('session-messages')

    <div class="row g-3">
        @forelse($types as $type)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-light text-primary border mb-2 font-monospace">{{ $type->code ?: 'TYPE' }}</span>
                            <h5 class="fw-bold text-dark mb-1">{{ $type->name }}</h5>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <button class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#editTypeModal{{ $type->id }}">
                                        <i class="bi bi-pencil me-2"></i> Edit
                                    </button>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('leaves.types.destroy', ['id' => $type->id]) }}" onsubmit="return confirm('Delete this leave category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">{{ $type->description ?: 'No detailed policy description provided.' }}</p>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="badge {{ $type->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $type->is_active ? 'Active' : 'Disabled' }}
                        </span>
                        <small class="text-muted">{{ $type->leaves_count }} applications submitted</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editTypeModal{{ $type->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('leaves.types.update', ['id' => $type->id]) }}" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold">Edit Leave Type</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Leave Type Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $type->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Short Code</label>
                            <input type="text" name="code" class="form-control" value="{{ $type->code }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Description / Rules</label>
                            <textarea name="description" class="form-control" rows="3">{{ $type->description }}</textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editActive{{ $type->id }}" {{ $type->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small" for="editActive{{ $type->id }}">Active for Student Applications</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card shadow-sm border-0 py-5 text-center">
                <i class="bi bi-tags fs-1 text-muted mb-2"></i>
                <h5 class="fw-bold text-dark">No Leave Types Configured</h5>
                <p class="text-muted small mb-3">Add standard leave categories like Medical Leave, Casual Leave, Family Emergency.</p>
                <div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Leave Type
                    </button>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>

<!-- Add Type Modal -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('leaves.types.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Add Leave Type</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Leave Type Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Medical Leave" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Short Code</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. MED">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description / Rules</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief description of leave eligibility and requirements..."></textarea>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="newTypeActive" checked>
                    <label class="form-check-label small" for="newTypeActive">Active for Student Applications</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Create Leave Type</button>
            </div>
        </form>
    </div>
        </div>
    </div>
</div>
@endsection
