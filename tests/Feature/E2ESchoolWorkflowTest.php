<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\SchoolSession;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Course;
use App\Models\Mark;
use App\Models\FinalMark;
use App\Models\Attendance;
use App\Models\Promotion;
use App\Models\Semester;
use Database\Seeders\DemoSchoolSeeder;

class E2ESchoolWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSchoolSeeder::class);
    }

    /**
     * Test Super Admin login and access to global settings and reports.
     */
    public function test_super_admin_can_access_settings_and_reports()
    {
        $superAdmin = User::where('email', 'demo.superadmin@example.com')->first();
        $this->assertNotNull($superAdmin);

        $response = $this->actingAs($superAdmin)->get('/settings');
        $response->assertStatus(200);

        $reportResponse = $this->actingAs($superAdmin)->get('/reports/base/preview');
        $reportResponse->assertStatus(200);
    }

    /**
     * Test Admin access to user management and academic structure.
     */
    public function test_admin_can_view_classes_and_students()
    {
        $admin = User::where('email', 'demo.admin@example.com')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/classes');
        $response->assertStatus(200);

        $studentResponse = $this->actingAs($admin)->get('/students/view/list');
        $studentResponse->assertStatus(200);
    }

    /**
     * Test Teacher capabilities (viewing assigned classes and entering marks/attendance).
     */
    public function test_teacher_can_view_attendances_and_marks()
    {
        $teacher = User::where('email', 'demo.teacher01@example.com')->first();
        $this->assertNotNull($teacher);

        $attendanceResponse = $this->actingAs($teacher)->get('/attendances');
        $attendanceResponse->assertStatus(200);

        $currSession = \App\Models\SchoolSession::where('session_name', '2026-2027')->first();
        $grading = \App\Models\GradingSystem::where('session_id', $currSession->id)->first();
        $fm = \App\Models\FinalMark::where('session_id', $currSession->id)->where('class_id', $grading->class_id)->where('semester_id', $grading->semester_id)->first();
        $marksResponse = $this->actingAs($teacher)->get("/marks/results?class_id={$fm->class_id}&semester_id={$fm->semester_id}&section_id={$fm->section_id}&course_id={$fm->course_id}");
        $marksResponse->assertStatus(200);
    }

    /**
     * Test Student dashboard and profile access boundaries.
     */
    public function test_student_can_view_own_dashboard_and_marks()
    {
        $student = User::where('email', 'demo.student01@example.com')->first();
        $this->assertNotNull($student);

        $response = $this->actingAs($student)->get('/home');
        $response->assertStatus(200);

        $attendanceResponse = $this->actingAs($student)->get("/students/view/attendance/{$student->id}");
        $attendanceResponse->assertStatus(200);
    }

    /**
     * Test student cannot access administrative settings (Permission & Authorization check).
     */
    public function test_student_cannot_access_admin_settings()
    {
        $student = User::where('email', 'demo.student01@example.com')->first();
        $this->assertNotNull($student);

        $response = $this->actingAs($student)->get('/settings');
        $response->assertStatus(403);
    }

    /**
     * Test student cannot edit other student's sensitive data (IDOR prevention).
     */
    public function test_student_cannot_edit_other_students()
    {
        $student1 = User::where('email', 'demo.student01@example.com')->first();
        $student2 = User::where('email', 'demo.student02@example.com')->first();

        $response = $this->actingAs($student1)->get("/students/edit/{$student2->id}");
        $response->assertStatus(403);
    }

    /**
     * Verify data integrity of seeded academic structure and student promotions.
     */
    public function test_demo_seeded_data_integrity()
    {
        $currSession = SchoolSession::where('session_name', '2026-2027')->first();
        $this->assertNotNull($currSession);

        $promotionsCount = Promotion::where('session_id', $currSession->id)->count();
        $this->assertEquals(70, $promotionsCount);

        $marksCount = Mark::where('session_id', $currSession->id)->count();
        $this->assertGreaterThan(0, $marksCount);

        $finalMarksCount = FinalMark::where('session_id', $currSession->id)->count();
        $this->assertGreaterThan(0, $finalMarksCount);
    }

    /**
     * Test teacher cannot take attendance for unassigned class/section.
     */
    public function test_teacher_cannot_take_attendance_for_unassigned_class()
    {
        $teacher = User::where('email', 'demo.teacher01@example.com')->first();
        $this->assertNotNull($teacher);

        // Class ID 999 or unassigned class ID 99
        $response = $this->actingAs($teacher)->get('/attendances/take?class_id=99&section_id=99&course_id=99');
        $response->assertStatus(404);
    }

    /**
     * Test student cannot view other student's attendance (IDOR check).
     */
    public function test_student_cannot_view_other_student_attendance()
    {
        $student1 = User::where('email', 'demo.student01@example.com')->first();
        $student2 = User::where('email', 'demo.student02@example.com')->first();

        $response = $this->actingAs($student1)->get("/students/view/attendance/{$student2->id}");
        $response->assertStatus(404);
    }

    /**
     * Test student cannot view other student's courses (IDOR check).
     */
    public function test_student_cannot_view_other_student_courses()
    {
        $student1 = User::where('email', 'demo.student01@example.com')->first();
        $student2 = User::where('email', 'demo.student02@example.com')->first();

        $response = $this->actingAs($student1)->get("/courses/student/index/{$student2->id}");
        $response->assertStatus(404);
    }
}
