<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as HttpResponse;

class ReportEngineService
{
    /**
     * @var SettingService
     */
    protected $settingService;

    /**
     * ReportEngineService constructor.
     *
     * @param SettingService $settingService
     */
    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Retrieve all compiled report settings with fallbacks and base64-encoded assets.
     *
     * @param array $overrides
     * @return array
     */
    public function getReportConfig(array $overrides = []): array
    {
        $settings = $this->settingService->all();

        // Primary Branding
        $orgName = $settings['report_school_name'] ?? $settings['organization_name'] ?? $settings['software_name'] ?? 'DoorKnob Academy';
        $tagline = $settings['tagline'] ?? 'School Management ERP';
        $schoolCode = $settings['school_code'] ?? '';
        $affiliationNo = $settings['affiliation_number'] ?? '';
        $registrationNo = $settings['registration_number'] ?? '';
        $udiseCode = $settings['udise_code'] ?? '';
        $address = $settings['address'] ?? '';
        $city = $settings['city'] ?? '';
        $state = $settings['state'] ?? '';
        $zipCode = $settings['zip_code'] ?? '';
        $phone = $settings['phone'] ?? $settings['mobile'] ?? '';
        $email = $settings['email'] ?? $settings['support_email'] ?? '';
        $website = $settings['website'] ?? '';

        // Colors & Typography
        $primaryColor = $settings['report_primary_color'] ?? $settings['theme.primary_color'] ?? '#0d6efd';
        $secondaryColor = $settings['report_secondary_color'] ?? '#6c757d';
        $tableHeaderBg = $settings['report_table_header_bg'] ?? $primaryColor;
        $tableHeaderColor = $settings['report_table_header_color'] ?? '#ffffff';
        $fontFamily = $settings['report_font_family'] ?? "'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
        $fontSize = $settings['report_font_size'] ?? '13px';

        // Header & Letterhead Layout
        $headerStyle = $settings['report_header_style'] ?? 'standard'; // standard, modern, minimal, classic, boxed
        $headerAlign = $settings['report_header_align'] ?? 'center'; // left, center, right
        $showLogo = (bool)($settings['report_show_logo'] ?? 1);
        $logoPosition = $settings['report_logo_position'] ?? 'left'; // left, center, right
        $logoHeight = $settings['report_logo_height'] ?? '70px';
        $showHeaderAddress = (bool)($settings['report_header_show_address'] ?? 1);
        $showHeaderContact = (bool)($settings['report_header_show_contact'] ?? 1);
        $showHeaderAffiliation = (bool)($settings['report_header_show_affiliation'] ?? 1);
        $showHeaderBorder = (bool)($settings['report_header_bottom_border'] ?? 1);
        $headerCustomText = $settings['report_header_custom_text'] ?? '';

        // Watermark Configuration
        $watermarkType = $settings['report_watermark_type'] ?? ((bool)($settings['show_watermark_on_report'] ?? 1) ? 'text' : 'none');
        $watermarkText = $settings['report_watermark_text'] ?? $orgName;
        $watermarkOpacity = (float)($settings['report_watermark_opacity'] ?? 0.08);
        $watermarkRotation = (int)($settings['report_watermark_rotation'] ?? -30);
        $watermarkFontSize = $settings['report_watermark_font_size'] ?? '60px';
        $watermarkColor = $settings['report_watermark_color'] ?? '#000000';

        // Signatures & Stamp
        $showSignatures = (bool)($settings['report_show_signatures'] ?? 1);
        $showStamp = (bool)($settings['report_show_stamp'] ?? 1);
        $sigLabel1 = $settings['report_signature_label_1'] ?? 'Principal Signature';
        $sigLabel2 = $settings['report_signature_label_2'] ?? 'Authorized Signatory';
        $sigLabel3 = $settings['report_signature_label_3'] ?? 'Accountant / Cashier';
        $principalName = $settings['principal_name'] ?? '';

        // Footer & Notes
        $footerText = $settings['report_footer_text'] ?? 'This is a computer-generated document. Official verified record.';
        $footerCustomNote = $settings['report_footer_custom_text'] ?? '';
        $showFooterDate = (bool)($settings['report_footer_show_date'] ?? 1);
        $showFooterUser = (bool)($settings['report_footer_show_by'] ?? 1);
        $showFooterPageNum = (bool)($settings['report_footer_show_page_number'] ?? 1);
        $copyright = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $orgName);

        // Page Paper & Margins
        $paperSize = $settings['report_paper_size'] ?? 'A4'; // A4, Letter, Legal, A5
        $orientation = $settings['report_orientation'] ?? 'portrait'; // portrait, landscape
        $marginTop = $settings['report_margin_top'] ?? '10mm';
        $marginBottom = $settings['report_margin_bottom'] ?? '10mm';
        $marginLeft = $settings['report_margin_left'] ?? '10mm';
        $marginRight = $settings['report_margin_right'] ?? '10mm';

        // Resolve Images as Base64 Data URIs (Safe for Headless Chromium)
        $logoBase64 = $this->getSettingImageAsBase64('logo') 
            ?: $this->getSettingImageAsBase64('light_logo') 
            ?: $this->getSettingImageAsBase64('dark_logo');

        $watermarkImageBase64 = $this->getSettingImageAsBase64('report_watermark_image');
        $principalSigBase64 = $this->getSettingImageAsBase64('report_principal_signature');
        $authorizedSigBase64 = $this->getSettingImageAsBase64('report_authorized_signature');
        $stampBase64 = $this->getSettingImageAsBase64('report_school_stamp');

        $config = [
            'org_name'                  => $orgName,
            'tagline'                   => $tagline,
            'school_code'               => $schoolCode,
            'affiliation_number'        => $affiliationNo,
            'registration_number'       => $registrationNo,
            'udise_code'                => $udiseCode,
            'address'                   => $address,
            'city'                      => $city,
            'state'                     => $state,
            'zip_code'                  => $zipCode,
            'full_address'              => implode(', ', array_filter([$address, $city, $state, $zipCode])),
            'phone'                     => $phone,
            'email'                     => $email,
            'website'                   => $website,
            'primary_color'             => $primaryColor,
            'secondary_color'           => $secondaryColor,
            'table_header_bg'           => $tableHeaderBg,
            'table_header_color'        => $tableHeaderColor,
            'font_family'               => $fontFamily,
            'font_size'                 => $fontSize,
            'header_style'              => $headerStyle,
            'header_align'              => $headerAlign,
            'show_logo'                 => $showLogo,
            'logo_position'             => $logoPosition,
            'logo_height'               => $logoHeight,
            'logo_base64'               => $logoBase64,
            'header_show_address'       => $showHeaderAddress,
            'header_show_contact'       => $showHeaderContact,
            'header_show_affiliation'   => $showHeaderAffiliation,
            'header_bottom_border'      => $showHeaderBorder,
            'header_custom_text'        => $headerCustomText,
            'watermark_type'            => $watermarkType,
            'watermark_text'            => $watermarkText,
            'watermark_image_base64'    => $watermarkImageBase64,
            'watermark_opacity'         => $watermarkOpacity,
            'watermark_rotation'        => $watermarkRotation,
            'watermark_font_size'       => $watermarkFontSize,
            'watermark_color'           => $watermarkColor,
            'show_signatures'           => $showSignatures,
            'show_stamp'                => $showStamp,
            'signature_label_1'         => $sigLabel1,
            'signature_label_2'         => $sigLabel2,
            'signature_label_3'         => $sigLabel3,
            'principal_name'            => $principalName,
            'principal_signature_base64'=> $principalSigBase64,
            'authorized_signature_base64'=> $authorizedSigBase64,
            'stamp_base64'              => $stampBase64,
            'footer_text'               => $footerText,
            'footer_custom_note'        => $footerCustomNote,
            'show_footer_date'          => $showFooterDate,
            'show_footer_user'          => $showFooterUser,
            'show_footer_page_number'   => $showFooterPageNum,
            'copyright'                 => $copyright,
            'paper_size'                => $paperSize,
            'orientation'               => $orientation,
            'margin_top'                => $marginTop,
            'margin_bottom'             => $marginBottom,
            'margin_left'               => $marginLeft,
            'margin_right'              => $marginRight,
        ];

        return array_merge($config, $overrides);
    }

    /**
     * Render the Blade view into an HTML string with all report configurations injected.
     *
     * @param string $view
     * @param array $data
     * @param array $options
     * @return string
     */
    public function renderHtml(string $view, array $data = [], array $options = []): string
    {
        $reportConfig = $this->getReportConfig($options);

        $mergedData = array_merge([
            'reportConfig' => $reportConfig,
            'isPdfRender'  => $options['isPdfRender'] ?? false,
        ], $data);

        return View::make($view, $mergedData)->render();
    }

    /**
     * Generate binary PDF string using BrowserShot.
     *
     * @param string $view
     * @param array $data
     * @param array $options
     * @return string
     */
    public function generatePdf(string $view, array $data = [], array $options = []): string
    {
        $options['isPdfRender'] = true;
        $html = $this->renderHtml($view, $data, $options);
        $reportConfig = $this->getReportConfig($options);

        $browsershot = $this->createBrowsershotInstance($html, $reportConfig, $options);

        return $browsershot->pdf();
    }

    /**
     * Return a downloadable PDF response.
     *
     * @param string $view
     * @param array $data
     * @param string $filename
     * @param array $options
     * @return HttpResponse
     */
    public function downloadPdf(string $view, array $data = [], string $filename = 'report.pdf', array $options = []): HttpResponse
    {
        $pdf = $this->generatePdf($view, $data, $options);

        return Response::make($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Stream the PDF directly to the browser (inline view).
     *
     * @param string $view
     * @param array $data
     * @param string $filename
     * @param array $options
     * @return HttpResponse
     */
    public function streamPdf(string $view, array $data = [], string $filename = 'report.pdf', array $options = []): HttpResponse
    {
        $pdf = $this->generatePdf($view, $data, $options);

        return Response::make($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Return standard web preview view.
     *
     * @param string $view
     * @param array $data
     * @param array $options
     * @return \Illuminate\Contracts\View\View
     */
    public function preview(string $view, array $data = [], array $options = [])
    {
        $options['isPdfRender'] = false;
        $reportConfig = $this->getReportConfig($options);

        $mergedData = array_merge([
            'reportConfig' => $reportConfig,
            'isPdfRender'  => false,
        ], $data);

        return View::make($view, $mergedData);
    }

    /**
     * Create and configure the BrowserShot instance.
     *
     * @param string $html
     * @param array $reportConfig
     * @param array $options
     * @return Browsershot
     */
    protected function createBrowsershotInstance(string $html, array $reportConfig, array $options = []): Browsershot
    {
        $nodeBinary = config('browsershot.node_binary', '/usr/bin/node');
        $npmBinary  = config('browsershot.npm_binary', '/usr/bin/npm');
        $chromePath = config('browsershot.chrome_path', '/usr/bin/chromium');
        $timeout    = config('browsershot.timeout', 120);

        $browsershot = Browsershot::html($html)
            ->setNodeBinary($nodeBinary)
            ->setNpmBinary($npmBinary)
            ->setChromePath($chromePath)
            ->noSandbox()
            ->setOption('args', [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-extensions',
            ])
            ->timeout($timeout)
            ->showBackground()
            ->waitUntilNetworkIdle();

        // Paper Format
        $format = $options['paper_size'] ?? $reportConfig['paper_size'] ?? 'A4';
        $browsershot->format($format);

        // Orientation
        $orientation = $options['orientation'] ?? $reportConfig['orientation'] ?? 'portrait';
        if (strtolower($orientation) === 'landscape') {
            $browsershot->landscape();
        }

        // Margins (Top, Right, Bottom, Left)
        $topMargin    = $this->parseMarginMm($options['margin_top'] ?? $reportConfig['margin_top'] ?? '10mm');
        $rightMargin  = $this->parseMarginMm($options['margin_right'] ?? $reportConfig['margin_right'] ?? '10mm');
        $bottomMargin = $this->parseMarginMm($options['margin_bottom'] ?? $reportConfig['margin_bottom'] ?? '10mm');
        $leftMargin   = $this->parseMarginMm($options['margin_left'] ?? $reportConfig['margin_left'] ?? '10mm');

        $browsershot->margins($topMargin, $rightMargin, $bottomMargin, $leftMargin);

        return $browsershot;
    }

    /**
     * Convert setting stored file or public image into Base64 Data URI.
     *
     * @param string $settingKey
     * @return string|null
     */
    public function getSettingImageAsBase64(string $settingKey): ?string
    {
        $value = $this->settingService->get($settingKey);
        if (empty($value)) {
            return null;
        }

        // Check if file exists in public storage disk
        if (Storage::disk('public')->exists($value)) {
            $fullPath = Storage::disk('public')->path($value);
            return $this->fileToBase64($fullPath);
        }

        // Check public folder
        $publicFilePath = public_path($value);
        if (file_exists($publicFilePath) && is_file($publicFilePath)) {
            return $this->fileToBase64($publicFilePath);
        }

        // Check public/storage folder
        $publicStoragePath = public_path('storage/' . $value);
        if (file_exists($publicStoragePath) && is_file($publicStoragePath)) {
            return $this->fileToBase64($publicStoragePath);
        }

        return null;
    }

    /**
     * Convert absolute file path to base64 Data URI.
     *
     * @param string $filePath
     * @return string|null
     */
    public function fileToBase64(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_file($filePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
        ];

        $mime = $mimeMap[$extension] ?? mime_content_type($filePath) ?: 'image/png';
        $data = file_get_contents($filePath);

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Parse margin string like "10mm" or "15" to float in mm.
     *
     * @param string|int|float $margin
     * @return float
     */
    protected function parseMarginMm($margin): float
    {
        if (is_numeric($margin)) {
            return (float)$margin;
        }

        $clean = preg_replace('/[^0-9.]/', '', (string)$margin);
        return is_numeric($clean) ? (float)$clean : 10.0;
    }
}
