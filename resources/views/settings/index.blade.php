@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Menu -->
        @include('layouts.left-menu')

        <!-- Main Content Area -->
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10 ps-md-4">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/home') }}"><i class="bi bi-grid"></i> Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><i class="bi bi-gear-fill"></i> White Label ERP Settings</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1"><i class="bi bi-sliders text-primary"></i> White Label & System Configuration</h3>
                    <p class="text-muted mb-0">Manage global branding, theme colors, report headers, contact info, SEO, and system preferences.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('settings.export') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i> Export Settings JSON</a>
                </div>
            </div>

            @php
                $activeTab = request()->query('tab', 'branding');
            @endphp

            <!-- Settings Navigation Tabs -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom-0 pb-0">
                    <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'general' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'general']) }}"><i class="bi bi-info-circle me-1"></i> General</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'branding' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'branding']) }}"><i class="bi bi-palette me-1"></i> Branding</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'theme' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'theme']) }}"><i class="bi bi-paint-bucket me-1"></i> Theme & Colors</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'contact' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'contact']) }}"><i class="bi bi-telephone me-1"></i> Contact</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'social' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'social']) }}"><i class="bi bi-share me-1"></i> Social Media</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'seo' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'seo']) }}"><i class="bi bi-search me-1"></i> SEO</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'login' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'login']) }}"><i class="bi bi-box-arrow-in-right me-1"></i> Login Page</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'email' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'email']) }}"><i class="bi bi-envelope me-1"></i> Email</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'reports' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'reports']) }}"><i class="bi bi-file-earmark-pdf me-1"></i> Reports & PDF</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'system' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'system']) }}"><i class="bi bi-cpu me-1"></i> System</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'advanced' ? 'active fw-bold' : '' }}" href="{{ route('settings.index', ['tab' => 'advanced']) }}"><i class="bi bi-code-slash me-1"></i> Advanced</a>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="active_tab" value="{{ $activeTab }}">

                        <!-- TAB 1: GENERAL -->
                        @if($activeTab === 'general')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-sliders me-2"></i> General ERP Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Software Name</label>
                                    <input type="text" name="software_name" class="form-control" value="{{ setting('software_name', 'DoorKnob') }}" required>
                                    <input type="hidden" name="_groups[software_name]" value="general">
                                    <small class="text-muted">Primary name displayed across top header and portals.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Software Short Name</label>
                                    <input type="text" name="software_short_name" class="form-control" value="{{ setting('software_short_name', 'DK') }}">
                                    <input type="hidden" name="_groups[software_short_name]" value="general">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Tagline</label>
                                    <input type="text" name="tagline" class="form-control" value="{{ setting('tagline', 'School Management ERP') }}">
                                    <input type="hidden" name="_groups[tagline]" value="general">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Organization Name</label>
                                    <input type="text" name="organization_name" class="form-control" value="{{ setting('organization_name', 'DoorKnob Education') }}">
                                    <input type="hidden" name="_groups[organization_name]" value="general">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Default Currency Code</label>
                                    <input type="text" name="default_currency" class="form-control" value="{{ setting('default_currency', 'INR') }}">
                                    <input type="hidden" name="_groups[default_currency]" value="general">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="form-control" value="{{ setting('currency_symbol', '₹') }}">
                                    <input type="hidden" name="_groups[currency_symbol]" value="general">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Timezone</label>
                                    <input type="text" name="timezone" class="form-control" value="{{ setting('timezone', 'Asia/Kolkata') }}">
                                    <input type="hidden" name="_groups[timezone]" value="general">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Date Format</label>
                                    <input type="text" name="date_format" class="form-control" value="{{ setting('date_format', 'Y-m-d') }}">
                                    <input type="hidden" name="_groups[date_format]" value="general">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Time Format</label>
                                    <input type="text" name="time_format" class="form-control" value="{{ setting('time_format', 'H:i') }}">
                                    <input type="hidden" name="_groups[time_format]" value="general">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Language</label>
                                    <input type="text" name="language" class="form-control" value="{{ setting('language', 'en') }}">
                                    <input type="hidden" name="_groups[language]" value="general">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 2: BRANDING -->
                        @if($activeTab === 'branding')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-palette me-2"></i> Brand Identity & Logos</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Browser Title</label>
                                    <input type="text" name="browser_title" class="form-control" value="{{ setting('browser_title', 'DoorKnob ERP') }}">
                                    <input type="hidden" name="_groups[browser_title]" value="branding">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Footer Copyright Text</label>
                                    <input type="text" name="footer_copyright" class="form-control" value="{{ setting('footer_copyright', '© 2026 DoorKnob. All rights reserved.') }}">
                                    <input type="hidden" name="_groups[footer_copyright]" value="branding">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Powered By Text</label>
                                    <input type="text" name="powered_by_text" class="form-control" value="{{ setting('powered_by_text', 'Powered by DoorKnob ERP') }}">
                                    <input type="hidden" name="_groups[powered_by_text]" value="branding">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Show Powered By</label>
                                    <select name="show_powered_by" class="form-select">
                                        <option value="1" {{ setting('show_powered_by', '1') == '1' ? 'selected' : '' }}>Yes (Show)</option>
                                        <option value="0" {{ setting('show_powered_by', '1') == '0' ? 'selected' : '' }}>No (Hide)</option>
                                    </select>
                                    <input type="hidden" name="_groups[show_powered_by]" value="branding">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Developer Name</label>
                                    <input type="text" name="developer_name" class="form-control" value="{{ setting('developer_name', 'DoorKnob Systems') }}">
                                    <input type="hidden" name="_groups[developer_name]" value="branding">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Developer Website URL</label>
                                    <input type="url" name="developer_website" class="form-control" value="{{ setting('developer_website', 'https://doorknob.io') }}">
                                    <input type="hidden" name="_groups[developer_website]" value="branding">
                                </div>

                                <hr class="my-4">

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Primary Logo</label>
                                    @if(setting_asset('logo'))
                                        <div class="mb-2 p-2 border bg-light rounded text-center">
                                            <img src="{{ setting_asset('logo') }}" style="max-height: 50px;">
                                        </div>
                                    @endif
                                    <input type="file" name="logo" class="form-control" accept="image/*">
                                    <input type="hidden" name="_groups[logo]" value="branding">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Dark Mode Logo</label>
                                    @if(setting_asset('dark_logo'))
                                        <div class="mb-2 p-2 border bg-dark rounded text-center">
                                            <img src="{{ setting_asset('dark_logo') }}" style="max-height: 50px;">
                                        </div>
                                    @endif
                                    <input type="file" name="dark_logo" class="form-control" accept="image/*">
                                    <input type="hidden" name="_groups[dark_logo]" value="branding">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Light Mode Logo</label>
                                    @if(setting_asset('light_logo'))
                                        <div class="mb-2 p-2 border bg-light rounded text-center">
                                            <img src="{{ setting_asset('light_logo') }}" style="max-height: 50px;">
                                        </div>
                                    @endif
                                    <input type="file" name="light_logo" class="form-control" accept="image/*">
                                    <input type="hidden" name="_groups[light_logo]" value="branding">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Favicon Icon (.ico, .png, .svg)</label>
                                    @if(setting_asset('favicon'))
                                        <div class="mb-2 p-2 border bg-light rounded text-center">
                                            <img src="{{ setting_asset('favicon') }}" style="max-height: 32px;">
                                        </div>
                                    @endif
                                    <input type="file" name="favicon" class="form-control" accept="image/*,.ico">
                                    <input type="hidden" name="_groups[favicon]" value="branding">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Login Screen Logo</label>
                                    @if(setting_asset('login_logo'))
                                        <div class="mb-2 p-2 border bg-light rounded text-center">
                                            <img src="{{ setting_asset('login_logo') }}" style="max-height: 50px;">
                                        </div>
                                    @endif
                                    <input type="file" name="login_logo" class="form-control" accept="image/*">
                                    <input type="hidden" name="_groups[login_logo]" value="branding">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Footer Logo</label>
                                    @if(setting_asset('footer_logo'))
                                        <div class="mb-2 p-2 border bg-light rounded text-center">
                                            <img src="{{ setting_asset('footer_logo') }}" style="max-height: 50px;">
                                        </div>
                                    @endif
                                    <input type="file" name="footer_logo" class="form-control" accept="image/*">
                                    <input type="hidden" name="_groups[footer_logo]" value="branding">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 3: THEME & COLORS -->
                        @if($activeTab === 'theme')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-paint-bucket me-2"></i> Color Palette & Typography</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Primary Color</label>
                                    <input type="color" name="primary_color" class="form-control form-control-color w-100" value="{{ setting('primary_color', '#0d6efd') }}">
                                    <input type="hidden" name="_groups[primary_color]" value="theme">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Secondary Color</label>
                                    <input type="color" name="secondary_color" class="form-control form-control-color w-100" value="{{ setting('secondary_color', '#6c757d') }}">
                                    <input type="hidden" name="_groups[secondary_color]" value="theme">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Accent Color</label>
                                    <input type="color" name="accent_color" class="form-control form-control-color w-100" value="{{ setting('accent_color', '#ffc107') }}">
                                    <input type="hidden" name="_groups[accent_color]" value="theme">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Background Color</label>
                                    <input type="color" name="background_color" class="form-control form-control-color w-100" value="{{ setting('background_color', '#f8f9fa') }}">
                                    <input type="hidden" name="_groups[background_color]" value="theme">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Navbar Color</label>
                                    <input type="color" name="navbar_color" class="form-control form-control-color w-100" value="{{ setting('navbar_color', '#ffffff') }}">
                                    <input type="hidden" name="_groups[navbar_color]" value="theme">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Success Color</label>
                                    <input type="color" name="success_color" class="form-control form-control-color w-100" value="{{ setting('success_color', '#198754') }}">
                                    <input type="hidden" name="_groups[success_color]" value="theme">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Danger Color</label>
                                    <input type="color" name="danger_color" class="form-control form-control-color w-100" value="{{ setting('danger_color', '#dc3545') }}">
                                    <input type="hidden" name="_groups[danger_color]" value="theme">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Card Corner Radius</label>
                                    <input type="text" name="card_radius" class="form-control" value="{{ setting('card_radius', '8px') }}">
                                    <input type="hidden" name="_groups[card_radius]" value="theme">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Font Family</label>
                                    <input type="text" name="font_family" class="form-control" value="{{ setting('font_family', "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif") }}">
                                    <input type="hidden" name="_groups[font_family]" value="theme">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Google Font Embed URL</label>
                                    <input type="url" name="google_font_url" class="form-control" value="{{ setting('google_font_url', 'https://fonts.googleapis.com/css?family=Nunito') }}">
                                    <input type="hidden" name="_groups[google_font_url]" value="theme">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 4: CONTACT -->
                        @if($activeTab === 'contact')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-telephone me-2"></i> Institution Contact Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Company / Institution Legal Name</label>
                                    <input type="text" name="company_name" class="form-control" value="{{ setting('company_name', 'DoorKnob Education Inc.') }}">
                                    <input type="hidden" name="_groups[company_name]" value="contact">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Address Line</label>
                                    <input type="text" name="address" class="form-control" value="{{ setting('address', '100 Innovation Way, Suite 400') }}">
                                    <input type="hidden" name="_groups[address]" value="contact">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">City</label>
                                    <input type="text" name="city" class="form-control" value="{{ setting('city', 'Tech City') }}">
                                    <input type="hidden" name="_groups[city]" value="contact">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">State / Province</label>
                                    <input type="text" name="state" class="form-control" value="{{ setting('state', 'CA') }}">
                                    <input type="hidden" name="_groups[state]" value="contact">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Country</label>
                                    <input type="text" name="country" class="form-control" value="{{ setting('country', 'USA') }}">
                                    <input type="hidden" name="_groups[country]" value="contact">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Zip / Postal Code</label>
                                    <input type="text" name="zip_code" class="form-control" value="{{ setting('zip_code', '90210') }}">
                                    <input type="hidden" name="_groups[zip_code]" value="contact">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Primary Phone</label>
                                    <input type="text" name="phone" class="form-control" value="{{ setting('phone', '+1 (800) 555-0199') }}">
                                    <input type="hidden" name="_groups[phone]" value="contact">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Mobile Number</label>
                                    <input type="text" name="mobile" class="form-control" value="{{ setting('mobile', '+1 (800) 555-0199') }}">
                                    <input type="hidden" name="_groups[mobile]" value="contact">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">WhatsApp Support</label>
                                    <input type="text" name="whatsapp" class="form-control" value="{{ setting('whatsapp', '+1 (800) 555-0199') }}">
                                    <input type="hidden" name="_groups[whatsapp]" value="contact">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Primary Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ setting('email', 'support@doorknob.io') }}">
                                    <input type="hidden" name="_groups[email]" value="contact">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Support Email</label>
                                    <input type="email" name="support_email" class="form-control" value="{{ setting('support_email', 'support@doorknob.io') }}">
                                    <input type="hidden" name="_groups[support_email]" value="contact">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Admissions Email</label>
                                    <input type="email" name="admissions_email" class="form-control" value="{{ setting('admissions_email', 'admissions@doorknob.io') }}">
                                    <input type="hidden" name="_groups[admissions_email]" value="contact">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Website URL</label>
                                    <input type="url" name="website" class="form-control" value="{{ setting('website', 'https://doorknob.io') }}">
                                    <input type="hidden" name="_groups[website]" value="contact">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Office Hours</label>
                                    <input type="text" name="office_hours" class="form-control" value="{{ setting('office_hours', 'Mon - Fri: 8:00 AM - 5:00 PM') }}">
                                    <input type="hidden" name="_groups[office_hours]" value="contact">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 5: SOCIAL MEDIA -->
                        @if($activeTab === 'social')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-share me-2"></i> Social Media Links</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-facebook text-primary me-1"></i> Facebook</label>
                                    <input type="url" name="facebook" class="form-control" value="{{ setting('facebook', '') }}">
                                    <input type="hidden" name="_groups[facebook]" value="social">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-instagram text-danger me-1"></i> Instagram</label>
                                    <input type="url" name="instagram" class="form-control" value="{{ setting('instagram', '') }}">
                                    <input type="hidden" name="_groups[instagram]" value="social">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-twitter text-info me-1"></i> Twitter / X</label>
                                    <input type="url" name="twitter" class="form-control" value="{{ setting('twitter', '') }}">
                                    <input type="hidden" name="_groups[twitter]" value="social">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-linkedin text-primary me-1"></i> LinkedIn</label>
                                    <input type="url" name="linkedin" class="form-control" value="{{ setting('linkedin', '') }}">
                                    <input type="hidden" name="_groups[linkedin]" value="social">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-youtube text-danger me-1"></i> YouTube</label>
                                    <input type="url" name="youtube" class="form-control" value="{{ setting('youtube', '') }}">
                                    <input type="hidden" name="_groups[youtube]" value="social">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-telegram text-info me-1"></i> Telegram</label>
                                    <input type="url" name="telegram" class="form-control" value="{{ setting('telegram', '') }}">
                                    <input type="hidden" name="_groups[telegram]" value="social">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-whatsapp text-success me-1"></i> WhatsApp Channel</label>
                                    <input type="url" name="whatsapp_channel" class="form-control" value="{{ setting('whatsapp_channel', '') }}">
                                    <input type="hidden" name="_groups[whatsapp_channel]" value="social">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-github text-dark me-1"></i> GitHub</label>
                                    <input type="url" name="github" class="form-control" value="{{ setting('github', '') }}">
                                    <input type="hidden" name="_groups[github]" value="social">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold"><i class="bi bi-discord text-primary me-1"></i> Discord</label>
                                    <input type="url" name="discord" class="form-control" value="{{ setting('discord', '') }}">
                                    <input type="hidden" name="_groups[discord]" value="social">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 6: SEO -->
                        @if($activeTab === 'seo')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-search me-2"></i> Search Engine Optimization & Tracking</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Meta Title</label>
                                    <input type="text" name="meta_title" class="form-control" value="{{ setting('meta_title', 'DoorKnob - White Label Education ERP') }}">
                                    <input type="hidden" name="_groups[meta_title]" value="seo">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Meta Keywords</label>
                                    <input type="text" name="keywords" class="form-control" value="{{ setting('keywords', 'school management, ERP, education software') }}">
                                    <input type="hidden" name="_groups[keywords]" value="seo">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label font-weight-bold">Meta Description</label>
                                    <textarea name="meta_description" class="form-control" rows="2">{{ setting('meta_description', 'Next generation school management software.') }}</textarea>
                                    <input type="hidden" name="_groups[meta_description]" value="seo">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Google Analytics Tracking Script / ID</label>
                                    <textarea name="google_analytics" class="form-control font-monospace" rows="3">{{ setting('google_analytics', '') }}</textarea>
                                    <input type="hidden" name="_groups[google_analytics]" value="seo">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Custom Head Script (&lt;head&gt;)</label>
                                    <textarea name="custom_head_script" class="form-control font-monospace" rows="3">{{ setting('custom_head_script', '') }}</textarea>
                                    <input type="hidden" name="_groups[custom_head_script]" value="seo">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 7: LOGIN PAGE -->
                        @if($activeTab === 'login')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-box-arrow-in-right me-2"></i> Custom Login Page Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Welcome Title</label>
                                    <input type="text" name="login_welcome_title" class="form-control" value="{{ setting('login_welcome_title', 'Welcome to DoorKnob ERP') }}">
                                    <input type="hidden" name="_groups[login_welcome_title]" value="login">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Welcome Subtitle</label>
                                    <input type="text" name="login_welcome_subtitle" class="form-control" value="{{ setting('login_welcome_subtitle', 'Please sign in to access your portal') }}">
                                    <input type="hidden" name="_groups[login_welcome_subtitle]" value="login">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Login Button Color</label>
                                    <input type="color" name="login_button_color" class="form-control form-control-color w-100" value="{{ setting('login_button_color', '#0d6efd') }}">
                                    <input type="hidden" name="_groups[login_button_color]" value="login">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Login Card Color</label>
                                    <input type="color" name="login_card_color" class="form-control form-control-color w-100" value="{{ setting('login_card_color', '#ffffff') }}">
                                    <input type="hidden" name="_groups[login_card_color]" value="login">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Show Org Name on Login</label>
                                    <select name="show_org_name_on_login" class="form-select">
                                        <option value="1" {{ setting('show_org_name_on_login', '1') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ setting('show_org_name_on_login', '1') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                    <input type="hidden" name="_groups[show_org_name_on_login]" value="login">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Login Background Image</label>
                                    @if(setting_asset('login_bg_image'))
                                        <div class="mb-2 p-2 border rounded text-center">
                                            <img src="{{ setting_asset('login_bg_image') }}" style="max-height: 80px;">
                                        </div>
                                    @endif
                                    <input type="file" name="login_bg_image" class="form-control" accept="image/*">
                                    <input type="hidden" name="_groups[login_bg_image]" value="login">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 8: EMAIL BRANDING -->
                        @if($activeTab === 'email')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-envelope me-2"></i> Email Branding & Footers</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Email Support Address</label>
                                    <input type="email" name="email_support_address" class="form-control" value="{{ setting('email_support_address', 'support@doorknob.io') }}">
                                    <input type="hidden" name="_groups[email_support_address]" value="email">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Reply-To Address</label>
                                    <input type="email" name="email_reply_to" class="form-control" value="{{ setting('email_reply_to', 'no-reply@doorknob.io') }}">
                                    <input type="hidden" name="_groups[email_reply_to]" value="email">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label font-weight-bold">Email Footer Text</label>
                                    <input type="text" name="email_footer" class="form-control" value="{{ setting('email_footer', 'DoorKnob ERP - School Administration System') }}">
                                    <input type="hidden" name="_groups[email_footer]" value="email">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label font-weight-bold">Email Signature HTML</label>
                                    <textarea name="email_signature" class="form-control" rows="3">{{ setting('email_signature', 'Best regards,<br>DoorKnob ERP Administration') }}</textarea>
                                    <input type="hidden" name="_groups[email_signature]" value="email">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 9: REPORTS & PDF -->
                        @if($activeTab === 'reports')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-file-earmark-pdf me-2"></i> Printable Reports & PDF Header Branding</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Report Footer Disclaimer</label>
                                    <input type="text" name="report_footer_text" class="form-control" value="{{ setting('report_footer_text', 'This is a computer-generated document. No signature required.') }}">
                                    <input type="hidden" name="_groups[report_footer_text]" value="reports">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Show Watermark on Reports</label>
                                    <select name="show_watermark_on_report" class="form-select">
                                        <option value="1" {{ setting('show_watermark_on_report', '1') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ setting('show_watermark_on_report', '1') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                    <input type="hidden" name="_groups[show_watermark_on_report]" value="reports">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label font-weight-bold">Report Primary Color</label>
                                    <input type="color" name="report_primary_color" class="form-control form-control-color w-100" value="{{ setting('report_primary_color', '#0d6efd') }}">
                                    <input type="hidden" name="_groups[report_primary_color]" value="reports">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 10: SYSTEM -->
                        @if($activeTab === 'system')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-cpu me-2"></i> System Preferences</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Maintenance Mode</label>
                                    <select name="maintenance_mode" class="form-select">
                                        <option value="0" {{ setting('maintenance_mode', '0') == '0' ? 'selected' : '' }}>Disabled (Normal Operation)</option>
                                        <option value="1" {{ setting('maintenance_mode', '0') == '1' ? 'selected' : '' }}>Enabled (Maintenance Mode)</option>
                                    </select>
                                    <input type="hidden" name="_groups[maintenance_mode]" value="system">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">SMS Notifications</label>
                                    <select name="sms_enabled" class="form-select">
                                        <option value="1" {{ setting('sms_enabled', '1') == '1' ? 'selected' : '' }}>Enabled</option>
                                        <option value="0" {{ setting('sms_enabled', '1') == '0' ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <input type="hidden" name="_groups[sms_enabled]" value="system">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">WhatsApp Notifications</label>
                                    <select name="whatsapp_enabled" class="form-select">
                                        <option value="1" {{ setting('whatsapp_enabled', '1') == '1' ? 'selected' : '' }}>Enabled</option>
                                        <option value="0" {{ setting('whatsapp_enabled', '1') == '0' ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                    <input type="hidden" name="_groups[whatsapp_enabled]" value="system">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Default Pagination Items</label>
                                    <input type="number" name="default_pagination" class="form-control" value="{{ setting('default_pagination', '15') }}">
                                    <input type="hidden" name="_groups[default_pagination]" value="system">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Max Upload Size (KB)</label>
                                    <input type="number" name="upload_max_size" class="form-control" value="{{ setting('upload_max_size', '10240') }}">
                                    <input type="hidden" name="_groups[upload_max_size]" value="system">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label font-weight-bold">Session Timeout (Minutes)</label>
                                    <input type="number" name="session_timeout" class="form-control" value="{{ setting('session_timeout', '120') }}">
                                    <input type="hidden" name="_groups[session_timeout]" value="system">
                                </div>
                            </div>
                        @endif

                        <!-- TAB 11: ADVANCED -->
                        @if($activeTab === 'advanced')
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-code-slash me-2"></i> Custom CSS / JS & Backup Restore</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Custom CSS Editor</label>
                                    <textarea name="custom_css" class="form-control font-monospace" rows="6" placeholder="/* Inject custom CSS rules */">{{ setting('custom_css', '') }}</textarea>
                                    <input type="hidden" name="_groups[custom_css]" value="advanced">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label font-weight-bold">Custom JavaScript Editor</label>
                                    <textarea name="custom_js" class="form-control font-monospace" rows="6" placeholder="// Inject custom JS scripts">{{ setting('custom_js', '') }}</textarea>
                                    <input type="hidden" name="_groups[custom_js]" value="advanced">
                                </div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>

                    <!-- JSON Import Form for Advanced tab -->
                    @if($activeTab === 'advanced')
                        <hr class="my-4">
                        <div class="card bg-light border">
                            <div class="card-body">
                                <h6 class="fw-bold text-dark"><i class="bi bi-upload me-1"></i> Restore Settings from JSON Backup</h6>
                                <p class="text-muted small">Upload a previously exported DoorKnob settings JSON file to restore configurations.</p>
                                <form action="{{ route('settings.import') }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                                    @csrf
                                    <input type="file" name="settings_json" class="form-control" accept=".json,.txt" required>
                                    <button type="submit" class="btn btn-secondary px-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Restore</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
