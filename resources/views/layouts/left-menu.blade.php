@auth
<div class="col-xs-1 col-sm-1 col-md-1 col-lg-2 col-xl-2 col-xxl-2 border-rt-e6 px-0 no-print">
    <div class="d-flex flex-column align-items-center align-items-sm-start">
        <ul class="nav flex-column pt-2 w-100">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('home')? 'active' : '' }}" href="{{url('home')}}"><i class="bi bi-grid"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">{{ __('Dashboard') }}</span></a>
            </li>
            @can('view classes')
            <li class="nav-item">
                @php
                    if (session()->has('browse_session_id')){
                        $classCount = \App\Models\SchoolClass::where('session_id', session('browse_session_id'))->count();
                    } else {
                        $latest_session = \App\Models\SchoolSession::orderBy('id', 'desc')->first();
                        $classCount = $latest_session ? \App\Models\SchoolClass::where('session_id', $latest_session->id)->count() : 0;
                    }
                @endphp
                <a class="nav-link d-flex {{ request()->is('classes*')? 'active' : '' }}" href="{{url('classes')}}"><i class="bi bi-diagram-3"></i> <span class="ms-2 d-inline d-sm-none d-md-none d-xl-inline">Classes</span> <span class="ms-auto d-inline d-sm-none d-md-none d-xl-inline">{{ $classCount }}</span></a>
            </li>
            @endcan
            @if(Auth::user()->effective_role != "student")
            <li class="nav-item">
                <a type="button" href="#student-submenu" data-bs-toggle="collapse" class="d-flex nav-link {{ request()->is('students*')? 'active' : '' }}"><i class="bi bi-person-lines-fill"></i> <span class="ms-2 d-inline d-sm-none d-md-none d-xl-inline">Students</span>
                    <i class="ms-auto d-inline d-sm-none d-md-none d-xl-inline bi bi-chevron-down"></i>
                </a>
                <ul class="nav collapse {{ request()->is('students*')? 'show' : 'hide' }} bg-white" id="student-submenu">
                    <li class="nav-item w-100" {{ request()->routeIs('student.list.show')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('student.list.show')}}"><i class="bi bi-person-video2 me-2"></i> View Students</a></li>
                    @if (!session()->has('browse_session_id') && Auth::user()->isAdminOrSuperAdmin())
                    <li class="nav-item w-100" {{ request()->routeIs('student.create.show')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('student.create.show')}}"><i class="bi bi-person-plus me-2"></i> Add Student</a></li>
                    @endif
                </ul>
            </li>
            <li class="nav-item">
                <a type="button" href="#teacher-submenu" data-bs-toggle="collapse" class="d-flex nav-link {{ request()->is('teachers*')? 'active' : '' }}"><i class="bi bi-person-lines-fill"></i> <span class="ms-2 d-inline d-sm-none d-md-none d-xl-inline">Teachers</span>
                    <i class="ms-auto d-inline d-sm-none d-md-none d-xl-inline bi bi-chevron-down"></i>
                </a>
                <ul class="nav collapse {{ request()->is('teachers*')? 'show' : 'hide' }} bg-white" id="teacher-submenu">
                    <li class="nav-item w-100" {{ request()->routeIs('teacher.list.show')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('teacher.list.show')}}"><i class="bi bi-person-video2 me-2"></i> View Teachers</a></li>
                    @if (!session()->has('browse_session_id') && Auth::user()->isAdminOrSuperAdmin())
                    <li class="nav-item w-100" {{ request()->routeIs('teacher.create.show')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('teacher.create.show')}}"><i class="bi bi-person-plus me-2"></i> Add Teacher</a></li>
                    @endif
                </ul>
            </li>
            @endif

            @if(Auth::user()->effective_role == "teacher")
            <li class="nav-item">
                <a class="nav-link {{ (request()->is('courses/teacher*') || request()->is('courses/assignments*'))? 'active' : '' }}" href="{{route('course.teacher.list.show', ['teacher_id' => Auth::user()->id])}}"><i class="bi bi-journal-medical"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">My Courses</span></a>
            </li>
            @endif

            @can('view attendances')
            <li class="nav-item">
                @if(Auth::user()->effective_role == "student")
                    <a class="nav-link {{ request()->routeIs('student.attendance.show')? 'active' : '' }}" href="{{route('student.attendance.show', ['id' => Auth::user()->id])}}"><i class="bi bi-calendar2-week"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Attendance</span></a>
                @else
                    <a class="nav-link {{ request()->is('attendances*')? 'active' : '' }}" href="{{route('attendance.index')}}"><i class="bi bi-calendar2-week"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Attendance (Manual)</span></a>
                @endif
            </li>
            @endcan

            @php
                $biometricEnabled = app(\App\Services\SettingService::class)->get('biometric_attendance_enabled', '1') == '1';
            @endphp

            @if($biometricEnabled)
                @if(Auth::user()->isAdminOrSuperAdmin() || Auth::user()->effective_role == "teacher")
                <li class="nav-item">
                    <a type="button" href="#biometric-submenu" data-bs-toggle="collapse" class="d-flex nav-link {{ request()->is('biometric*')? 'active' : '' }}"><i class="bi bi-fingerprint"></i> <span class="ms-2 d-inline d-sm-none d-md-none d-xl-inline">Biometric</span>
                        <i class="ms-auto d-inline d-sm-none d-md-none d-xl-inline bi bi-chevron-down"></i>
                    </a>
                    <ul class="nav collapse {{ request()->is('biometric*')? 'show' : 'hide' }} bg-white" id="biometric-submenu">
                        <li class="nav-item w-100" {{ request()->routeIs('biometric.dashboard')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('biometric.dashboard')}}"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                        <li class="nav-item w-100" {{ request()->routeIs('biometric.today')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('biometric.today')}}"><i class="bi bi-calendar-check me-2"></i> Today's Attendance</a></li>
                        @if(Auth::user()->isAdminOrSuperAdmin())
                        <li class="nav-item w-100" {{ request()->routeIs('biometric.devices.*')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('biometric.devices.index')}}"><i class="bi bi-hdd-network me-2"></i> Devices</a></li>
                        <li class="nav-item w-100" {{ request()->routeIs('biometric.enrollment.*')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('biometric.enrollment.index')}}"><i class="bi bi-person-badge me-2"></i> Student Enrollment</a></li>
                        <li class="nav-item w-100" {{ request()->routeIs('biometric.punches')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('biometric.punches')}}"><i class="bi bi-journal-text me-2"></i> Raw Punch Logs</a></li>
                        <li class="nav-item w-100" {{ request()->routeIs('biometric.settings.*')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('biometric.settings.index')}}"><i class="bi bi-sliders me-2"></i> Attendance Rules</a></li>
                        @endif
                        <li class="nav-item w-100" {{ request()->routeIs('biometric.reports.*')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('biometric.reports.index')}}"><i class="bi bi-bar-chart-line me-2"></i> Attendance Reports</a></li>
                    </ul>
                </li>
                @endif

                <li class="nav-item">
                    <a type="button" href="#leave-submenu" data-bs-toggle="collapse" class="d-flex nav-link {{ request()->is('leaves*')? 'active' : '' }}"><i class="bi bi-envelope-paper"></i> <span class="ms-2 d-inline d-sm-none d-md-none d-xl-inline">Leave Mgmt</span>
                        <i class="ms-auto d-inline d-sm-none d-md-none d-xl-inline bi bi-chevron-down"></i>
                    </a>
                    <ul class="nav collapse {{ request()->is('leaves*')? 'show' : 'hide' }} bg-white" id="leave-submenu">
                        @if(Auth::user()->effective_role == 'student')
                        <li class="nav-item w-100" {{ request()->routeIs('leaves.my-leaves')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('leaves.my-leaves')}}"><i class="bi bi-clock-history me-2"></i> My Leaves</a></li>
                        @else
                        <li class="nav-item w-100" {{ request()->routeIs('leaves.index')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('leaves.index')}}"><i class="bi bi-list-check me-2"></i> Leave Requests</a></li>
                        @if(Auth::user()->isAdminOrSuperAdmin())
                        <li class="nav-item w-100" {{ request()->routeIs('leaves.types')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('leaves.types')}}"><i class="bi bi-tags me-2"></i> Leave Types</a></li>
                        @endif
                        @endif
                    </ul>
                </li>
            @endif

            @if(Auth::user()->effective_role == "student")
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('course.student.list.show')? 'active' : '' }}" href="{{route('course.student.list.show', ['student_id' => Auth::user()->id])}}"><i class="bi bi-journal-medical"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">My Courses</span></a>
            </li>
            <li class="nav-item border-bottom">
                @php
                    if (session()->has('browse_session_id')){
                        $class_info = \App\Models\Promotion::where('session_id', session('browse_session_id'))->where('student_id', Auth::user()->id)->first();
                    } else {
                        $latest_session = \App\Models\SchoolSession::orderBy('id', 'desc')->first();
                        $class_info = $latest_session ? \App\Models\Promotion::where('session_id', $latest_session->id)->where('student_id', Auth::user()->id)->first() : null;
                    }
                @endphp
                @if($class_info)
                <a class="nav-link {{ request()->is('routine*')? 'active' : '' }}" href="{{route('section.routine.show', ['class_id' => $class_info->class_id, 'section_id'=> $class_info->section_id])}}"><i class="bi bi-calendar4-range"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Routine</span></a>
                @endif
            </li>
            @endif

            @if(Auth::user()->effective_role != "student")
            <li class="nav-item border-bottom">
                <a type="button" href="#exam-grade-submenu" data-bs-toggle="collapse" class="d-flex nav-link {{ (request()->is('exams*') || request()->is('marks*'))? 'active' : '' }}"><i class="bi bi-file-text"></i> <span class="ms-2 d-inline d-sm-none d-md-none d-xl-inline">Exams / Grades</span>
                    <i class="ms-auto d-inline d-sm-none d-md-none d-xl-inline bi bi-chevron-down"></i>
                </a>
                <ul class="nav collapse {{ (request()->is('exams*') || request()->is('marks*'))? 'show' : 'hide' }} bg-white" id="exam-grade-submenu">
                    <li class="nav-item w-100" {{ request()->routeIs('exam.list.show')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('exam.list.show')}}"><i class="bi bi-file-text me-2"></i> View Exams</a></li>
                    @if (Auth::user()->isAdminOrSuperAdmin() || Auth::user()->effective_role == "teacher")
                    <li class="nav-item w-100" {{ request()->routeIs('exam.create.show')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('exam.create.show')}}"><i class="bi bi-file-plus me-2"></i> Create Exams</a></li>
                    @endif
                    @if (Auth::user()->isAdminOrSuperAdmin())
                    <li class="nav-item w-100" {{ request()->routeIs('exam.grade.system.create')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('exam.grade.system.create')}}"><i class="bi bi-file-plus me-2"></i> Add Grade Systems</a></li>
                    @endif
                    <li class="nav-item w-100" {{ request()->routeIs('exam.grade.system.index')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('exam.grade.system.index')}}"><i class="bi bi-file-ruled me-2"></i> View Grade Systems</a></li>
                </ul>
            </li>
            @endif

            @if (Auth::user()->isAdminOrSuperAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->is('notice*')? 'active' : '' }}" href="{{route('notice.create')}}"><i class="bi bi-megaphone"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Notice</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('calendar-event*')? 'active' : '' }}" href="{{route('events.show')}}"><i class="bi bi-calendar-event"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Event</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('syllabus*')? 'active' : '' }}" href="{{route('class.syllabus.create')}}"><i class="bi bi-journal-text"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Syllabus</span></a>
            </li>
            <li class="nav-item border-bottom">
                <a class="nav-link {{ request()->is('routine*')? 'active' : '' }}" href="{{route('section.routine.create')}}"><i class="bi bi-calendar4-range"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Routine</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('academics*')? 'active' : '' }}" href="{{url('academics/settings')}}"><i class="bi bi-tools"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Academic</span></a>
            </li>
            @if (!session()->has('browse_session_id'))
            <li class="nav-item">
                <a class="nav-link {{ request()->is('promotions*')? 'active' : '' }}" href="{{url('promotions/index')}}"><i class="bi bi-sort-numeric-up-alt"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Promotion</span></a>
            </li>
            @endif
            @endif

            @if(!Auth::user()->isAdminOrSuperAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->is('calendar-event*')? 'active' : '' }}" href="{{route('events.show')}}"><i class="bi bi-calendar-event"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Event / Notice</span></a>
            </li>
            @endif

            @if(Auth::user()->isAdminOrSuperAdmin() || Auth::user()->can('view payments'))
            <li class="nav-item">
                <a type="button" href="#payment-submenu" data-bs-toggle="collapse" class="d-flex nav-link {{ request()->is('finance*')? 'active' : '' }}"><i class="bi bi-currency-exchange"></i> <span class="ms-2 d-inline d-sm-none d-md-none d-xl-inline">Payment</span>
                    <i class="ms-auto d-inline d-sm-none d-md-none d-xl-inline bi bi-chevron-down"></i>
                </a>
                <ul class="nav collapse {{ request()->is('finance*')? 'show' : 'hide' }} bg-white" id="payment-submenu">
                    @can('view payments')
                    <li class="nav-item w-100" {{ request()->routeIs('finance.fee-collection.index')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('finance.fee-collection.index')}}"><i class="bi bi-cash-stack me-2"></i> Fee Collection</a></li>
                    @endcan
                    @can('view transactions')
                    <li class="nav-item w-100" {{ request()->routeIs('finance.transactions.index')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('finance.transactions.index')}}"><i class="bi bi-journal-text me-2"></i> Transactions</a></li>
                    @endcan
                    @can('manage expenses')
                    <li class="nav-item w-100" {{ request()->routeIs('finance.expenses.index')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('finance.expenses.index')}}"><i class="bi bi-credit-card me-2"></i> Expenses</a></li>
                    @endcan
                    @can('view reports')
                    <li class="nav-item w-100" {{ request()->routeIs('finance.reports.index')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('finance.reports.index')}}"><i class="bi bi-bar-chart-line me-2"></i> Reports</a></li>
                    <li class="nav-item w-100" {{ request()->routeIs('finance.reports.due-students.index')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('finance.reports.due-students.index')}}"><i class="bi bi-exclamation-circle me-2"></i> Due Students</a></li>
                    @endcan
                    @can('send fee reminder')
                    <li class="nav-item w-100" {{ request()->routeIs('finance.fee-reminder.index')? 'style="font-weight:bold;"' : '' }}><a class="nav-link" href="{{route('finance.fee-reminder.index')}}"><i class="bi bi-bell me-2"></i> Fee Reminder</a></li>
                    @endcan
                </ul>
            </li>
            @endif

            @if (Auth::user()->isSuperAdmin())
            <li class="nav-item border-top mt-2 pt-2">
                <a class="nav-link {{ request()->is('settings*')? 'active' : '' }}" href="{{ route('settings.index') }}"><i class="bi bi-gear-fill"></i> <span class="ms-1 d-inline d-sm-none d-md-none d-xl-inline">Settings</span></a>
            </li>
            @endif
        </ul>
    </div>
</div>
@endauth