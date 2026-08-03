<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\FeeStructureInterface;
use App\Interfaces\SchoolClassInterface;
use App\Interfaces\SchoolSessionInterface;
use App\Http\Requests\FeeStructureStoreRequest;
use App\Traits\SchoolSession;
use Illuminate\Http\Request;
use Exception;

class FeeStructureController extends Controller
{
    use SchoolSession;

    protected $feeStructureRepository;
    protected $schoolSessionRepository;
    protected $schoolClassRepository;

    public function __construct(
        FeeStructureInterface $feeStructureRepository,
        SchoolSessionInterface $schoolSessionRepository,
        SchoolClassInterface $schoolClassRepository
    ) {
        $this->middleware(['can:view payments']);

        $this->feeStructureRepository = $feeStructureRepository;
        $this->schoolSessionRepository = $schoolSessionRepository;
        $this->schoolClassRepository   = $schoolClassRepository;
    }

    public function index(Request $request)
    {
        $current_school_session_id = $this->getSchoolCurrentSession();

        $classes = $this->schoolClassRepository->getAllBySession($current_school_session_id);
        $structures = $this->feeStructureRepository->getAllBySession($current_school_session_id);

        $data = [
            'current_school_session_id' => $current_school_session_id,
            'classes'    => $classes,
            'structures' => $structures,
        ];

        return view('finance.fee-structure.index', $data);
    }

    public function store(FeeStructureStoreRequest $request)
    {
        try {
            $this->feeStructureRepository->store($request->validated());
            return back()->with('status', 'Fee structure created successfully!');
        } catch (Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->feeStructureRepository->delete($id);
            return back()->with('status', 'Fee structure deleted successfully!');
        } catch (Exception $e) {
            return back()->withError($e->getMessage());
        }
    }
}
