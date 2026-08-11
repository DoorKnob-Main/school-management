<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SchoolSession;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Course;
use App\Models\AssignedTeacher;
use App\Models\StudentParentInfo;
use App\Models\StudentAcademicInfo;
use App\Models\Promotion;
use App\Models\GradingSystem;
use App\Models\GradeRule;
use App\Models\Exam;
use App\Models\ExamRule;
use App\Models\Mark;
use App\Models\FinalMark;
use App\Models\Attendance;
use App\Models\Notice;
use App\Models\Event;
use App\Models\Routine;
use App\Models\Syllabus;
use App\Models\Assignment;
use App\Models\AcademicSetting;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DemoSchoolSeeder extends Seeder
{
    /**
     * Run the realistic demo school seeder.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting Demo School Seeder (50-100 Users & End-to-End ERP Data)...');

        // Ensure roles and permissions exist
        $this->call(PermissionSeeder::class);
        $this->call(SuperAdminSeeder::class);

        $passwordHash = Hash::make('Password123!');

        // 1. ACADEMIC SESSIONS
        $prevSession = SchoolSession::updateOrCreate(
            ['session_name' => '2025-2026'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $currSession = SchoolSession::updateOrCreate(
            ['session_name' => '2026-2027'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $prevSessionId = $prevSession->id;
        $currSessionId = $currSession->id;

        // Ensure AcademicSetting exists
        AcademicSetting::updateOrCreate(
            ['id' => 1],
            ['attendance_type' => 'section', 'marks_submission_status' => 'off']
        );

        // 2. SEMESTERS
        $prevSem1 = Semester::updateOrCreate(
            ['session_id' => $prevSessionId, 'semester_name' => 'Semester 1 (July-Dec)'],
            ['start_date' => '2025-07-01', 'end_date' => '2025-12-31']
        );
        $prevSem2 = Semester::updateOrCreate(
            ['session_id' => $prevSessionId, 'semester_name' => 'Semester 2 (Jan-June)'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-06-30']
        );

        $currSem1 = Semester::updateOrCreate(
            ['session_id' => $currSessionId, 'semester_name' => 'Semester 1 (July-Dec)'],
            ['start_date' => '2026-07-01', 'end_date' => '2026-12-31']
        );
        $currSem2 = Semester::updateOrCreate(
            ['session_id' => $currSessionId, 'semester_name' => 'Semester 2 (Jan-June)'],
            ['start_date' => '2027-01-01', 'end_date' => '2027-06-30']
        );

        // 3. CLASSES & SECTIONS (Current Session 2026-2027)
        $classList = [
            'Class 4'           => ['General'],
            'Class 5'           => ['General'],
            'Class 6'           => ['General'],
            'Class 7'           => ['General'],
            'Class 8'           => ['General'],
            'Class 9'           => ['Science', 'General'],
            'Class 10'          => ['Science', 'General'],
            'Class 11 Science'  => ['Science'],
            'Class 11 Commerce' => ['Commerce'],
        ];

        // Seed Classes & Sections for Current Session
        $classes = [];
        $sections = [];

        foreach ($classList as $className => $tags) {
            $c = SchoolClass::updateOrCreate(
                ['session_id' => $currSessionId, 'class_name' => $className]
            );
            $classes[$className] = $c;

            // Sections A & B
            $secA = Section::updateOrCreate(
                ['session_id' => $currSessionId, 'class_id' => $c->id, 'section_name' => 'Section A'],
                ['room_no' => 'Room 10' . rand(1, 9)]
            );
            $secB = Section::updateOrCreate(
                ['session_id' => $currSessionId, 'class_id' => $c->id, 'section_name' => 'Section B'],
                ['room_no' => 'Room 20' . rand(1, 9)]
            );

            $sections[$className] = [
                'Section A' => $secA,
                'Section B' => $secB,
            ];
        }

        // Seed Classes & Sections for Previous Session (2025-2026) for promotion testing
        $prevClasses = [];
        $prevSections = [];
        foreach (['Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9'] as $className) {
            $c = SchoolClass::updateOrCreate(
                ['session_id' => $prevSessionId, 'class_name' => $className]
            );
            $prevClasses[$className] = $c;

            $secA = Section::updateOrCreate(
                ['session_id' => $prevSessionId, 'class_id' => $c->id, 'section_name' => 'Section A'],
                ['room_no' => 'Prev Room 101']
            );
            $secB = Section::updateOrCreate(
                ['session_id' => $prevSessionId, 'class_id' => $c->id, 'section_name' => 'Section B'],
                ['room_no' => 'Prev Room 102']
            );
            $prevSections[$className] = ['Section A' => $secA, 'Section B' => $secB];
        }

        // 4. COURSES / SUBJECTS (Current Session, Sem 1)
        $courseMapping = [
            'Class 5' => [
                ['name' => 'Mathematics', 'type' => 'Theory'],
                ['name' => 'English Language', 'type' => 'Theory'],
                ['name' => 'General Science', 'type' => 'General'],
                ['name' => 'Social Studies', 'type' => 'Theory'],
                ['name' => 'Hindi Literature', 'type' => 'Theory'],
            ],
            'Class 6' => [
                ['name' => 'Mathematics', 'type' => 'Theory'],
                ['name' => 'English Language', 'type' => 'Theory'],
                ['name' => 'Science', 'type' => 'General'],
                ['name' => 'Social Science', 'type' => 'Theory'],
                ['name' => 'Computer Applications', 'type' => 'Practical'],
            ],
            'Class 7' => [
                ['name' => 'Mathematics', 'type' => 'Theory'],
                ['name' => 'English Literature', 'type' => 'Theory'],
                ['name' => 'Science', 'type' => 'General'],
                ['name' => 'Social Science', 'type' => 'Theory'],
                ['name' => 'Computer Science', 'type' => 'Practical'],
            ],
            'Class 8' => [
                ['name' => 'Mathematics', 'type' => 'Theory'],
                ['name' => 'English Literature', 'type' => 'Theory'],
                ['name' => 'Science & Tech', 'type' => 'General'],
                ['name' => 'Social Science', 'type' => 'Theory'],
                ['name' => 'Hindi', 'type' => 'Theory'],
            ],
            'Class 9' => [
                ['name' => 'Advanced Mathematics', 'type' => 'Theory'],
                ['name' => 'Physics', 'type' => 'Theory'],
                ['name' => 'Chemistry', 'type' => 'Theory'],
                ['name' => 'Biology', 'type' => 'Theory'],
                ['name' => 'English Communicative', 'type' => 'Theory'],
            ],
            'Class 10' => [
                ['name' => 'Mathematics Standard', 'type' => 'Theory'],
                ['name' => 'Physics', 'type' => 'Theory'],
                ['name' => 'Chemistry', 'type' => 'Theory'],
                ['name' => 'Biology', 'type' => 'Theory'],
                ['name' => 'English Core', 'type' => 'Theory'],
            ],
            'Class 11 Science' => [
                ['name' => 'Physics Theory', 'type' => 'Theory'],
                ['name' => 'Chemistry Organic & Inorganic', 'type' => 'Theory'],
                ['name' => 'Higher Mathematics', 'type' => 'Theory'],
                ['name' => 'Biology & Biotechnology', 'type' => 'Theory'],
                ['name' => 'English Elective', 'type' => 'Theory'],
            ],
            'Class 11 Commerce' => [
                ['name' => 'Financial Accountancy', 'type' => 'Theory'],
                ['name' => 'Macroeconomics', 'type' => 'Theory'],
                ['name' => 'Business Studies', 'type' => 'Theory'],
                ['name' => 'Applied Mathematics', 'type' => 'Theory'],
                ['name' => 'English Core', 'type' => 'Theory'],
            ],
        ];

        $courses = [];
        foreach ($courseMapping as $cName => $cList) {
            if (isset($classes[$cName])) {
                $classObj = $classes[$cName];
                foreach ($cList as $cData) {
                    $crs = Course::updateOrCreate(
                        [
                            'session_id'  => $currSessionId,
                            'semester_id' => $currSem1->id,
                            'class_id'    => $classObj->id,
                            'course_name' => $cData['name'],
                        ],
                        [
                            'course_type' => $cData['type'],
                        ]
                    );
                    $courses[$cName][] = $crs;
                }
            }
        }

        // Also seed Courses for Previous Session (2025-2026, Sem 1)
        $prevCourses = [];
        foreach (['Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8'] as $cName) {
            if (isset($prevClasses[$cName])) {
                $classObj = $prevClasses[$cName];
                $crs = Course::updateOrCreate(
                    [
                        'session_id'  => $prevSessionId,
                        'semester_id' => $prevSem1->id,
                        'class_id'    => $classObj->id,
                        'course_name' => $cName . ' Mathematics',
                    ],
                    [
                        'course_type' => 'Theory',
                    ]
                );
                $prevCourses[$cName][] = $crs;
            }
        }

        // 5. USERS & ROLES
        // 5A. Super Admin (demo.superadmin@example.com)
        $superAdmin = User::updateOrCreate(
            ['email' => 'demo.superadmin@example.com'],
            [
                'first_name'  => 'Demo',
                'last_name'   => 'SuperAdmin',
                'password'    => $passwordHash,
                'gender'      => 'Male',
                'nationality' => 'Indian',
                'phone'       => '+1 800 555 0100',
                'address'     => '100 Central Campus Way',
                'address2'    => 'Suite 1',
                'city'        => 'Metro City',
                'zip'         => '100001',
                'role'        => 'super_admin',
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        // 5B. Admin (demo.admin@example.com)
        $admin = User::updateOrCreate(
            ['email' => 'demo.admin@example.com'],
            [
                'first_name'  => 'Rajesh',
                'last_name'   => 'Sharma (Admin)',
                'password'    => $passwordHash,
                'gender'      => 'Male',
                'nationality' => 'Indian',
                'phone'       => '+1 800 555 0101',
                'address'     => '101 Admin Block',
                'address2'    => 'Floor 2',
                'city'        => 'Metro City',
                'zip'         => '100001',
                'role'        => 'admin',
            ]
        );

        $teacherPermissions = [
            'create exams',
            'view exams',
            'create exams rule',
            'view exams rule',
            'edit exams rule',
            'delete exams rule',
            'take attendances',
            'view attendances',
            'create assignments',
            'view assignments',
            'save marks',
            'view users',
            'view routines',
            'view syllabi',
            'view events',
            'view notices',
        ];

        $studentPermissions = [
            'view attendances',
            'view assignments',
            'submit assignments',
            'view exams',
            'view marks',
            'view users',
            'view routines',
            'view syllabi',
            'view events',
            'view notices',
        ];

        // Give Admin full administrative permissions
        $admin->givePermissionTo([
            'create school sessions',
            'update browse by session',
            'create semesters',
            'edit semesters',
            'assign teachers',
            'create courses',
            'view courses',
            'edit courses',
            'create classes',
            'view classes',
            'edit classes',
            'create sections',
            'view sections',
            'edit sections',
            'create exams',
            'view exams',
            'create exams rule',
            'edit exams rule',
            'delete exams rule',
            'view exams rule',
            'create routines',
            'view routines',
            'edit routines',
            'delete routines',
            'view marks',
            'view academic settings',
            'update marks submission window',
            'create users',
            'edit users',
            'view users',
            'promote students',
            'update attendances type',
            'view attendances',
            'create grading systems',
            'view grading systems',
            'edit grading systems',
            'delete grading systems',
            'create grading systems rule',
            'view grading systems rule',
            'edit grading systems rule',
            'delete grading systems rule',
            'create notices',
            'view notices',
            'edit notices',
            'delete notices',
            'create events',
            'view events',
            'edit events',
            'delete events',
            'create syllabi',
            'view syllabi',
            'edit syllabi',
            'delete syllabi',
            'view assignments',
            'view payments',
            'collect fees',
            'manage expenses',
            'view reports',
            'view transactions',
            'send fee reminder',
        ]);

        // 5C. 8 TEACHERS (demo.teacher01@example.com to demo.teacher08@example.com)
        $teacherData = [
            ['first' => 'Amit',     'last' => 'Sharma',   'dept' => 'Mathematics'],
            ['first' => 'Priya',    'last' => 'Singh',    'dept' => 'Science'],
            ['first' => 'Rahul',    'last' => 'Kumar',    'dept' => 'English'],
            ['first' => 'Neha',     'last' => 'Verma',    'dept' => 'Social Science'],
            ['first' => 'Arjun',    'last' => 'Gupta',    'dept' => 'Physics'],
            ['first' => 'Pooja',    'last' => 'Kumari',   'dept' => 'Chemistry'],
            ['first' => 'Rohit',    'last' => 'Raj',      'dept' => 'Accountancy'],
            ['first' => 'Sneha',    'last' => 'Singh',    'dept' => 'Biology'],
        ];

        $teachers = [];
        foreach ($teacherData as $idx => $tInfo) {
            $num = sprintf('%02d', $idx + 1);
            $email = "demo.teacher{$num}@example.com";

            $tUser = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name'  => $tInfo['first'],
                    'last_name'   => $tInfo['last'],
                    'password'    => $passwordHash,
                    'gender'      => ($idx % 2 == 0) ? 'Male' : 'Female',
                    'nationality' => 'Indian',
                    'phone'       => '+1 800 555 02' . $num,
                    'address'     => "Teacher Residence #{$num}",
                    'address2'    => "Apt {$num}",
                    'city'        => 'Metro City',
                    'zip'         => '100002',
                    'role'        => 'teacher',
                ]
            );

            $tUser->syncPermissions($teacherPermissions);
            $teachers[] = $tUser;
        }

        // 6. TEACHER ASSIGNMENTS (Current Session 2026-2027)
        $assignmentSpecs = [
            // Teacher 1 (Amit Sharma - Math)
            0 => [
                ['class' => 'Class 5', 'section' => 'Section A', 'course_name' => 'Mathematics'],
                ['class' => 'Class 6', 'section' => 'Section A', 'course_name' => 'Mathematics'],
                ['class' => 'Class 7', 'section' => 'Section A', 'course_name' => 'Mathematics'],
            ],
            // Teacher 2 (Priya Singh - Science)
            1 => [
                ['class' => 'Class 5', 'section' => 'Section A', 'course_name' => 'General Science'],
                ['class' => 'Class 6', 'section' => 'Section B', 'course_name' => 'Science'],
                ['class' => 'Class 7', 'section' => 'Section B', 'course_name' => 'Science'],
            ],
            // Teacher 3 (Rahul Kumar - English)
            2 => [
                ['class' => 'Class 5', 'section' => 'Section B', 'course_name' => 'English Language'],
                ['class' => 'Class 6', 'section' => 'Section A', 'course_name' => 'English Language'],
                ['class' => 'Class 8', 'section' => 'Section A', 'course_name' => 'English Literature'],
            ],
            // Teacher 4 (Neha Verma - SST & Hindi)
            3 => [
                ['class' => 'Class 5', 'section' => 'Section A', 'course_name' => 'Social Studies'],
                ['class' => 'Class 6', 'section' => 'Section A', 'course_name' => 'Social Science'],
                ['class' => 'Class 8', 'section' => 'Section B', 'course_name' => 'Social Science'],
            ],
            // Teacher 5 (Arjun Gupta - Physics & Math)
            4 => [
                ['class' => 'Class 9', 'section' => 'Section A', 'course_name' => 'Physics'],
                ['class' => 'Class 10', 'section' => 'Section A', 'course_name' => 'Physics'],
                ['class' => 'Class 11 Science', 'section' => 'Section A', 'course_name' => 'Physics Theory'],
            ],
            // Teacher 6 (Pooja Kumari - Chemistry)
            5 => [
                ['class' => 'Class 9', 'section' => 'Section B', 'course_name' => 'Chemistry'],
                ['class' => 'Class 10', 'section' => 'Section B', 'course_name' => 'Chemistry'],
                ['class' => 'Class 11 Science', 'section' => 'Section A', 'course_name' => 'Chemistry Organic & Inorganic'],
            ],
            // Teacher 7 (Rohit Raj - Commerce & Accountancy)
            6 => [
                ['class' => 'Class 11 Commerce', 'section' => 'Section A', 'course_name' => 'Financial Accountancy'],
                ['class' => 'Class 11 Commerce', 'section' => 'Section A', 'course_name' => 'Macroeconomics'],
                ['class' => 'Class 11 Commerce', 'section' => 'Section B', 'course_name' => 'Business Studies'],
            ],
            // Teacher 8 (Sneha Singh - Bio)
            7 => [
                ['class' => 'Class 9', 'section' => 'Section A', 'course_name' => 'Biology'],
                ['class' => 'Class 10', 'section' => 'Section A', 'course_name' => 'Biology'],
                ['class' => 'Class 11 Science', 'section' => 'Section B', 'course_name' => 'Biology & Biotechnology'],
            ],
        ];

        foreach ($assignmentSpecs as $tIdx => $specs) {
            $tUser = $teachers[$tIdx];
            foreach ($specs as $spec) {
                $cName = $spec['class'];
                $sName = $spec['section'];
                $crsName = $spec['course_name'];

                if (isset($classes[$cName]) && isset($sections[$cName][$sName])) {
                    $cObj = $classes[$cName];
                    $sObj = $sections[$cName][$sName];
                    $crsObj = Course::where('class_id', $cObj->id)
                        ->where('course_name', $crsName)
                        ->first();

                    if ($crsObj) {
                        AssignedTeacher::updateOrCreate([
                            'session_id'  => $currSessionId,
                            'semester_id' => $currSem1->id,
                            'class_id'    => $cObj->id,
                            'section_id'  => $sObj->id,
                            'course_id'   => $crsObj->id,
                        ], [
                            'teacher_id'  => $tUser->id,
                        ]);
                    }
                }
            }
        }

        // 7. 70 STUDENTS (demo.student01@example.com to demo.student70@example.com)
        $studentDistribution = [
            'Class 5'           => ['Section A' => 6, 'Section B' => 6], // 12
            'Class 6'           => ['Section A' => 6, 'Section B' => 6], // 12
            'Class 7'           => ['Section A' => 6, 'Section B' => 6], // 12
            'Class 8'           => ['Section A' => 6, 'Section B' => 6], // 12
            'Class 9'           => ['Section A' => 5, 'Section B' => 5], // 10
            'Class 10'          => ['Section A' => 3, 'Section B' => 3], // 6
            'Class 11 Science'  => ['Section A' => 3, 'Section B' => 3], // 6
        ]; // Total = 70 students

        $firstNames = ['Aarav', 'Ananya', 'Vivaan', 'Diya', 'Aditya', 'Isha', 'Kabir', 'Riya', 'Vihaan', 'Kavya', 'Reyansh', 'Anushka', 'Rohan', 'Meera', 'Dev', 'Sanya', 'Arnav', 'Tarun', 'Pari', 'Yash'];
        $lastNames  = ['Sharma', 'Verma', 'Gupta', 'Patel', 'Singh', 'Kumar', 'Joshi', 'Chawla', 'Mehta', 'Nair', 'Reddy', 'Rao', 'Bhat', 'Roy', 'Sen', 'Kapoor', 'Malhotra', 'Saxena', 'Deshmukh', 'Das'];

        $studentCounter = 1;
        $students = [];

        foreach ($studentDistribution as $cName => $secMap) {
            $cObj = $classes[$cName];
            foreach ($secMap as $sName => $count) {
                $sObj = $sections[$cName][$sName];
                for ($i = 1; $i <= $count; $i++) {
                    $numStr = sprintf('%02d', $studentCounter);
                    $email = "demo.student{$numStr}@example.com";
                    $fName = $firstNames[($studentCounter - 1) % count($firstNames)];
                    $lName = $lastNames[($studentCounter * 3) % count($lastNames)];

                    $studentUser = User::updateOrCreate(
                        ['email' => $email],
                        [
                            'first_name'  => $fName,
                            'last_name'   => $lName,
                            'password'    => $passwordHash,
                            'gender'      => ($studentCounter % 2 == 0) ? 'Female' : 'Male',
                            'nationality' => 'Indian',
                            'phone'       => '+1 800 555 03' . $numStr,
                            'address'     => "Student Residence #{$numStr}",
                            'address2'    => "Flat {$numStr}",
                            'city'        => 'Metro City',
                            'zip'         => '100003',
                            'birthday'    => Carbon::now()->subYears(10 + ($studentCounter % 6))->format('Y-m-d'),
                            'religion'    => 'General',
                            'blood_type'  => ['A+', 'B+', 'O+', 'AB+'][$studentCounter % 4],
                            'role'        => 'student',
                        ]
                    );

                    $studentUser->syncPermissions($studentPermissions);

                    // Parent Info
                    StudentParentInfo::updateOrCreate(
                        ['student_id' => $studentUser->id],
                        [
                            'father_name'    => "Father of {$fName}",
                            'father_phone'   => '+1 800 555 90' . $numStr,
                            'mother_name'    => "Mother of {$fName}",
                            'mother_phone'   => '+1 800 555 91' . $numStr,
                            'parent_address' => "Parent Residence #{$numStr}",
                        ]
                    );

                    // Academic Info
                    StudentAcademicInfo::updateOrCreate(
                        ['student_id' => $studentUser->id],
                        [
                            'board_reg_no' => 'REG-2026-' . sprintf('%04d', $studentCounter),
                        ]
                    );

                    // Promotion / Current Class Section (Current Session 2026-2027)
                    Promotion::updateOrCreate(
                        [
                            'student_id' => $studentUser->id,
                            'session_id' => $currSessionId,
                        ],
                        [
                            'class_id'       => $cObj->id,
                            'section_id'     => $sObj->id,
                            'id_card_number' => 'DEMO-2026-' . sprintf('%03d', $studentCounter),
                        ]
                    );

                    // Previous Session Record (2025-2026) for promotion testing
                    if ($studentCounter <= 40) {
                        $prevClassName = ($cName === 'Class 5') ? 'Class 4' : (($cName === 'Class 6') ? 'Class 5' : (($cName === 'Class 7') ? 'Class 6' : 'Class 7'));
                        if (isset($prevClasses[$prevClassName]) && isset($prevSections[$prevClassName]['Section A'])) {
                            Promotion::updateOrCreate(
                                [
                                    'student_id' => $studentUser->id,
                                    'session_id' => $prevSessionId,
                                ],
                                [
                                    'class_id'       => $prevClasses[$prevClassName]->id,
                                    'section_id'     => $prevSections[$prevClassName]['Section A']->id,
                                    'id_card_number' => 'DEMO-2025-' . sprintf('%03d', $studentCounter),
                                ]
                            );
                        }
                    }

                    $students[] = [
                        'user'  => $studentUser,
                        'class' => $cObj,
                        'sec'   => $sObj,
                    ];

                    $studentCounter++;
                }
            }
        }

        // 8. GRADING SYSTEMS & RULES
        $primaryGrading = GradingSystem::updateOrCreate(
            [
                'session_id'  => $currSessionId,
                'semester_id' => $currSem1->id,
                'class_id'    => $classes['Class 5']->id,
            ],
            ['system_name' => 'Primary Grading Standard']
        );

        $secondaryGrading = GradingSystem::updateOrCreate(
            [
                'session_id'  => $currSessionId,
                'semester_id' => $currSem1->id,
                'class_id'    => $classes['Class 10']->id,
            ],
            ['system_name' => 'Secondary Board Grading Standard']
        );

        $gradeRulesSpecs = [
            ['point' => 4.0, 'grade' => 'A+', 'start_at' => 90, 'end_at' => 100],
            ['point' => 3.7, 'grade' => 'A',  'start_at' => 80, 'end_at' => 89],
            ['point' => 3.3, 'grade' => 'B+', 'start_at' => 70, 'end_at' => 79],
            ['point' => 3.0, 'grade' => 'B',  'start_at' => 60, 'end_at' => 69],
            ['point' => 2.5, 'grade' => 'C',  'start_at' => 50, 'end_at' => 59],
            ['point' => 2.0, 'grade' => 'D',  'start_at' => 40, 'end_at' => 49],
            ['point' => 0.0, 'grade' => 'F',  'start_at' => 0,  'end_at' => 39],
        ];

        foreach ([$primaryGrading, $secondaryGrading] as $gSys) {
            foreach ($gradeRulesSpecs as $rSpec) {
                GradeRule::updateOrCreate(
                    [
                        'grading_system_id' => $gSys->id,
                        'session_id'        => $currSessionId,
                        'grade'             => $rSpec['grade'],
                    ],
                    [
                        'point'    => $rSpec['point'],
                        'start_at' => $rSpec['start_at'],
                        'end_at'   => $rSpec['end_at'],
                    ]
                );
            }
        }

        // 9. EXAMS, EXAM RULES, MARKS, FINAL MARKS
        $examTypes = ['Unit Test 1', 'Final Examination'];
        foreach (['Class 5', 'Class 10'] as $targetClassName) {
            $cObj = $classes[$targetClassName];
            $cCourses = Course::where('class_id', $cObj->id)->get();

            foreach ($cCourses as $crsObj) {
                foreach ($examTypes as $eType) {
                    $exam = Exam::updateOrCreate(
                        [
                            'session_id'  => $currSessionId,
                            'semester_id' => $currSem1->id,
                            'class_id'    => $cObj->id,
                            'course_id'   => $crsObj->id,
                            'exam_name'   => $eType,
                        ],
                        [
                            'start_date'  => Carbon::now()->addDays(5)->toDateTimeString(),
                            'end_date'    => Carbon::now()->addDays(7)->toDateTimeString(),
                        ]
                    );

                    // Exam Rule
                    ExamRule::updateOrCreate(
                        [
                            'session_id' => $currSessionId,
                            'exam_id'   => $exam->id,
                        ],
                        [
                            'total_marks'             => 100,
                            'pass_marks'              => 40,
                            'marks_distribution_note' => 'Theory: 80, Internal: 20',
                        ]
                    );

                    // Seed marks for students in this class
                    $classStudents = Promotion::where('session_id', $currSessionId)
                        ->where('class_id', $cObj->id)
                        ->get();

                    foreach ($classStudents as $idx => $prom) {
                        $markVal = 85;
                        if ($idx % 5 == 0) $markVal = 96;       // High
                        else if ($idx % 5 == 1) $markVal = 72;  // Normal
                        else if ($idx % 5 == 2) $markVal = 40;  // Boundary Pass
                        else if ($idx % 5 == 3) $markVal = 32;  // Failure
                        else $markVal = 64;

                        Mark::updateOrCreate(
                            [
                                'exam_id'    => $exam->id,
                                'student_id' => $prom->student_id,
                                'session_id' => $currSessionId,
                                'class_id'   => $cObj->id,
                                'section_id' => $prom->section_id,
                                'course_id'  => $crsObj->id,
                            ],
                            [
                                'marks' => $markVal,
                            ]
                        );

                        // Seed Final Mark entry
                        FinalMark::updateOrCreate(
                            [
                                'semester_id' => $currSem1->id,
                                'student_id'  => $prom->student_id,
                                'session_id'  => $currSessionId,
                                'class_id'    => $cObj->id,
                                'section_id'  => $prom->section_id,
                                'course_id'   => $crsObj->id,
                            ],
                            [
                                'calculated_marks' => $markVal,
                                'final_marks'      => $markVal,
                                'note'             => ($markVal >= 40) ? 'Passed' : 'Failed',
                            ]
                        );
                    }
                }
            }
        }

        // 10. ATTENDANCE RECORDS
        foreach (['Class 5', 'Class 6'] as $targetClassName) {
            $cObj = $classes[$targetClassName];
            $promotions = Promotion::where('session_id', $currSessionId)
                ->where('class_id', $cObj->id)
                ->get();

            $crsObj = Course::where('class_id', $cObj->id)->first();

            foreach ($promotions as $idx => $prom) {
                // Section Attendance
                Attendance::updateOrCreate(
                    [
                        'session_id'  => $currSessionId,
                        'class_id'    => $cObj->id,
                        'section_id'  => $prom->section_id,
                        'student_id'  => $prom->student_id,
                        'course_id'   => 0,
                        'created_at'  => Carbon::today()->toDateTimeString(),
                    ],
                    [
                        'status'     => ($idx % 7 == 0) ? 'off' : 'on',
                        'updated_at' => Carbon::today()->toDateTimeString(),
                    ]
                );

                // Course Attendance
                if ($crsObj) {
                    Attendance::updateOrCreate(
                        [
                            'session_id'  => $currSessionId,
                            'class_id'    => $cObj->id,
                            'section_id'  => $prom->section_id,
                            'student_id'  => $prom->student_id,
                            'course_id'   => $crsObj->id,
                            'created_at'  => Carbon::today()->toDateTimeString(),
                        ],
                        [
                            'status'     => 'on',
                            'updated_at' => Carbon::today()->toDateTimeString(),
                        ]
                    );
                }
            }
        }

        // 11. NOTICES, EVENTS, ROUTINES, SYLLABI, ASSIGNMENTS
        Notice::updateOrCreate(
            [
                'session_id' => $currSessionId,
                'notice'     => 'Annual Sports Day 2026 Notice: All students and staff are invited to attend the Annual Sports Meet.',
            ]
        );

        Event::updateOrCreate(
            [
                'session_id' => $currSessionId,
                'title'      => 'Mid-Term Parent Teacher Meeting',
            ],
            [
                'start'      => Carbon::tomorrow()->toDateTimeString(),
                'end'        => Carbon::tomorrow()->addDay()->toDateTimeString(),
            ]
        );

        if (isset($courses['Class 5'][0])) {
            $crs5_0 = $courses['Class 5'][0];

            Routine::updateOrCreate(
                [
                    'session_id' => $currSessionId,
                    'class_id'   => $classes['Class 5']->id,
                    'section_id' => $sections['Class 5']['Section A']->id,
                    'course_id'  => $crs5_0->id,
                    'weekday'    => 1, // Monday
                ],
                [
                    'start'      => '09:00 AM',
                    'end'        => '10:00 AM',
                ]
            );

            Syllabus::updateOrCreate(
                [
                    'session_id'    => $currSessionId,
                    'class_id'      => $classes['Class 5']->id,
                    'course_id'     => $crs5_0->id,
                ],
                [
                    'syllabus_name'      => 'Class 5 Mathematics Term 1 Syllabus',
                    'syllabus_file_path' => 'syllabi/demo_math_syllabus.pdf',
                ]
            );

            Assignment::updateOrCreate(
                [
                    'session_id'           => $currSessionId,
                    'semester_id'          => $currSem1->id,
                    'class_id'             => $classes['Class 5']->id,
                    'section_id'           => $sections['Class 5']['Section A']->id,
                    'course_id'            => $crs5_0->id,
                    'teacher_id'           => $teachers[0]->id,
                    'assignment_name'      => 'Fractions & Problem Solving Sheet',
                ],
                [
                    'assignment_file_path' => 'assignments/demo_fractions_sheet.pdf',
                ]
            );
        }

        $this->command->info('Demo School Seeder Completed Successfully!');
        $this->command->info('Total Users Created/Updated: ' . User::count());
    }
}
