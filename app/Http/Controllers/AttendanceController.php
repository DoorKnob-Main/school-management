<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
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

        $classes_and_sections = $this->schoolClassRepository->getClassesAndSections($current_school_session_id);
        $courseRepository = new CourseRepository();
        $courses = $courseRepository->getAll($current_school_session_id);

        if (auth()->user()->effective_role == 'teacher') {
            $assignedTeacherRepository = new AssignedTeacherRepository();
            $assignedTeacherCourses = $assignedTeacherRepository->getTeacherCourses($current_school_session_id, auth()->user()->id, 0);
            $assignedClassIds = $assignedTeacherCourses->pluck('class_id')->unique()->toArray();
            $assignedCourseIds = $assignedTeacherCourses->pluck('course_id')->unique()->toArray();

            $classes_and_sections['school_classes'] = $classes_and_sections['school_classes']->filter(function($c) use ($assignedClassIds) {
                return in_array($c->id, $assignedClassIds);
            });

            $courses = $courses->filter(function($crs) use ($assignedCourseIds) {
                return in_array($crs->id, $assignedCourseIds);
            });
        }

        $data = [
            'academic_setting'      => $academic_setting,
            'classes_and_sections'  => $classes_and_sections,
            'courses'               => $courses,
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
            $course_id = $request->query('course_id');

            $student_list = $this->userRepository->getAllStudents($current_school_session_id, $class_id, $section_id);

            $school_class = $this->schoolClassRepository->findById($class_id);
            $school_section = $this->sectionRepository->findById($section_id);

            $attendanceRepository = new AttendanceRepository();

            if($academic_setting->attendance_type == 'section') {
                $attendance_count = $attendanceRepository->getSectionAttendance($class_id, $section_id, $current_school_session_id)->count();
            } else {
                $attendance_count = $attendanceRepository->getCourseAttendance($class_id, $course_id, $current_school_session_id)->count();
            }

            $data = [
                'current_school_session_id' => $current_school_session_id,
                'academic_setting'  => $academic_setting,
                'student_list'      => $student_list,
                'school_class'      => $school_class,
                'school_section'    => $school_section,
                'attendance_count'  => $attendance_count,
            ];

            return view('attendances.take', $data);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
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
        $section_id = $request->query('section_id');
        $course_id = $request->query('course_id');

        $attendanceRepository = new AttendanceRepository();

        try {
            $academic_setting = $this->academicSettingRepository->getAcademicSetting();
            if($academic_setting->attendance_type == 'section') {
                $attendances = $attendanceRepository->getSectionAttendance($class_id, $section_id, $current_school_session_id);
            } else {
                $attendances = $attendanceRepository->getCourseAttendance($class_id, $course_id, $current_school_session_id);
            }
            $data = ['attendances' => $attendances];
            
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
