<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $reportConfig['org_name'] . ' Report')</title>

    <style>
        /* CSS Reset & Font Setup */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --report-primary: {{ $reportConfig['primary_color'] ?? '#0d6efd' }};
            --report-secondary: {{ $reportConfig['secondary_color'] ?? '#6c757d' }};
            --report-table-bg: {{ $reportConfig['table_header_bg'] ?? '#0d6efd' }};
            --report-table-color: {{ $reportConfig['table_header_color'] ?? '#ffffff' }};
            --report-font: {{ $reportConfig['font_family'] ?? "'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif" }};
            --report-font-size: {{ $reportConfig['font_size'] ?? '13px' }};
            --report-border-color: #dee2e6;
            --report-bg-light: #f8f9fa;
        }

        body {
            font-family: var(--report-font);
            font-size: var(--report-font-size);
            color: #212529;
            background-color: {{ $isPdfRender ? '#ffffff' : '#f0f2f5' }};
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen Preview Wrapper */
        .report-preview-container {
            max-width: 210mm;
            margin: {{ $isPdfRender ? '0' : '25px auto' }};
            padding: {{ $isPdfRender ? '0' : '20px' }};
        }

        .report-page-sheet {
            background: #ffffff;
            position: relative;
            box-shadow: {{ $isPdfRender ? 'none' : '0 4px 20px rgba(0, 0, 0, 0.08)' }};
            border-radius: {{ $isPdfRender ? '0' : '6px' }};
            padding: {{ $isPdfRender ? '0' : '30px 35px' }};
            min-height: {{ $isPdfRender ? 'auto' : '297mm' }};
            overflow: hidden;
        }

        /* No-Print Web Action Toolbar */
        .report-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .report-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease-in-out;
        }

        .report-btn-primary {
            background-color: var(--report-primary);
            color: #ffffff;
        }
        .report-btn-primary:hover {
            opacity: 0.9;
            color: #ffffff;
        }

        .report-btn-secondary {
            background-color: #6c757d;
            color: #ffffff;
        }
        .report-btn-secondary:hover {
            background-color: #5a6268;
            color: #ffffff;
        }

        .report-btn-outline {
            background-color: transparent;
            border-color: #ced4da;
            color: #495057;
        }
        .report-btn-outline:hover {
            background-color: #e9ecef;
        }

        /* Watermark Layer */
        .report-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate({{ $reportConfig['watermark_rotation'] }}deg);
            z-index: 0;
            pointer-events: none;
            opacity: {{ $reportConfig['watermark_opacity'] }};
            user-select: none;
            text-align: center;
            width: 100%;
        }

        .report-watermark-text {
            font-size: {{ $reportConfig['watermark_font_size'] }};
            font-weight: 900;
            color: {{ $reportConfig['watermark_color'] }};
            text-transform: uppercase;
            letter-spacing: 4px;
            white-space: nowrap;
        }

        .report-watermark-image {
            max-width: 60%;
            max-height: 400px;
            object-fit: contain;
        }

        /* Report Body Content Container (Above Watermark) */
        .report-content {
            position: relative;
            z-index: 1;
        }

        /* Header / Letterhead Styles */
        .report-header-wrapper {
            margin-bottom: 20px;
            {{ $reportConfig['header_bottom_border'] ? 'border-bottom: 2.5px solid ' . ($reportConfig['primary_color'] ?? '#0d6efd') . ';' : '' }}
            padding-bottom: 14px;
        }

        .letterhead-standard {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .letterhead-center {
            text-align: center;
        }

        .letterhead-logo {
            max-height: {{ $reportConfig['logo_height'] ?? '70px' }};
            max-width: 180px;
            object-fit: contain;
        }

        .school-name-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--report-primary);
            letter-spacing: 0.3px;
            line-height: 1.2;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .school-tagline {
            font-size: 11px;
            color: #6c757d;
            font-style: italic;
            margin-bottom: 4px;
        }

        .school-meta-line {
            font-size: 10.5px;
            color: #495057;
            line-height: 1.4;
        }

        .school-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
            font-size: 10px;
        }

        .school-badge {
            display: inline-block;
            background: #eef2f6;
            color: #334155;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
        }

        /* Document Title Banner */
        .doc-title-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--report-border-color);
            border-left: 4px solid var(--report-primary);
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 18px;
        }

        .doc-title-main {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 1px;
        }

        .doc-meta-right {
            text-align: right;
            font-size: 11px;
            color: #475569;
        }

        .doc-meta-badge {
            display: inline-block;
            background: var(--report-primary);
            color: #ffffff;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            margin-bottom: 2px;
        }

        /* Information Summary Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .info-box {
            background: #ffffff;
            border: 1px solid var(--report-border-color);
            border-radius: 4px;
            padding: 10px 12px;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--report-primary);
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding: 2.5px 0;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            color: #1e293b;
            font-weight: 700;
            text-align: right;
        }

        /* Metric KPI Highlight Cards */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }

        .kpi-title {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }

        .kpi-value {
            font-size: 15px;
            font-weight: 800;
            color: var(--report-primary);
        }

        /* Report Tables */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11.5px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid var(--report-border-color);
            padding: 6px 8px;
            vertical-align: middle;
        }

        .report-table thead th {
            background-color: var(--report-table-bg);
            color: var(--report-table-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10.5px;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .report-table tbody tr:nth-child(even) {
            background-color: #fcfcfd;
        }

        .report-table tfoot td {
            background-color: #f1f5f9;
            font-weight: 700;
            border-top: 2px solid #94a3b8;
        }

        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        .fw-bold { font-weight: 700 !important; }
        .text-success { color: #16a34a !important; }
        .text-danger { color: #dc2626 !important; }
        .text-muted { color: #64748b !important; }

        /* Signatures & Verification Block */
        .report-signature-block {
            margin-top: 30px;
            margin-bottom: 15px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            padding-top: 10px;
        }

        .signature-item {
            text-align: center;
            flex: 1;
        }

        .signature-image-container {
            height: 55px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            margin-bottom: 6px;
        }

        .signature-img {
            max-height: 50px;
            max-width: 140px;
            object-fit: contain;
        }

        .signature-stamp-img {
            max-height: 60px;
            max-width: 90px;
            object-fit: contain;
            opacity: 0.85;
        }

        .signature-line {
            border-top: 1px dashed #64748b;
            margin: 0 auto 5px auto;
            width: 140px;
        }

        .signature-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
        }

        .signature-sublabel {
            font-size: 9.5px;
            color: #64748b;
        }

        /* Footer Notes & Legal */
        .report-footer-section {
            border-top: 1px solid var(--report-border-color);
            padding-top: 10px;
            margin-top: 20px;
            font-size: 9.5px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Print Media Styles */
        @media print {
            @page {
                size: {{ $reportConfig['paper_size'] ?? 'A4' }} {{ $reportConfig['orientation'] ?? 'portrait' }};
                margin: {{ $reportConfig['margin_top'] ?? '10mm' }} {{ $reportConfig['margin_right'] ?? '10mm' }} {{ $reportConfig['margin_bottom'] ?? '10mm' }} {{ $reportConfig['margin_left'] ?? '10mm' }};
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .report-preview-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .report-page-sheet {
                box-shadow: none !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }

            .no-print, .report-toolbar {
                display: none !important;
            }

            .report-table thead {
                display: table-header-group;
            }

            .report-table tfoot {
                display: table-footer-group;
            }

            .report-table tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .page-break {
                page-break-before: always;
                break-before: always;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

<div class="report-preview-container">
    {{-- Web Action Bar for HTML preview mode --}}
    @if(!$isPdfRender)
        <div class="report-toolbar no-print">
            <div>
                <strong style="color: var(--report-primary); font-size: 14px;">
                    <svg style="width: 16px; height: 16px; vertical-align: -2px; fill: currentColor;" viewBox="0 0 16 16">
                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                    </svg>
                    Report Preview
                </strong>
                <span class="text-muted ms-2" style="font-size: 12px;">| Paper: {{ $reportConfig['paper_size'] }} ({{ ucfirst($reportConfig['orientation']) }})</span>
            </div>
            <div style="display: flex; gap: 8px;">
                <button onclick="window.print()" class="report-btn report-btn-outline">
                    <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 16 16">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    Browser Print
                </button>
                @if(isset($pdfDownloadUrl))
                    <a href="{{ $pdfDownloadUrl }}" class="report-btn report-btn-primary" target="_blank">
                        <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 16 16">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                        </svg>
                        Download PDF
                    </a>
                @endif
                <button onclick="window.close()" class="report-btn report-btn-outline">Close</button>
            </div>
        </div>
    @endif

    <div class="report-page-sheet">
        {{-- Watermark Layer --}}
        @if(($reportConfig['watermark_type'] ?? 'none') === 'text' && !empty($reportConfig['watermark_text']))
            <div class="report-watermark">
                <div class="report-watermark-text">{{ $reportConfig['watermark_text'] }}</div>
            </div>
        @elseif(($reportConfig['watermark_type'] ?? 'none') === 'image' && !empty($reportConfig['watermark_image_base64']))
            <div class="report-watermark">
                <img src="{{ $reportConfig['watermark_image_base64'] }}" alt="Watermark" class="report-watermark-image">
            </div>
        @endif

        {{-- Main Report Document Container --}}
        <div class="report-content">
            {{-- Header / Letterhead Component --}}
            <header class="report-header-wrapper">
                @if(($reportConfig['header_style'] ?? 'standard') === 'center' || ($reportConfig['header_align'] ?? '') === 'center')
                    {{-- Centered Letterhead Layout --}}
                    <div class="letterhead-center">
                        @if($reportConfig['show_logo'] && !empty($reportConfig['logo_base64']))
                            <img src="{{ $reportConfig['logo_base64'] }}" alt="{{ $reportConfig['org_name'] }}" class="letterhead-logo" style="margin-bottom: 6px;">
                        @endif
                        <h1 class="school-name-title">{{ $reportConfig['org_name'] }}</h1>
                        @if(!empty($reportConfig['tagline']))
                            <p class="school-tagline">{{ $reportConfig['tagline'] }}</p>
                        @endif
                        @if($reportConfig['header_show_address'] && !empty($reportConfig['full_address']))
                            <p class="school-meta-line">{{ $reportConfig['full_address'] }}</p>
                        @endif
                        @if($reportConfig['header_show_contact'])
                            <p class="school-meta-line">
                                @if(!empty($reportConfig['phone'])) Phone: {{ $reportConfig['phone'] }} @endif
                                @if(!empty($reportConfig['email'])) | Email: {{ $reportConfig['email'] }} @endif
                                @if(!empty($reportConfig['website'])) | Web: {{ $reportConfig['website'] }} @endif
                            </p>
                        @endif
                        @if($reportConfig['header_show_affiliation'])
                            <div class="school-badges" style="justify-content: center;">
                                @if(!empty($reportConfig['affiliation_number']))
                                    <span class="school-badge">Affiliation: {{ $reportConfig['affiliation_number'] }}</span>
                                @endif
                                @if(!empty($reportConfig['school_code']))
                                    <span class="school-badge">School Code: {{ $reportConfig['school_code'] }}</span>
                                @endif
                                @if(!empty($reportConfig['udise_code']))
                                    <span class="school-badge">UDISE: {{ $reportConfig['udise_code'] }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Standard / Left-Aligned Letterhead Layout --}}
                    <div class="letterhead-standard">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            @if($reportConfig['show_logo'] && !empty($reportConfig['logo_base64']) && ($reportConfig['logo_position'] ?? 'left') === 'left')
                                <img src="{{ $reportConfig['logo_base64'] }}" alt="{{ $reportConfig['org_name'] }}" class="letterhead-logo">
                            @endif
                            <div>
                                <h1 class="school-name-title">{{ $reportConfig['org_name'] }}</h1>
                                @if(!empty($reportConfig['tagline']))
                                    <p class="school-tagline">{{ $reportConfig['tagline'] }}</p>
                                @endif
                                @if($reportConfig['header_show_address'] && !empty($reportConfig['full_address']))
                                    <p class="school-meta-line">{{ $reportConfig['full_address'] }}</p>
                                @endif
                                @if($reportConfig['header_show_contact'])
                                    <p class="school-meta-line">
                                        @if(!empty($reportConfig['phone'])) Tel: {{ $reportConfig['phone'] }} @endif
                                        @if(!empty($reportConfig['email'])) | Email: {{ $reportConfig['email'] }} @endif
                                        @if(!empty($reportConfig['website'])) | {{ $reportConfig['website'] }} @endif
                                    </p>
                                @endif
                                @if($reportConfig['header_show_affiliation'])
                                    <div class="school-badges">
                                        @if(!empty($reportConfig['affiliation_number']))
                                            <span class="school-badge">Affil. No: {{ $reportConfig['affiliation_number'] }}</span>
                                        @endif
                                        @if(!empty($reportConfig['school_code']))
                                            <span class="school-badge">Code: {{ $reportConfig['school_code'] }}</span>
                                        @endif
                                        @if(!empty($reportConfig['udise_code']))
                                            <span class="school-badge">UDISE: {{ $reportConfig['udise_code'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if($reportConfig['show_logo'] && !empty($reportConfig['logo_base64']) && ($reportConfig['logo_position'] ?? '') === 'right')
                            <img src="{{ $reportConfig['logo_base64'] }}" alt="{{ $reportConfig['org_name'] }}" class="letterhead-logo">
                        @endif
                    </div>
                @endif
            </header>

            {{-- Document Title Bar --}}
            <div class="doc-title-bar">
                <div>
                    <h2 class="doc-title-main">@yield('document-title', 'ACADEMIC & ADMINISTRATIVE REPORT')</h2>
                    <p class="doc-subtitle">@yield('document-subtitle', 'Official Institutional Record')</p>
                </div>
                <div class="doc-meta-right">
                    @hasSection('document-number')
                        <div class="doc-meta-badge">@yield('document-number')</div>
                    @endif
                    <div><strong>Date:</strong> @yield('document-date', date('d M Y'))</div>
                    @hasSection('document-session')
                        <div><strong>Session:</strong> @yield('document-session')</div>
                    @endif
                </div>
            </div>

            {{-- Report Body Content Slot --}}
            <main>
                @yield('content')
            </main>

            {{-- Signatures & Stamp Block --}}
            @if($reportConfig['show_signatures'])
                <div class="report-signature-block">
                    <div class="signature-grid">
                        {{-- Signature 1: Signatory / Prepared By --}}
                        <div class="signature-item">
                            <div class="signature-image-container">
                                @if(!empty($reportConfig['authorized_signature_base64']))
                                    <img src="{{ $reportConfig['authorized_signature_base64'] }}" alt="Signature" class="signature-img">
                                @endif
                            </div>
                            <div class="signature-line"></div>
                            <div class="signature-label">{{ $reportConfig['signature_label_2'] ?? 'Authorized Signatory' }}</div>
                            <div class="signature-sublabel">Office Administration</div>
                        </div>

                        {{-- Official School Stamp --}}
                        @if($reportConfig['show_stamp'])
                            <div class="signature-item" style="flex: 0.8;">
                                <div class="signature-image-container">
                                    @if(!empty($reportConfig['stamp_base64']))
                                        <img src="{{ $reportConfig['stamp_base64'] }}" alt="Official Stamp" class="signature-stamp-img">
                                    @else
                                        <div style="border: 2px dashed #94a3b8; border-radius: 50%; width: 55px; height: 55px; display: inline-flex; align-items: center; justify-content: center; font-size: 8.5px; color: #94a3b8; font-weight: bold; text-transform: uppercase;">
                                            Seal
                                        </div>
                                    @endif
                                </div>
                                <div class="signature-label" style="font-size: 9.5px; color: #64748b;">Institution Seal</div>
                            </div>
                        @endif

                        {{-- Signature 2: Principal / Head of Institution --}}
                        <div class="signature-item">
                            <div class="signature-image-container">
                                @if(!empty($reportConfig['principal_signature_base64']))
                                    <img src="{{ $reportConfig['principal_signature_base64'] }}" alt="Principal Signature" class="signature-img">
                                @endif
                            </div>
                            <div class="signature-line"></div>
                            <div class="signature-label">{{ $reportConfig['signature_label_1'] ?? 'Principal Signature' }}</div>
                            <div class="signature-sublabel">
                                @if(!empty($reportConfig['principal_name']))
                                    {{ $reportConfig['principal_name'] }}
                                @else
                                    Head of Institution
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Footer Section --}}
            <footer class="report-footer-section">
                <div>
                    <span>{{ $reportConfig['footer_text'] }}</span>
                    @if(!empty($reportConfig['footer_custom_note']))
                        <span style="display: block; font-size: 8.5px; color: #94a3b8;">{{ $reportConfig['footer_custom_note'] }}</span>
                    @endif
                </div>
                <div style="text-align: right;">
                    @if($reportConfig['show_footer_date'])
                        <span>Generated on {{ date('d-m-Y H:i:s') }}</span>
                    @endif
                    @if($reportConfig['show_footer_user'] && Auth::check())
                        <span> | By: {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</span>
                    @endif
                    <span style="display: block; font-size: 8.5px;">{{ $reportConfig['copyright'] }}</span>
                </div>
            </footer>
        </div>
    </div>
</div>

</body>
</html>
