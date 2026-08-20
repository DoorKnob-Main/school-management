<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Interfaces\UserInterface;
use App\Interfaces\SchoolClassInterface;
use App\Interfaces\SchoolSessionInterface;
use App\Interfaces\AcademicSettingInterface;
use App\Http\Requests\AttendanceStoreRequest;
use App\Interfaces\SectionInterface;
use App\Repositories\AttendanceRepository;
use App\Repositories\CourseRepository;
use App\Repositories\AssignedTeacherRepository;
use App\Traits\SchoolSession;
use App\Traits\AssignedTeacherCheck;

class AttendanceController extends Controller
{
    use SchoolSession, AssignedTeacherCheck;
    protected $academicSettingRepository;
    protected $schoolSessionRepository;
    protected $schoolClassRepository;
    protected $sectionRepository;
    protected $userRepository;

    public function __construct(
        UserInterface $userRepository,
        AcademicSettingInterface $academicSettingRepository,
        SchoolSessionInterface $schoolSessionRepository,
        SchoolClassInterface $schoolClassRepository,
        SectionInterface $sectionRepository
    ) {
        $this->middleware(['can:view attendances']);

        $this->userRepository = $userRepository;
        $this->academicSettingRepository = $academicSettingRepository;
        $this->schoolSessionRepository = $schoolSessionRepository;
        $this->schoolClassRepository = $schoolClassRepository;
        $this->sectionRepository = $sectionRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $academic_setting = $this->academicSettingRepository->getAcademicSetting();

        $current_school_session_id = $this->getSchoolCurrentSession();

        // Each class carries its OWN sections/courses (scoped via relations) so
        // the launcher shows the real hierarchy instead of every section under
        // every class.
        $classes = $this->schoolClassRepository->getAllWithCoursesBySession($current_school_session_id);
        $classes->load('sections');

        if (auth()->user()->effective_role == 'teacher') {
            $assignedTeacherRepository = new AssignedTeacherRepository();
            $assignedTeacherCourses = $assignedTeacherRepository->getTeacherCourses($current_school_session_id, auth()->user()->id, 0);
            $assignedClassIds   = $assignedTeacherCourses->pluck('class_id')->unique()->toArray();
            $assignedSectionIds = $assignedTeacherCourses->pluck('section_id')->unique()->toArray();
            $assignedCourseIds  = $assignedTeacherCourses->pluck('course_id')->unique()->toArray();

            $classes = $classes->filter(function ($c) use ($assignedClassIds) {
                return in_array($c->id, $assignedClassIds);
            })->values();

            foreach ($classes as $c) {
                $c->setRelation('sections', $c->sections->filter(function ($s) use ($assignedSectionIds) {
                    return in_array($s->id, $assignedSectionIds);
                })->values());
                $c->setRelation('courses', $c->courses->filter(function ($crs) use ($assignedCourseIds) {
                    return in_array($crs->id, $assignedCourseIds);
                })->values());
            }
        }

        $data = [
            'academic_setting' => $academic_setting,
            'classes'          => $classes,
            'today'            => Carbon::today()->toDateString(),
        ];

        return view('attendances.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if($request->query('class_id') == null){
            return abort(404);
        }
        try{
            $current_school_session_id = $this->getSchoolCurrentSession();
            $this->checkIfLoggedInUserIsAssignedTeacher($request, $current_school_session_id);

            $academic_setting = $this->academicSettingRepository->getAcademicSetting();

            $class_id = $request->query('class_id');
            $section_id = $request->query('section_id', 0);
            $course_id = $request->query('course_id', 0);

            // Selected date (default today, never the future).
            $attendance_date = $this->resolveDate($request->query('date'));

            $student_list = $this->userRepository->getAllStudents($current_school_session_id, $class_id, $section_id);

            $school_class = $this->schoolClassRepository->findById($class_id);
            $school_section = $section_id ? $this->sectionRepository->findById($section_id) : null;

            $attendanceRepository = new AttendanceRepository();

            if($academic_setting->attendance_type == 'section') {
                $existing = $attendanceRepository->getSectionAttendance($class_id, $section_id, $current_school_session_id, $attendance_date);
            } else {
                $existing = $attendanceRepository->getCourseAttendance($class_id, $course_id, $current_school_session_id, $attendance_date);
            }

            // Map student_id => status for the selected date, so the form
            // pre-selects current marks and stays editable.
            $existing_attendance = $existing->pluck('status', 'student_id')->toArray();

            $data = [
                'current_school_session_id' => $current_school_session_id,
                'academic_setting'  => $academic_setting,
                'student_list'      => $student_list,
                'school_class'      => $school_class,
                'school_section'    => $school_section,
                'course_id'         => $course_id,
                'section_id'        => $section_id,
                'attendance_date'   => $attendance_date,
                'attendance_count'  => $existing->count(),
                'existing_attendance' => $existing_attendance,
                'statuses'          => AttendanceRepository::MANUAL_STATUSES,
            ];

            return view('attendances.take', $data);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Normalize an incoming date string to Y-m-d, defaulting to today and
     * never allowing a future date.
     */
    private function resolveDate($date)
    {
        try {
            $parsed = $date ? Carbon::parse($date) : Carbon::today();
        } catch (\Exception $e) {
            $parsed = Carbon::today();
        }
        if ($parsed->gt(Carbon::today())) {
            $parsed = Carbon::today();
        }
        return $parsed->toDateString();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\AttendanceStoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AttendanceStoreRequest $request)
    {
        try {
            $current_school_session_id = $this->getSchoolCurrentSession();
            $this->checkIfLoggedInUserIsAssignedTeacher($request, $current_school_session_id);

            $latestSession = $this->schoolSessionRepository->getLatestSession();
            if ($request->session_id != $latestSession->id && !auth()->user()->isAdminOrSuperAdmin()) {
                return back()->withError('Previous academic sessions are read-only and cannot be modified.');
            }

            $attendanceRepository = new AttendanceRepository();
            $attendanceRepository->saveAttendance($request->validated());

            return back()->with('status', 'Attendance save was successful!');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if($request->query('class_id') == null){
            return abort(404);
        }

        $current_school_session_id = $this->getSchoolCurrentSession();
        $this->checkIfLoggedInUserIsAssignedTeacher($request, $current_school_session_id);

        $class_id = $request->query('class_id');
        $section_id = $request->query('section_id', 0);
        $course_id = $request->query('course_id', 0);
        $attendance_date = $this->resolveDate($request->query('date'));

        $attendanceRepository = new AttendanceRepository();

        try {
            $academic_setting = $this->academicSettingRepository->getAcademicSetting();
            if($academic_setting->attendance_type == 'section') {
                $attendances = $attendanceRepository->getSectionAttendance($class_id, $section_id, $current_school_session_id, $attendance_date);
            } else {
                $attendances = $attendanceRepository->getCourseAttendance($class_id, $course_id, $current_school_session_id, $attendance_date);
            }

            // Live summary of the selected day.
            $presentLike = ['present', 'on', 'late'];
            $summary = [
                'total'    => $attendances->count(),
                'present'  => $attendances->whereIn('status', ['present', 'on'])->count(),
                'late'     => $attendances->where('status', 'late')->count(),
                'on_leave' => $attendances->where('status', 'on_leave')->count(),
                'absent'   => $attendances->filter(function ($a) use ($presentLike) {
                    return !in_array($a->status, array_merge($presentLike, ['on_leave']));
                })->count(),
            ];

            $data = [
                'attendances'     => $attendances,
                'attendance_date' => $attendance_date,
                'summary'         => $summary,
                'school_class'    => $this->schoolClassRepository->findById($class_id),
                'school_section'  => $section_id ? $this->sectionRepository->findById($section_id) : null,
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'course_id'       => $course_id,
            ];

            return view('attendances.view', $data);
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    public function showStudentAttendance($id) {
        if(auth()->user()->effective_role == "student" && auth()->user()->id != $id) {
            return abort(404);
        }
        $current_school_session_id = $this->getSchoolCurrentSession();

        $attendanceRepository = new AttendanceRepository();
        $attendances = $attendanceRepository->getStudentAttendance($current_school_session_id, $id);
        $student = $this->userRepository->findStudent($id);

        $data = [
            'attendances'   => $attendances,
            'student'       => $student,
        ];

        return view('attendances.attendance', $data);
    }
}
