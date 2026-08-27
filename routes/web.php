<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ExamRuleController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SyllabusController;
use App\Http\Controllers\GradeRuleController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\GradingSystemController;
use App\Http\Controllers\SchoolSessionController;
use App\Http\Controllers\AcademicSettingController;
use App\Http\Controllers\AssignedTeacherController;
use App\Http\Controllers\Auth\UpdatePasswordController;
use App\Http\Controllers\FeeCollectionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\FeeReminderController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FeeComponentTypeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\BiometricEnrollmentController;
use App\Http\Controllers\BiometricAttendanceController;
use App\Http\Controllers\StudentLeaveController;
use App\Http\Controllers\BiometricReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {

    Route::prefix('school')->name('school.')->group(function () {
        Route::post('session/create', [SchoolSessionController::class, 'store'])->name('session.store');
        Route::post('session/browse', [SchoolSessionController::class, 'browse'])->name('session.browse');

        Route::post('semester/create', [SemesterController::class, 'store'])->name('semester.create');
        Route::post('final-marks-submission-status/update', [AcademicSettingController::class, 'updateFinalMarksSubmissionStatus'])->name('final.marks.submission.status.update');

        Route::post('attendance/type/update', [AcademicSettingController::class, 'updateAttendanceType'])->name('attendance.type.update');

        // Class
        Route::post('class/create', [SchoolClassController::class, 'store'])->name('class.create');
        Route::post('class/update', [SchoolClassController::class, 'update'])->name('class.update');

        // Sections
        Route::post('section/create', [SectionController::class, 'store'])->name('section.create');
        Route::post('section/update', [SectionController::class, 'update'])->name('section.update');

        // Courses
        Route::post('course/create', [CourseController::class, 'store'])->name('course.create');
        Route::post('course/update', [CourseController::class, 'update'])->name('course.update');

        // Teacher
        Route::post('teacher/create', [UserController::class, 'storeTeacher'])->name('teacher.create');
        Route::post('teacher/update', [UserController::class, 'updateTeacher'])->name('teacher.update');
        Route::post('teacher/assign', [AssignedTeacherController::class, 'store'])->name('teacher.assign');

        // Student
        Route::post('student/create', [UserController::class, 'storeStudent'])->name('student.create');
        Route::post('student/update', [UserController::class, 'updateStudent'])->name('student.update');
    });


    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Attendance
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendances/view', [AttendanceController::class, 'show'])->name('attendance.list.show');
    Route::get('/attendances/take', [AttendanceController::class, 'create'])->name('attendance.create.show');
    Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store');

    // Classes and sections
    Route::get('/classes', [SchoolClassController::class, 'index'])->name('class.index');
    Route::get('/class/edit/{id}', [SchoolClassController::class, 'edit'])->name('class.edit');
    Route::get('/sections', [SectionController::class, 'getByClassId'])->name('get.sections.courses.by.classId');
    Route::get('/section/edit/{id}', [SectionController::class, 'edit'])->name('section.edit');

    // Teachers
    Route::get('/teachers/add', function () {
        return view('teachers.add');
    })->name('teacher.create.show');
    Route::get('/teachers/edit/{id}', [UserController::class, 'editTeacher'])->name('teacher.edit.show');
    Route::get('/teachers/view/list', [UserController::class, 'getTeacherList'])->name('teacher.list.show');
    Route::get('/teachers/view/profile/{id}', [UserController::class, 'showTeacherProfile'])->name('teacher.profile.show');

    //Students
    Route::get('/students/add', [UserController::class, 'createStudent'])->name('student.create.show');
    Route::get('/students/edit/{id}', [UserController::class, 'editStudent'])->name('student.edit.show');
    Route::get('/students/view/list', [UserController::class, 'getStudentList'])->name('student.list.show');
    Route::get('/students/view/profile/{id}', [UserController::class, 'showStudentProfile'])->name('student.profile.show');
    Route::get('/students/view/attendance/{id}', [AttendanceController::class, 'showStudentAttendance'])->name('student.attendance.show');

    // Marks
    Route::get('/marks/create', [MarkController::class, 'create'])->name('course.mark.create');
    Route::post('/marks/store', [MarkController::class, 'store'])->name('course.mark.store');
    Route::get('/marks/results', [MarkController::class, 'index'])->name('course.mark.list.show');
    // Route::get('/marks/view', function () {
    //     return view('marks.view');
    // });
    Route::get('/marks/view', [MarkController::class, 'showCourseMark'])->name('course.mark.show');
    Route::get('/marks/final/submit', [MarkController::class, 'showFinalMark'])->name('course.final.mark.submit.show');
    Route::post('/marks/final/submit', [MarkController::class, 'storeFinalMark'])->name('course.final.mark.submit.store');

    // Exams
    Route::get('/exams/view', [ExamController::class, 'index'])->name('exam.list.show');
    // Route::get('/exams/view/history', function () {
    //     return view('exams.history');
    // });
    Route::post('/exams/create', [ExamController::class, 'store'])->name('exam.create');
    // Route::post('/exams/delete', [ExamController::class, 'delete'])->name('exam.delete');
    Route::get('/exams/create', [ExamController::class, 'create'])->name('exam.create.show');
    Route::get('/exams/add-rule', [ExamRuleController::class, 'create'])->name('exam.rule.create');
    Route::post('/exams/add-rule', [ExamRuleController::class, 'store'])->name('exam.rule.store');
    Route::get('/exams/edit-rule', [ExamRuleController::class, 'edit'])->name('exam.rule.edit');
    Route::post('/exams/edit-rule', [ExamRuleController::class, 'update'])->name('exam.rule.update');
    Route::get('/exams/view-rule', [ExamRuleController::class, 'index'])->name('exam.rule.show');
    Route::get('/exams/grade/create', [GradingSystemController::class, 'create'])->name('exam.grade.system.create');
    Route::post('/exams/grade/create', [GradingSystemController::class, 'store'])->name('exam.grade.system.store');
    Route::get('/exams/grade/view', [GradingSystemController::class, 'index'])->name('exam.grade.system.index');
    Route::get('/exams/grade/add-rule', [GradeRuleController::class, 'create'])->name('exam.grade.system.rule.create');
    Route::post('/exams/grade/add-rule', [GradeRuleController::class, 'store'])->name('exam.grade.system.rule.store');
    Route::get('/exams/grade/view-rules', [GradeRuleController::class, 'index'])->name('exam.grade.system.rule.show');
    Route::post('/exams/grade/delete-rule', [GradeRuleController::class, 'destroy'])->name('exam.grade.system.rule.delete');

    // Promotions
    Route::get('/promotions/index', [PromotionController::class, 'index'])->name('promotions.index');
    Route::get('/promotions/promote', [PromotionController::class, 'create'])->name('promotions.create');
    Route::post('/promotions/promote', [PromotionController::class, 'store'])->name('promotions.store');

    // Academic settings
    Route::get('/academics/settings', [AcademicSettingController::class, 'index']);

    // Calendar events
    Route::get('calendar-event', [EventController::class, 'index'])->name('events.show');
    Route::post('calendar-crud-ajax', [EventController::class, 'calendarEvents'])->name('events.crud');

    // Routines
    Route::get('/routine/create', [RoutineController::class, 'create'])->name('section.routine.create');
    Route::get('/routine/view', [RoutineController::class, 'show'])->name('section.routine.show');
    Route::post('/routine/store', [RoutineController::class, 'store'])->name('section.routine.store');

    // Syllabus
    Route::get('/syllabus/create', [SyllabusController::class, 'create'])->name('class.syllabus.create');
    Route::post('/syllabus/create', [SyllabusController::class, 'store'])->name('syllabus.store');
    Route::get('/syllabus/index', [SyllabusController::class, 'index'])->name('course.syllabus.index');

    // Notices
    Route::get('/notice/create', [NoticeController::class, 'create'])->name('notice.create');
    Route::post('/notice/create', [NoticeController::class, 'store'])->name('notice.store');

    // Courses
    Route::get('courses/teacher/index', [AssignedTeacherController::class, 'getTeacherCourses'])->name('course.teacher.list.show');
    Route::get('courses/student/index/{student_id}', [CourseController::class, 'getStudentCourses'])->name('course.student.list.show');
    Route::get('course/edit/{id}', [CourseController::class, 'edit'])->name('course.edit');

    // Assignment
    Route::get('courses/assignments/index', [AssignmentController::class, 'getCourseAssignments'])->name('assignment.list.show');
    Route::get('courses/assignments/create', [AssignmentController::class, 'create'])->name('assignment.create');
    Route::post('courses/assignments/create', [AssignmentController::class, 'store'])->name('assignment.store');

    // Update password
    Route::get('password/edit', [UpdatePasswordController::class, 'edit'])->name('password.edit');
    Route::post('password/edit', [UpdatePasswordController::class, 'update'])->name('password.update');

    // Finance Module
    Route::prefix('finance')->name('finance.')->group(function () {
        // Fee Collection & Receipts
        Route::get('/fee-collection', [FeeCollectionController::class, 'index'])->name('fee-collection.index');
        Route::post('/fee-collection/collect', [FeeCollectionController::class, 'collect'])->name('fee-collection.collect');
        Route::get('/fee-collection/history/{student_id}', [FeeCollectionController::class, 'getHistory'])->name('fee-collection.history');
        Route::get('/fee-collection/receipt/{id}', [FeeCollectionController::class, 'receipt'])->name('fee-collection.receipt');
        Route::get('/fee-collection/receipt/{id}/pdf', [FeeCollectionController::class, 'receiptPdf'])->name('fee-collection.receipt-pdf');

        // Fee Structure Configuration
        Route::get('/fee-structure', [FeeStructureController::class, 'index'])->name('fee-structure.index');
        Route::post('/fee-structure/store', [FeeStructureController::class, 'store'])->name('fee-structure.store');
        Route::delete('/fee-structure/destroy/{id}', [FeeStructureController::class, 'destroy'])->name('fee-structure.destroy');

        // Fee Components (admin-configurable breakup: Tuition, GST, Transport, etc.)
        Route::get('/fee-component-types', [FeeComponentTypeController::class, 'index'])->name('fee-component-types.index');
        Route::post('/fee-component-types/store', [FeeComponentTypeController::class, 'store'])->name('fee-component-types.store');
        Route::put('/fee-component-types/{id}', [FeeComponentTypeController::class, 'update'])->name('fee-component-types.update');
        Route::delete('/fee-component-types/{id}', [FeeComponentTypeController::class, 'destroy'])->name('fee-component-types.destroy');

        // Transactions Ledger
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses/store', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/destroy/{id}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('/expenses/voucher/{id}', [ExpenseController::class, 'voucher'])->name('expenses.voucher');
        Route::get('/expenses/voucher/{id}/pdf', [ExpenseController::class, 'voucherPdf'])->name('expenses.voucher-pdf');

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('/reports/preview', [ReportController::class, 'previewPdf'])->name('reports.preview');

        // Due Students Report
        Route::get('/reports/due-students', [ReportController::class, 'dueStudents'])->name('reports.due-students.index');
        Route::get('/reports/due-students/pdf', [ReportController::class, 'dueStudentsPdf'])->name('reports.due-students.pdf');
        Route::get('/reports/due-students/preview', [ReportController::class, 'dueStudentsPreview'])->name('reports.due-students.preview');

        // Fee Reminder
        Route::get('/fee-reminder', [FeeReminderController::class, 'index'])->name('fee-reminder.index');
        Route::post('/fee-reminder/send', [FeeReminderController::class, 'send'])->name('fee-reminder.send');
    });

    // Reusable Report Engine Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/base/preview', [\App\Http\Controllers\ReportEngineController::class, 'previewBaseReport'])->name('base.preview');
        Route::get('/base/pdf', [\App\Http\Controllers\ReportEngineController::class, 'downloadBaseReportPdf'])->name('base.pdf');
        Route::get('/base/stream', [\App\Http\Controllers\ReportEngineController::class, 'streamBaseReportPdf'])->name('base.stream');
        Route::get('/settings/preview', [\App\Http\Controllers\ReportEngineController::class, 'settingsPreview'])->name('settings.preview');
    });

    // White-Label Settings Module (Super Admin Only)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/update', [SettingController::class, 'update'])->name('update');
        Route::get('/export-json', [SettingController::class, 'exportJson'])->name('export');
        Route::post('/import-json', [SettingController::class, 'importJson'])->name('import');
    });

    // Super Admin Role Impersonation / Switching
    Route::prefix('super-admin')->name('super-admin.')->group(function () {
        Route::post('/switch-role', [SuperAdminController::class, 'switchRole'])->name('switch-role');
        Route::post('/exit-impersonation', [SuperAdminController::class, 'exitImpersonation'])->name('exit-impersonation');
    });

    // Biometric Attendance Module
    Route::prefix('biometric')->name('biometric.')->group(function () {
        // Dashboard & Today's Attendance
        Route::get('/dashboard', [BiometricAttendanceController::class, 'dashboard'])->name('dashboard');
        Route::get('/today', [BiometricAttendanceController::class, 'today'])->name('today');
        Route::get('/punches', [BiometricAttendanceController::class, 'punchLogs'])->name('punches');
        Route::post('/correct', [BiometricAttendanceController::class, 'correctAttendance'])->name('correct');
        Route::post('/recalculate', [BiometricAttendanceController::class, 'recalculate'])->name('recalculate');

        // Settings (School timing, windows, etc.)
        Route::get('/settings', [BiometricAttendanceController::class, 'settings'])->name('settings.index');
        Route::post('/settings/update', [BiometricAttendanceController::class, 'updateSettings'])->name('settings.update');

        // Devices Management
        Route::get('/devices', [BiometricDeviceController::class, 'index'])->name('devices.index');
        Route::post('/devices/store', [BiometricDeviceController::class, 'store'])->name('devices.store');
        Route::put('/devices/update/{id}', [BiometricDeviceController::class, 'update'])->name('devices.update');
        Route::delete('/devices/destroy/{id}', [BiometricDeviceController::class, 'destroy'])->name('devices.destroy');
        Route::post('/devices/test/{id}', [BiometricDeviceController::class, 'testConnection'])->name('devices.test');
        Route::post('/devices/sync/{id}', [BiometricDeviceController::class, 'syncNow'])->name('devices.sync');
        Route::post('/devices/sync-all', [BiometricDeviceController::class, 'syncAll'])->name('devices.sync-all');
        Route::post('/devices/sync-time/{id}', [BiometricDeviceController::class, 'syncTime'])->name('devices.sync-time');
        Route::post('/devices/unlock/{id}', [BiometricDeviceController::class, 'unlockDoor'])->name('devices.unlock');
        Route::post('/devices/clear-logs/{id}', [BiometricDeviceController::class, 'clearLogs'])->name('devices.clear-logs');
        Route::post('/devices/simulate/{id}', [BiometricDeviceController::class, 'simulatePunches'])->name('devices.simulate');
        Route::get('/devices/users/{id}', [BiometricDeviceController::class, 'fetchDeviceUsers'])->name('devices.users');
        Route::post('/devices/delete-user/{id}', [BiometricDeviceController::class, 'deleteDeviceUser'])->name('devices.delete-user');

        // Student Device Enrollment & Mappings
        Route::get('/enrollment', [BiometricEnrollmentController::class, 'index'])->name('enrollment.index');
        Route::post('/enrollment/enroll', [BiometricEnrollmentController::class, 'enroll'])->name('enrollment.enroll');
        Route::delete('/enrollment/unenroll/{id}', [BiometricEnrollmentController::class, 'unenroll'])->name('enrollment.unenroll');
        Route::get('/enrollment/unmapped', [BiometricEnrollmentController::class, 'unmapped'])->name('enrollment.unmapped');
        Route::post('/enrollment/map-user', [BiometricEnrollmentController::class, 'mapUser'])->name('enrollment.map-user');

        // Biometric Attendance Reports
        Route::get('/reports', [BiometricReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/student', [BiometricReportController::class, 'studentReport'])->name('reports.student');
        Route::get('/reports/class', [BiometricReportController::class, 'classReport'])->name('reports.class');
        Route::get('/reports/late', [BiometricReportController::class, 'lateReport'])->name('reports.late');
        Route::get('/reports/early', [BiometricReportController::class, 'earlyReport'])->name('reports.early');
        Route::get('/reports/missing-checkout', [BiometricReportController::class, 'missingCheckoutReport'])->name('reports.missing-checkout');
    });

    // Leave Management Module
    Route::prefix('leaves')->name('leaves.')->group(function () {
        Route::get('/', [StudentLeaveController::class, 'index'])->name('index');
        Route::post('/apply', [StudentLeaveController::class, 'store'])->name('apply');
        Route::post('/approve/{id}', [StudentLeaveController::class, 'approve'])->name('approve');
        Route::post('/reject/{id}', [StudentLeaveController::class, 'reject'])->name('reject');
        Route::get('/my-leaves', [StudentLeaveController::class, 'myLeaves'])->name('my-leaves');

        // Leave Types (Admin Only)
        Route::get('/types', [StudentLeaveController::class, 'typesIndex'])->name('types');
        Route::post('/types/store', [StudentLeaveController::class, 'storeType'])->name('types.store');
        Route::put('/types/update/{id}', [StudentLeaveController::class, 'updateType'])->name('types.update');
        Route::delete('/types/destroy/{id}', [StudentLeaveController::class, 'destroyType'])->name('types.destroy');
    });
});
