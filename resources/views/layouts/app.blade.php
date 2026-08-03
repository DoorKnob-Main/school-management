<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ setting('browser_title', setting('software_name', 'DoorKnob ERP')) }}</title>

    <!-- Dynamic Favicon -->
    @php
        $faviconUrl = setting_asset('favicon') ?? asset('favicon_io/favicon.ico');
    @endphp
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="icon" href="{{ $faviconUrl }}">

    <!-- Scripts -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    @if(setting('google_font_url'))
        <link href="{{ setting('google_font_url') }}" rel="stylesheet">
    @else
        <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    @endif

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- Dynamic Theme Customizations -->
    <style>
        :root {
            --bs-primary: {{ setting('primary_color', '#0d6efd') }};
            --bs-secondary: {{ setting('secondary_color', '#6c757d') }};
        }
        body {
            background-color: {{ setting('background_color', '#f8f9fa') }};
            font-family: {{ setting('font_family', "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif") }};
        }
        .bg-primary, .btn-primary {
            background-color: {{ setting('primary_color', '#0d6efd') }} !important;
            border-color: {{ setting('primary_color', '#0d6efd') }} !important;
        }
        .text-primary {
            color: {{ setting('primary_color', '#0d6efd') }} !important;
        }
        .card {
            border-radius: {{ setting('card_radius', '8px') }};
        }
        {!! setting('custom_css') !!}
    </style>

    <!-- SEO Head Inject -->
    @if(setting('meta_description'))
        <meta name="description" content="{{ setting('meta_description') }}">
    @endif
    @if(setting('keywords'))
        <meta name="keywords" content="{{ setting('keywords') }}">
    @endif
    @if(setting('robots'))
        <meta name="robots" content="{{ setting('robots') }}">
    @endif
    {!! setting('custom_head_script') !!}
    {!! setting('google_analytics') !!}
    {!! setting('facebook_pixel') !!}
    {!! setting('clarity_code') !!}
</head>
<body>
    <div id="app">
        <!-- Persistent Super Admin Impersonation Warning Banner -->
        @auth
            @if(Auth::user()->isSuperAdmin() && session()->has('impersonated_role'))
                <div class="alert alert-warning border-0 rounded-0 mb-0 py-2 text-center d-flex justify-content-between align-items-center px-4 sticky-top" style="z-index: 1050; background-color: #fff3cd; border-bottom: 2px solid #ffecb5 !important;">
                    <div>
                        <i class="bi bi-eye-fill me-2 text-dark"></i>
                        <strong class="text-dark">SUPER ADMIN IMPERSONATION MODE:</strong> Currently viewing application as <span class="badge bg-dark text-white text-uppercase ms-1 me-1">{{ session('impersonated_role') }}</span> (Real Identity: Super Admin)
                    </div>
                    <form action="{{ route('super-admin.exit-impersonation') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-arrow-left-circle me-1"></i> Exit Impersonation</button>
                    </form>
                </div>
            @endif
        @endauth

        <nav class="navbar sticky-top navbar-expand-md navbar-light bg-white border-btm-e6" style="background-color: {{ setting('navbar_color', '#ffffff') }} !important;">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    @if(setting_asset('logo'))
                        <img src="{{ setting_asset('logo') }}" alt="{{ setting('software_name', 'DoorKnob') }}" style="max-height: 38px;" class="me-2">
                    @else
                        <i class="bi bi-mortarboard-fill text-primary me-2 fs-4"></i>
                    @endif
                    <span class="fw-bold text-dark">{{ setting('software_name', 'DoorKnob') }}</span>
                    @if(setting('software_short_name'))
                        <span class="badge bg-primary ms-2 small">{{ setting('software_short_name') }}</span>
                    @endif
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    @auth
                        @php
                            $latest_school_session = \App\Models\SchoolSession::latest()->first();
                            $current_school_session_name = null;
                            if($latest_school_session){
                                $current_school_session_name = $latest_school_session->session_name;
                            }
                        @endphp
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            @if (session()->has('browse_session_name') && session('browse_session_name') !== $current_school_session_name)
                                <a class="nav-link text-danger disabled" href="#" tabindex="-1" aria-disabled="true"><i class="bi bi-exclamation-diamond-fill me-2"></i> Browsing as Academic Session {{session('browse_session_name')}}</a>
                            @elseif(\App\Models\SchoolSession::latest()->count() > 0)
                                <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Current Academic Session {{$current_school_session_name}}</a>
                            @else
                                <a class="nav-link text-danger disabled" href="#" tabindex="-1" aria-disabled="true"><i class="bi bi-exclamation-diamond-fill me-2"></i> Create an Academic Session.</a>
                            @endif
                        </li>
                    </ul>
                    @endauth

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto align-items-center">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                        @else
                            <!-- Super Admin Role Switcher Dropdown -->
                            @if(Auth::user()->isSuperAdmin())
                                <li class="nav-item dropdown me-3">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" id="superAdminRoleSwitcher" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-arrow-repeat me-1"></i> Role: {{ ucfirst(Auth::user()->effective_role) }}
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="superAdminRoleSwitcher">
                                        <li><h6 class="dropdown-header">Super Admin Context Switcher</h6></li>
                                        <li>
                                            <form action="{{ route('super-admin.switch-role') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="role" value="super_admin">
                                                <button type="submit" class="dropdown-item {{ !session()->has('impersonated_role') ? 'active fw-bold' : '' }}">
                                                    <i class="bi bi-shield-check me-2"></i> Super Admin (Unrestricted)
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('super-admin.switch-role') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="role" value="admin">
                                                <button type="submit" class="dropdown-item {{ session('impersonated_role') === 'admin' ? 'active fw-bold' : '' }}">
                                                    <i class="bi bi-person-badge me-2"></i> Impersonate Administrator
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('super-admin.switch-role') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="role" value="teacher">
                                                <button type="submit" class="dropdown-item {{ session('impersonated_role') === 'teacher' ? 'active fw-bold' : '' }}">
                                                    <i class="bi bi-journal-bookmark me-2"></i> Impersonate Teacher
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('super-admin.switch-role') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="role" value="student">
                                                <button type="submit" class="dropdown-item {{ session('impersonated_role') === 'student' ? 'active fw-bold' : '' }}">
                                                    <i class="bi bi-mortarboard me-2"></i> Impersonate Student
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="badge bg-primary text-white me-1">{{ ucfirst(Auth::user()->effective_role) }}</span>
                                    {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
                                    @if(Auth::user()->isSuperAdmin())
                                        <a class="dropdown-item text-primary fw-bold" href="{{ route('settings.index') }}">
                                            <i class="bi bi-gear-fill me-2"></i> ERP Settings
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    @endif
                                    <a class="dropdown-item" href="{{route('password.edit')}}">
                                        <i class="bi bi-key me-2"></i> Change Password
                                    </a>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="bi bi-door-open me-2"></i> {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Flash Session Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-0 text-center rounded-0" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-0 text-center rounded-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <main>
            @yield('content')
        </main>
    </div>

    <div id="watermark">
        <p>{{ setting('software_name', 'DoorKnob') }}</p>
    </div>

    {!! setting('custom_footer_script') !!}
    <script>
        {!! setting('custom_js') !!}
    </script>
</body>
</html>
