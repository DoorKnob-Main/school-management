@props([
    'title' => null,
    'subtitle' => null,
    'docNumber' => null,
    'date' => null
])

@php
    $orgLogo = setting_asset('logo') ?? setting_asset('light_logo') ?? setting_asset('dark_logo');
    $orgName = setting('organization_name', setting('software_name', 'DoorKnob'));
    $tagline = setting('tagline', 'School Management ERP');
    $address = setting('address', '');
    $cityState = implode(', ', array_filter([setting('city'), setting('state'), setting('zip_code')]));
    $phone = setting('phone', '');
    $email = setting('email', '');
    $website = setting('website', '');
    $primaryColor = setting('primary_color', '#0d6efd');
@endphp

<div class="report-header mb-4" style="border-bottom: 3px solid {{ $primaryColor }}; padding-bottom: 15px;">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            @if($orgLogo)
                <img src="{{ $orgLogo }}" alt="{{ $orgName }}" style="max-height: 70px; max-width: 180px;" class="me-3">
            @endif
            <div>
                <h2 class="fw-bold mb-0" style="color: {{ $primaryColor }}; font-size: 1.6rem;">{{ $orgName }}</h2>
                @if($tagline)
                    <p class="text-muted mb-1 fst-italic small">{{ $tagline }}</p>
                @endif
                <p class="mb-0 text-secondary" style="font-size: 0.82rem;">
                    @if($address) {{ $address }} | @endif
                    @if($cityState) {{ $cityState }} | @endif
                    @if($phone) Phone: {{ $phone }} | @endif
                    @if($email) Email: {{ $email }} @endif
                </p>
            </div>
        </div>
        @if($title || $docNumber)
            <div class="text-end">
                @if($title)
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.2rem;">{{ $title }}</h4>
                @endif
                @if($subtitle)
                    <small class="text-muted d-block">{{ $subtitle }}</small>
                @endif
                @if($docNumber)
                    <span class="badge bg-secondary mt-1">{{ $docNumber }}</span>
                @endif
                @if($date)
                    <small class="text-muted d-block mt-1">Date: {{ $date }}</small>
                @endif
            </div>
        @endif
    </div>
</div>
