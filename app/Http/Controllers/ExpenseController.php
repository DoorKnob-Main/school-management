<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\ExpenseInterface;
use App\Services\ExpenseService;
use App\Http\Requests\ExpenseStoreRequest;
use App\Traits\SchoolSession;
use App\Interfaces\SchoolSessionInterface;
use Illuminate\Http\Request;
use Exception;

class ExpenseController extends Controller
{
    use SchoolSession;

    protected $expenseRepository;
    protected $expenseService;
    protected $schoolSessionRepository;

    public function __construct(
        ExpenseInterface $expenseRepository,
        ExpenseService $expenseService,
        SchoolSessionInterface $schoolSessionRepository
    ) {
        $this->middleware(['can:manage expenses']);

        $this->expenseRepository       = $expenseRepository;
        $this->expenseService          = $expenseService;
        $this->schoolSessionRepository = $schoolSessionRepository;
    }

    public function index(Request $request)
    {
        $current_school_session_id = $this->getSchoolCurrentSession();

        $filters = [
            'category'     => $request->get('category'),
            'payment_mode' => $request->get('payment_mode'),
            'from_date'    => $request->get('from_date'),
            'to_date'      => $request->get('to_date'),
            'search'       => $request->get('search'),
        ];

        $expenses = $this->expenseRepository->getAll($filters);
        $categories = $this->expenseRepository->getCategories();
        $totalAmount = $expenses->sum('amount');

        $data = [
            'current_school_session_id' => $current_school_session_id,
            'expenses'    => $expenses,
            'categories'  => $categories,
            'filters'     => $filters,
            'totalAmount' => $totalAmount,
        ];

        return view('finance.expenses.index', $data);
    }

    public function store(ExpenseStoreRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['created_by'] = auth()->id();

            $this->expenseService->createExpense($validated);

            return back()->with('status', 'Expense recorded successfully!');
        } catch (Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->expenseRepository->delete($id);
            return back()->with('status', 'Expense deleted successfully!');
        } catch (Exception $e) {
            return back()->withError($e->getMessage());
        }
    }
}
