<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\TransactionInterface;
use App\Interfaces\SchoolClassInterface;
use App\Interfaces\SchoolSessionInterface;
use App\Interfaces\SectionInterface;
use App\Traits\SchoolSession;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use SchoolSession;

    protected $transactionRepository;
    protected $schoolSessionRepository;
    protected $schoolClassRepository;
    protected $sectionRepository;

    public function __construct(
        TransactionInterface $transactionRepository,
        SchoolSessionInterface $schoolSessionRepository,
        SchoolClassInterface $schoolClassRepository,
        SectionInterface $sectionRepository
    ) {
        $this->middleware(['can:view transactions']);

        $this->transactionRepository  = $transactionRepository;
        $this->schoolSessionRepository = $schoolSessionRepository;
        $this->schoolClassRepository   = $schoolClassRepository;
        $this->sectionRepository       = $sectionRepository;
    }

    public function index(Request $request)
    {
        $current_school_session_id = $this->getSchoolCurrentSession();

        $classes = $this->schoolClassRepository->getAllBySession($current_school_session_id);

        $filters = [
            'session_id'       => $current_school_session_id,
            'class_id'         => $request->get('class_id'),
            'section_id'       => $request->get('section_id'),
            'transaction_type' => $request->get('transaction_type'),
            'payment_mode'     => $request->get('payment_mode'),
            'from_date'        => $request->get('from_date'),
            'to_date'          => $request->get('to_date'),
            'search'           => $request->get('search'),
        ];

        $transactions = $this->transactionRepository->getFilteredTransactions($filters);
        $summary = $this->transactionRepository->getAnalyticsSummary($filters);

        $sections = collect();
        if ($request->filled('class_id')) {
            $sections = $this->sectionRepository->getAllByClassId($request->get('class_id'));
        }

        $data = [
            'current_school_session_id' => $current_school_session_id,
            'classes'      => $classes,
            'sections'     => $sections,
            'filters'      => $filters,
            'transactions' => $transactions,
            'summary'      => $summary,
        ];

        return view('finance.transactions.index', $data);
    }
}
