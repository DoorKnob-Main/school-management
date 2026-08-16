<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveType;
use App\Models\StudentLeave;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Support\Facades\Auth;

class StudentLeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->middleware(['auth']);
        $this->leaveService = $leaveService;
    }

    /**
     * Display a listing of leave applications (Admin/Teacher).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->effective_role === 'student') {
            return $this->myLeaves($request);
        }

        $status = $request->input('status', 'all');
        $studentId = $request->input('student_id');

        $query = StudentLeave::with(['student', 'leaveType', 'approver'])->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $leaves = $query->paginate(20);
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $students = User::where('role', 'student')->get();

        return view('leaves.index', compact('leaves', 'leaveTypes', 'students', 'status', 'studentId'));
    }

    /**
     * Apply for leave (Admin or Student).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $studentId = $user->effective_role === 'student' ? $user->id : $request->input('student_id');

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        if (!$studentId) {
            return redirect()->back()->with('error', 'Please select a student.');
        }

        $leave = $this->leaveService->applyLeave($validated, (int)$studentId);

        // If admin applies on behalf of student, auto-approve optionally
        if ($user->isAdminOrSuperAdmin() && $request->has('auto_approve')) {
            $this->leaveService->approveLeave($leave, $user->id);
        }

        return redirect()->back()->with('success', 'Leave application submitted successfully.');
    }

    /**
     * Approve leave request.
     */
    public function approve($id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin() && $user->effective_role !== 'teacher') {
            abort(403, 'Unauthorized action.');
        }

        $leave = StudentLeave::findOrFail($id);
        $this->leaveService->approveLeave($leave, $user->id);

        return redirect()->back()->with('success', 'Leave application approved.');
    }

    /**
     * Reject leave request.
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin() && $user->effective_role !== 'teacher') {
            abort(403, 'Unauthorized action.');
        }

        $leave = StudentLeave::findOrFail($id);
        $reason = $request->input('rejection_reason');

        $this->leaveService->rejectLeave($leave, $user->id, $reason);

        return redirect()->back()->with('success', 'Leave application rejected.');
    }

    /**
     * Student's own leave view.
     */
    public function myLeaves(Request $request)
    {
        $user = Auth::user();
        $leaves = StudentLeave::with(['leaveType', 'approver'])
            ->where('student_id', $user->id)
            ->latest()
            ->paginate(15);

        $leaveTypes = LeaveType::where('is_active', true)->get();

        return view('leaves.my_leaves', compact('leaves', 'leaveTypes'));
    }

    /**
     * Leave Types Management (Admin Only).
     */
    public function typesIndex()
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $types = LeaveType::withCount('leaves')->get();
        return view('leaves.types', compact('types'));
    }

    public function storeType(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->has('is_active');
        LeaveType::create($validated);

        return redirect()->route('leaves.types')->with('success', 'Leave type created.');
    }

    public function updateType(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $type = LeaveType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $type->update($validated);

        return redirect()->route('leaves.types')->with('success', 'Leave type updated.');
    }

    public function destroyType($id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $type = LeaveType::findOrFail($id);
        $type->delete();

        return redirect()->route('leaves.types')->with('success', 'Leave type deleted.');
    }
}
