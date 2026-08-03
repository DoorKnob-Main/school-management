<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\PaymentInterface;
use App\Interfaces\SchoolClassInterface;
use App\Interfaces\SchoolSessionInterface;
use App\Interfaces\SectionInterface;
use App\Interfaces\FeeStructureInterface;
use App\Services\PaymentService;
use App\Http\Requests\CollectFeeRequest;
use App\Traits\SchoolSession;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;

class FeeCollectionController extends Controller
{
    use SchoolSession;

    protected $paymentRepository;
    protected $paymentService;
    protected $schoolSessionRepository;
    protected $schoolClassRepository;
    protected $sectionRepository;
    protected $feeStructureRepository;

    public function __construct(
        PaymentInterface $paymentRepository,
        PaymentService $paymentService,
        SchoolSessionInterface $schoolSessionRepository,
        SchoolClassInterface $schoolClassRepository,
        SectionInterface $sectionRepository,
        FeeStructureInterface $feeStructureRepository
    ) {
        $this->middleware(['can:view payments']);

        $this->paymentRepository       = $paymentRepository;
        $this->paymentService          = $paymentService;
        $this->schoolSessionRepository = $schoolSessionRepository;
        $this->schoolClassRepository   = $schoolClassRepository;
        $this->sectionRepository       = $sectionRepository;
        $this->feeStructureRepository  = $feeStructureRepository;
    }

    public function index(Request $request)
    {
        $current_school_session_id = $this->getSchoolCurrentSession();

        $classes = $this->schoolClassRepository->getAllBySession($current_school_session_id);
        $selected_class_id = $request->get('class_id', $classes->first()->id ?? 0);

        $sections = collect();
        if ($selected_class_id > 0) {
            $sections = $this->sectionRepository->getAllByClassId($selected_class_id);
        }
        $selected_section_id = $request->get('section_id', 0);

        $query = Promotion::with(['student.parent_info', 'student.academic_info', 'schoolClass', 'section'])
            ->where('session_id', $current_school_session_id);

        if ($selected_class_id > 0) {
            $query->where('class_id', $selected_class_id);
        }

        if ($selected_section_id > 0) {
            $query->where('section_id', $selected_section_id);
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('id_card_number', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where(User::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('student.parent_info', function ($pq) use ($search) {
                      $pq->where('father_phone', 'like', "%{$search}%")
                        ->orWhere('mother_phone', 'like', "%{$search}%")
                        ->orWhere('father_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('student.academic_info', function ($aq) use ($search) {
                      $aq->where('board_reg_no', 'like', "%{$search}%");
                  });
            });
        }

        $promotions = $query->get();

        $studentsData = [];
        foreach ($promotions as $promo) {
            if (!$promo->student) continue;

            $summary = $this->paymentRepository->getStudentFeeSummary($promo->student_id, $current_school_session_id);

            $studentsData[] = [
                'promotion'      => $promo,
                'student'        => $promo->student,
                'schoolClass'    => $promo->schoolClass,
                'section'        => $promo->section,
                'parent_info'    => $promo->student->parent_info,
                'academic_info'  => $promo->student->academic_info,
                'fee_structure'  => $summary['fee_structure'],
                'total_fee'      => $summary['total_fee'],
                'paid_amount'    => $summary['paid_amount'],
                'remaining_due'  => $summary['remaining_due'],
                'status'         => $summary['status'],
            ];
        }

        $feeStructures = $this->feeStructureRepository->getAllBySession($current_school_session_id);

        $data = [
            'current_school_session_id' => $current_school_session_id,
            'classes'             => $classes,
            'sections'            => $sections,
            'selected_class_id'   => $selected_class_id,
            'selected_section_id' => $selected_section_id,
            'studentsData'        => $studentsData,
            'feeStructures'       => $feeStructures,
            'search'              => $request->get('search', ''),
        ];

        return view('finance.fee-collection.index', $data);
    }

    public function collect(CollectFeeRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['created_by'] = auth()->id();

            $this->paymentService->collectFee($validated);

            return back()->with('status', 'Fee payment recorded successfully!');
        } catch (Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    public function getHistory(Request $request, $student_id)
    {
        $current_school_session_id = $this->getSchoolCurrentSession();
        $history = $this->paymentRepository->getPaymentHistory($student_id, $current_school_session_id);
        $summary = $this->paymentRepository->getStudentFeeSummary($student_id, $current_school_session_id);

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'history' => $history,
        ]);
    }

    public function receipt($id)
    {
        $payment = $this->paymentRepository->findPaymentById($id);
        $summary = $this->paymentRepository->getStudentFeeSummary($payment->student_id, $payment->session_id);

        return view('finance.fee-collection.receipt', [
            'payment' => $payment,
            'summary' => $summary,
        ]);
    }
}
