<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\SchoolSession;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Routine;
use App\Models\AssignedTeacher;
use Database\Seeders\DemoSchoolSeeder;

class RoutineViewAndPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSchoolSeeder::class);
    }

    /**
     * Test routine view renders with teacher name, course name, time, and print components.
     */
    public function test_user_can_view_routine_with_teacher_name_and_printable_format()
    {
        $admin = User::where('email', 'demo.admin@example.com')->first();
        $teacher = User::where('email', 'like', 'demo.teacher%')->first();
        $session = SchoolSession::latest('id')->first();
        $class = SchoolClass::where('session_id', $session->id)->first();
        $section = Section::where('class_id', $class->id)->first();
        $semester = Semester::where('session_id', $session->id)->first() ?? Semester::create([
            'session_id'    => $session->id,
            'semester_name' => 'First Semester',
            'start_date'    => '2026-01-01',
            'end_date'      => '2026-06-30',
        ]);

        $course = Course::create([
            'course_name' => 'Advanced Physics',
            'course_type' => 'Core',
            'class_id'    => $class->id,
            'semester_id' => $semester->id,
            'session_id'  => $session->id,
        ]);

        // Assign teacher to this course and section
        AssignedTeacher::create([
            'teacher_id'  => $teacher->id,
            'semester_id' => $semester->id,
            'class_id'    => $class->id,
            'section_id'  => $section->id,
            'course_id'   => $course->id,
            'session_id'  => $session->id,
        ]);

        // Create routine entry
        Routine::create([
            'start'      => '09:00am',
            'end'        => '09:50am',
            'weekday'    => 1, // Monday
            'class_id'   => $class->id,
            'section_id' => $section->id,
            'course_id'  => $course->id,
            'session_id' => $session->id,
        ]);

        $response = $this->actingAs($admin)->get(route('section.routine.show', [
            'class_id'   => $class->id,
            'section_id' => $section->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Advanced Physics');
        $response->assertSee($teacher->first_name);
        $response->assertSee($teacher->last_name);
        $response->assertSee('09:00am - 09:50am');
        $response->assertSee('Print Routine');
        $response->assertSee('CLASS TIMETABLE &amp; ROUTINE', false);
        $response->assertSee('MONDAY');
    }

    /**
     * Test routine view handles unassigned teacher gracefully.
     */
    public function test_routine_view_handles_unassigned_teacher_gracefully()
    {
        $admin = User::where('email', 'demo.admin@example.com')->first();
        $session = SchoolSession::latest('id')->first();
        $class = SchoolClass::where('session_id', $session->id)->first();
        $section = Section::where('class_id', $class->id)->first();
        $semester = Semester::where('session_id', $session->id)->first();

        $course = Course::create([
            'course_name' => 'World History',
            'course_type' => 'General',
            'class_id'    => $class->id,
            'semester_id' => $semester->id,
            'session_id'  => $session->id,
        ]);

        Routine::create([
            'start'      => '10:00am',
            'end'        => '10:50am',
            'weekday'    => 2, // Tuesday
            'class_id'   => $class->id,
            'section_id' => $section->id,
            'course_id'  => $course->id,
            'session_id' => $session->id,
        ]);

        $response = $this->actingAs($admin)->get(route('section.routine.show', [
            'class_id'   => $class->id,
            'section_id' => $section->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('World History');
        $response->assertSee('Not Assigned');
        $response->assertSee('TUESDAY');
    }

    /**
     * Test creating a routine entry via store endpoint works.
     */
    public function test_admin_can_store_new_routine_entry()
    {
        $admin = User::where('email', 'demo.admin@example.com')->first();
        $session = SchoolSession::latest('id')->first();
        $class = SchoolClass::where('session_id', $session->id)->first();
        $section = Section::where('class_id', $class->id)->first();
        $semester = Semester::where('session_id', $session->id)->first();

        $course = Course::create([
            'course_name' => 'Chemistry Lab',
            'course_type' => 'Core',
            'class_id'    => $class->id,
            'semester_id' => $semester->id,
            'session_id'  => $session->id,
        ]);

        $response = $this->actingAs($admin)->post(route('section.routine.store'), [
            'start'      => '11:00am',
            'end'        => '11:50am',
            'weekday'    => 3,
            'class_id'   => $class->id,
            'section_id' => $section->id,
            'course_id'  => $course->id,
            'session_id' => $session->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('routines', [
            'class_id'   => $class->id,
            'section_id' => $section->id,
            'course_id'  => $course->id,
            'weekday'    => 3,
            'start'      => '11:00am',
            'end'        => '11:50am',
        ]);
    }
}
