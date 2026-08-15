<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BiometricPunchLog;
use App\Models\SchoolSession;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\User;
use App\Models\Promotion;
use App\Services\ReportEngineService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;

class BiometricReportController extends Controller
{
    protected $reportEngine;

    public function __construct(ReportEngineService $reportEngine)
    {
        $this->middleware(['auth', 'biometric.enabled']);
        $this->reportEngine = $reportEngine;
    }

    /**
     * Display Attendance Reports Hub.
     */
    public function index(Request $request)
    {
        $classes = SchoolClass::all();
        $sessions = SchoolSession::all();
        $latestSession = SchoolSession::latest()->first();

        return view('biometric.reports.index', compact('classes', 'sessions', 'latestSession'));
    }

    /**
     * Student Detailed Attendance Report.
     */
    public function studentReport(Request $request)
    {
        $studentId = $request->input('student_id');
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $user = Auth::user();
        if ($user->effective_role === 'student') {
            $studentId = $user->id;
        }

        $student = $studentId ? User::find($studentId) : null;
        $attendances = collect();
        $summary = [
            'total_days' => 0,
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'on_leave' => 0,
            'holiday' => 0,
            'percentage' => 0,
        ];

        if ($student) {
            $attendances = Attendance::where('student_id', $student->id)
                ->where(function($q) {
                    $q->where('course_id', 0)->orWhereNull('course_id');
                })
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at', 'asc')
                ->get();

            $summary['total_days'] = $attendances->count();
            $summary['present'] = $attendances->where('status', 'on')->where('late_minutes', 0)->count() + $attendances->where('status', 'present')->count();
            $summary['late'] = $attendances->where('late_minutes', '>', 0)->count() + $attendances->where('status', 'late')->count();
            $summary['absent'] = $attendances->where('status', 'off')->count() + $attendances->where('status', 'absent')->count();
            $summary['on_leave'] = $attendances->where('status', 'on_leave')->count();
            $summary['holiday'] = $attendances->where('status', 'holiday')->count();

            $effectiveWorking = $summary['total_days'] - $summary['holiday'];
            $attended = $summary['present'] + $summary['late'];
            $summary['percentage'] = $effectiveWorking > 0 ? round(($attended / $effectiveWorking) * 100, 1) : 0;
        }

        $students = User::where('role', 'student')->get();

        if ($request->input('export') === 'pdf' && $student) {
            return $this->exportPdf(
                'Student Attendance Report - ' . $student->first_name . ' ' . $student->last_name,
                view('biometric.reports.pdf.student', compact('student', 'attendances', 'summary', 'startDate', 'endDate'))->render()
            );
        }

        return view('biometric.reports.student', compact('student', 'attendances', 'summary', 'students', 'studentId', 'startDate', 'endDate'));
    }

    /**
     * Class Attendance Report.
     */
    public function classReport(Request $request)
    {
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $date = $request->input('date', Carbon::today()->toDateString());

        $classes = SchoolClass::all();
        $sections = $classId ? Section::where('class_id', $classId)->get() : collect();

        $attendances = collect();
        if ($classId) {
            $query = Attendance::with(['student', 'schoolClass', 'section'])
                ->where(function($q) {
                    $q->where('course_id', 0)->orWhereNull('course_id');
                })
                ->whereDate('created_at', $date)
                ->where('class_id', $classId);

            if ($sectionId) {
                $query->where('section_id', $sectionId);
            }

            $attendances = $query->get();
        }

        if ($request->input('export') === 'pdf' && $classId) {
            $className = SchoolClass::find($classId)->class_name ?? "Class {$classId}";
            return $this->exportPdf(
                "Class Attendance Report - {$className} ({$date})",
                view('biometric.reports.pdf.class', compact('attendances', 'date', 'className'))->render()
            );
        }

        return view('biometric.reports.class', compact('classes', 'sections', 'attendances', 'classId', 'sectionId', 'date'));
    }

    /**
     * Late Arrival Report.
     */
    public function lateReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $classId = $request->input('class_id');

        $query = Attendance::with(['student', 'schoolClass', 'section'])
            ->where(function($q) {
                $q->where('course_id', 0)->orWhereNull('course_id');
            })
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('late_minutes', '>', 0);

        if ($classId) {
            $query->where('class_id', $classId);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(30);
        $classes = SchoolClass::all();

        return view('biometric.reports.late', compact('records', 'classes', 'startDate', 'endDate', 'classId'));
    }

    /**
     * Early Departure Report.
     */
    public function earlyReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $classId = $request->input('class_id');

        $query = Attendance::with(['student', 'schoolClass', 'section'])
            ->where(function($q) {
                $q->where('course_id', 0)->orWhereNull('course_id');
            })
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('early_leave_minutes', '>', 0);

        if ($classId) {
            $query->where('class_id', $classId);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(30);
        $classes = SchoolClass::all();

        return view('biometric.reports.early', compact('records', 'classes', 'startDate', 'endDate', 'classId'));
    }

    /**
     * Missing Checkout Report.
     */
    public function missingCheckoutReport(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $classId = $request->input('class_id');

        $query = Attendance::with(['student', 'schoolClass', 'section'])
            ->where(function($q) {
                $q->where('course_id', 0)->orWhereNull('course_id');
            })
            ->whereDate('created_at', $date)
            ->whereNotNull('in_time')
            ->whereNull('out_time')
            ->whereIn('status', ['on', 'present', 'late']);

        if ($classId) {
            $query->where('class_id', $classId);
        }

        $records = $query->get();
        $classes = SchoolClass::all();

        return view('biometric.reports.missing_checkout', compact('records', 'classes', 'date', 'classId'));
    }

    /**
     * Export attendance report as PDF using ReportEngineService.
     */
    protected function exportPdf(string $title, string $htmlContent)
    {
        return $this->reportEngine->renderReportHtml($title, $htmlContent);
    }
}
