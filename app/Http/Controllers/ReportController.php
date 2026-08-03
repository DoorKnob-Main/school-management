<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\TransactionInterface;
use App\Interfaces\PaymentInterface;
use App\Interfaces\ExpenseInterface;
use App\Interfaces\SchoolClassInterface;
use App\Interfaces\SchoolSessionInterface;
use App\Interfaces\SectionInterface;
use App\Traits\SchoolSession;
use App\Models\Promotion;
use App\Models\FeePayment;
use App\Models\Expense;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    use SchoolSession;

    protected $transactionRepository;
    protected $paymentRepository;
    protected $expenseRepository;
    protected $schoolSessionRepository;
    protected $schoolClassRepository;
    protected $sectionRepository;

    public function __construct(
        TransactionInterface $transactionRepository,
        PaymentInterface $paymentRepository,
        ExpenseInterface $expenseRepository,
        SchoolSessionInterface $schoolSessionRepository,
        SchoolClassInterface $schoolClassRepository,
        SectionInterface $sectionRepository
    ) {
        $this->middleware(['can:view reports']);

        $this->transactionRepository  = $transactionRepository;
        $this->paymentRepository       = $paymentRepository;
        $this->expenseRepository       = $expenseRepository;
        $this->schoolSessionRepository = $schoolSessionRepository;
        $this->schoolClassRepository   = $schoolClassRepository;
        $this->sectionRepository       = $sectionRepository;
    }

    public function index(Request $request)
    {
        $current_school_session_id = $this->getSchoolCurrentSession();
        $classes = $this->schoolClassRepository->getAllBySession($current_school_session_id);

        // Date Range Logic (Supports Quick Date Filters & Custom Date Range)
        $quickFilter = $request->get('quick_filter', 'current_session');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($quickFilter != 'custom' && !empty($quickFilter)) {
            switch ($quickFilter) {
                case 'today':
                    $fromDate = Carbon::today()->toDateString();
                    $toDate   = Carbon::today()->toDateString();
                    break;
                case 'yesterday':
                    $fromDate = Carbon::yesterday()->toDateString();
                    $toDate   = Carbon::yesterday()->toDateString();
                    break;
                case 'this_week':
                    $fromDate = Carbon::now()->startOfWeek()->toDateString();
                    $toDate   = Carbon::now()->endOfWeek()->toDateString();
                    break;
                case 'last_week':
                    $fromDate = Carbon::now()->subWeek()->startOfWeek()->toDateString();
                    $toDate   = Carbon::now()->subWeek()->endOfWeek()->toDateString();
                    break;
                case 'this_month':
                    $fromDate = Carbon::now()->startOfMonth()->toDateString();
                    $toDate   = Carbon::now()->endOfMonth()->toDateString();
                    break;
                case 'last_month':
                    $fromDate = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                    $toDate   = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                    break;
                case 'current_session':
                default:
                    $fromDate = null;
                    $toDate   = null;
                    break;
            }
        }

        // Selected Classes (Array of Class IDs for Multi-Class Reports)
        $selectedClassIds = $request->get('class_ids', []);
        if (is_string($selectedClassIds)) {
            $selectedClassIds = explode(',', $selectedClassIds);
        }
        $selectedClassIds = array_map('intval', array_filter($selectedClassIds));

        // Filters for transactions / payments
        $filters = [
            'session_id'   => $current_school_session_id,
            'class_id'     => !empty($selectedClassIds) ? $selectedClassIds : null,
            'payment_mode' => $request->get('payment_mode'),
            'from_date'    => $fromDate,
            'to_date'      => $toDate,
        ];

        // Ledger Analytics Summary
        $analytics = $this->transactionRepository->getAnalyticsSummary($filters);

        // Calculate Outstanding Fees & Pending Students Count across promotions
        $promoQuery = Promotion::where('session_id', $current_school_session_id);
        if (!empty($selectedClassIds)) {
            $promoQuery->whereIn('class_id', $selectedClassIds);
        }
        $promotions = $promoQuery->get();

        $totalOutstanding = 0;
        $pendingStudentsCount = 0;
        $classWiseComparison = [];

        // Build Multi-Class Breakdown Comparison
        foreach ($classes as $cls) {
            if (!empty($selectedClassIds) && !in_array($cls->id, $selectedClassIds)) {
                continue;
            }

            $clsPromos = $promotions->where('class_id', $cls->id);
            $clsTotalFee = 0;
            $clsPaid = 0;
            $clsOutstanding = 0;
            $clsPendingCount = 0;

            foreach ($clsPromos as $cp) {
                $summary = $this->paymentRepository->getStudentFeeSummary($cp->student_id, $current_school_session_id);
                $clsTotalFee += $summary['total_fee'];
                $clsPaid += $summary['paid_amount'];
                $clsOutstanding += $summary['remaining_due'];
                if ($summary['remaining_due'] > 0) {
                    $clsPendingCount++;
                }
            }

            $totalOutstanding += $clsOutstanding;
            $pendingStudentsCount += $clsPendingCount;

            $classWiseComparison[] = [
                'class_id'      => $cls->id,
                'class_name'    => $cls->name,
                'student_count' => $clsPromos->count(),
                'total_fee'     => $clsTotalFee,
                'paid_amount'   => $clsPaid,
                'outstanding'   => $clsOutstanding,
                'pending_count' => $clsPendingCount,
            ];
        }

        // Payment Mode Collection Breakdown
        $paymentModes = ['Cash', 'UPI', 'Cheque', 'Card', 'Bank Transfer', 'Online', 'Other'];
        $paymentModeBreakdown = [];

        $feePaymentQuery = FeePayment::where('session_id', $current_school_session_id);
        if (!empty($selectedClassIds)) {
            $feePaymentQuery->whereIn('class_id', $selectedClassIds);
        }
        if ($fromDate) {
            $feePaymentQuery->whereDate('payment_date', '>=', $fromDate);
        }
        if ($toDate) {
            $feePaymentQuery->whereDate('payment_date', '<=', $toDate);
        }

        $allPayments = $feePaymentQuery->get();
        foreach ($paymentModes as $mode) {
            $sum = $allPayments->where('payment_mode', $mode)->sum('amount');
            $paymentModeBreakdown[$mode] = $sum;
        }

        $data = [
            'current_school_session_id' => $current_school_session_id,
            'classes'              => $classes,
            'selectedClassIds'     => $selectedClassIds,
            'quickFilter'          => $quickFilter,
            'fromDate'             => $fromDate,
            'toDate'               => $toDate,
            'payment_mode'         => $request->get('payment_mode', ''),
            'analytics'            => [
                'total_collection'  => $analytics['total_collection'],
                'total_expenses'    => $analytics['total_expenses'],
                'net_balance'       => $analytics['net_balance'],
                'outstanding_fees'  => $totalOutstanding,
                'transaction_count' => $analytics['transaction_count'],
                'pending_students'  => $pendingStudentsCount,
            ],
            'classWiseComparison'  => $classWiseComparison,
            'paymentModeBreakdown' => $paymentModeBreakdown,
        ];

        return view('finance.reports.index', $data);
    }
}
