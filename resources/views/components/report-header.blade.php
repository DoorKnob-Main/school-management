@props([
    'title' => null,
    'subtitle' => null,
    'docNumber' => null,
    'date' => null,
    'session' => null,
])

@php
    $orgLogo = setting_asset('logo') ?? setting_asset('light_logo') ?? setting_asset('dark_logo');
    $orgName = setting('report_school_name', setting('organization_name', setting('software_name', 'DoorKnob')));
    $tagline = setting('tagline', 'School Management ERP');
    $address = setting('address', '');
    $cityState = implode(', ', array_filter([setting('city'), setting('state'), setting('zip_code')]));
    $phone = setting('phone', setting('mobile', ''));
    $email = setting('email', setting('support_email', ''));
    $website = setting('website', '');
    $primaryColor = setting('report_primary_color', setting('primary_color', '#0d6efd'));
    $schoolCode = setting('school_code', '');
    $affiliationNumber = setting('affiliation_number', '');
    $udiseCode = setting('udise_code', '');
    $showLogo = (bool)setting('report_show_logo', 1);
    $logoHeight = setting('report_logo_height', '70px');
    $logoPosition = setting('report_logo_position', 'left');
    $showBorder = (bool)setting('report_header_bottom_border', 1);
    $headerAlign = setting('report_header_align', 'left');
@endphp

<div class="report-header mb-4" style="{{ $showBorder ? 'border-bottom: 2.5px solid ' . $primaryColor . ';' : '' }} padding-bottom: 14px;">
    @if($headerAlign === 'center')
        <div class="text-center">
            @if($showLogo && $orgLogo)
                <img src="{{ $orgLogo }}" alt="{{ $orgName }}" style="max-height: {{ $logoHeight }}; max-width: 180px; margin-bottom: 6px;">
            @endif
            <h2 class="fw-bold mb-0 text-uppercase" style="color: {{ $primaryColor }}; font-size: 1.5rem; letter-spacing: 0.5px;">{{ $orgName }}</h2>
            @if($tagline)
                <p class="text-muted mb-1 fst-italic small">{{ $tagline }}</p>
            @endif
            <p class="mb-1 text-secondary small">
                @if($address) {{ $address }} | @endif
                @if($cityState) {{ $cityState }} | @endif
                @if($phone) Phone: {{ $phone }} | @endif
                @if($email) Email: {{ $email }} @endif
            </p>
            @if($affiliationNumber || $schoolCode || $udiseCode)
                <div class="d-flex justify-content-center gap-2 mt-1">
                    @if($affiliationNumber) <span class="badge bg-light text-dark border">Affil: {{ $affiliationNumber }}</span> @endif
                    @if($schoolCode) <span class="badge bg-light text-dark border">Code: {{ $schoolCode }}</span> @endif
                    @if($udiseCode) <span class="badge bg-light text-dark border">UDISE: {{ $udiseCode }}</span> @endif
                </div>
            @endif
        </div>
        @if($title || $docNumber)
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                <div>
                    @if($title) <h5 class="fw-bold mb-0 text-dark">{{ $title }}</h5> @endif
                    @if($subtitle) <small class="text-muted">{{ $subtitle }}</small> @endif
                </div>
                <div class="text-end">
                    @if($docNumber) <span class="badge bg-primary">{{ $docNumber }}</span> @endif
                    @if($date) <small class="text-muted d-block mt-1">Date: {{ $date }}</small> @endif
                </div>
            </div>
        @endif
    @else
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                @if($showLogo && $orgLogo && $logoPosition === 'left')
                    <img src="{{ $orgLogo }}" alt="{{ $orgName }}" style="max-height: {{ $logoHeight }}; max-width: 180px;" class="me-3">
                @endif
                <div>
                    <h2 class="fw-bold mb-0 text-uppercase" style="color: {{ $primaryColor }}; font-size: 1.45rem; letter-spacing: 0.3px;">{{ $orgName }}</h2>
                    @if($tagline)
                        <p class="text-muted mb-1 fst-italic small">{{ $tagline }}</p>
                    @endif
                    <p class="mb-0 text-secondary" style="font-size: 0.82rem;">
                        @if($address) {{ $address }} | @endif
                        @if($cityState) {{ $cityState }} | @endif
                        @if($phone) Tel: {{ $phone }} | @endif
                        @if($email) {{ $email }} @endif
                    </p>
                    @if($affiliationNumber || $schoolCode || $udiseCode)
                        <div class="d-flex gap-2 mt-1">
                            @if($affiliationNumber) <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">Affil: {{ $affiliationNumber }}</span> @endif
                            @if($schoolCode) <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">Code: {{ $schoolCode }}</span> @endif
                            @if($udiseCode) <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">UDISE: {{ $udiseCode }}</span> @endif
                        </div>
                    @endif
                </div>
            </div>
            @if($showLogo && $orgLogo && $logoPosition === 'right')
                <img src="{{ $orgLogo }}" alt="{{ $orgName }}" style="max-height: {{ $logoHeight }}; max-width: 180px;" class="ms-3">
            @endif
            @if($title || $docNumber)
                <div class="text-end">
                    @if($title)
                        <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">{{ $title }}</h4>
                    @endif
                    @if($subtitle)
                        <small class="text-muted d-block">{{ $subtitle }}</small>
                    @endif
                    @if($docNumber)
                        <span class="badge bg-primary mt-1">{{ $docNumber }}</span>
                    @endif
                    @if($date)
                        <small class="text-muted d-block mt-1">Date: {{ $date }}</small>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
