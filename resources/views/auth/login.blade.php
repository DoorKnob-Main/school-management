@extends('layouts.app')

@section('content')
@php
    $loginBg = setting_asset('login_bg_image');
    $loginLogo = setting_asset('login_logo') ?? setting_asset('logo');
    $welcomeTitle = setting('login_welcome_title', 'Welcome to ' . setting('software_name', 'DoorKnob'));
    $welcomeSubtitle = setting('login_welcome_subtitle', 'Please sign in to access your portal');
    $buttonColor = setting('login_button_color', setting('primary_color', '#0d6efd'));
    $cardColor = setting('login_card_color', '#ffffff');
    $showOrg = setting('show_org_name_on_login', '1');
    $orgName = setting('organization_name', setting('software_name', 'DoorKnob'));
@endphp

<div class="container-fluid py-5" style="@if($loginBg) background: url('{{ $loginBg }}') no-repeat center center / cover; min-height: 85vh; @endif">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0" style="background-color: {{ $cardColor }}; border-radius: {{ setting('card_radius', '12px') }};">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            @if($loginLogo)
                                <img src="{{ $loginLogo }}" alt="{{ $orgName }}" class="mb-3" style="max-height: 75px; max-width: 220px;">
                            @else
                                <i class="bi bi-mortarboard-fill text-primary display-4 mb-2"></i>
                            @endif

                            @if($showOrg && $orgName)
                                <h5 class="fw-bold text-secondary mb-1">{{ $orgName }}</h5>
                            @endif
                            
                            <h3 class="fw-bold text-dark mb-1">{{ $welcomeTitle }}</h3>
                            <p class="text-muted small mb-0">{{ $welcomeSubtitle }}</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label font-weight-bold">{{ __('E-Mail Address / Username') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input id="email" type="text" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="DOORKNOB@SU or admin@ut.com">
                                </div>
                                @error('email')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @errorClass
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label font-weight-bold">{{ __('Password') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                                </div>
                                @error('password')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link p-0 small text-decoration-none" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary py-2 text-uppercase fw-bold" style="background-color: {{ $buttonColor }} !important; border-color: {{ $buttonColor }} !important;">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Sign In') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    @if(setting('show_powered_by', '1') && setting('powered_by_text'))
                        <div class="card-footer bg-light text-center py-3 border-0 rounded-bottom">
                            <small class="text-muted">{{ setting('powered_by_text') }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
