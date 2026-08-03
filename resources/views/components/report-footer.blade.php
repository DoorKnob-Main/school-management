@props([
    'issuedBy' => null,
    'showSignature' => true
])

@php
    $footerText = setting('report_footer_text', 'This is a computer-generated document. No signature required.');
    $copyright = setting('footer_copyright', '© ' . date('Y') . ' ' . setting('software_name', 'DoorKnob'));
    $showPoweredBy = setting('show_powered_by', '1');
    $poweredBy = setting('powered_by_text', 'Powered by DoorKnob ERP');
@endphp

<div class="report-footer pt-4 mt-4 border-top" style="font-size: 0.85rem;">
    @if($showSignature)
        <div class="row mb-4 align-items-end">
            <div class="col-6">
                @if($issuedBy)
                    <p class="small text-muted mb-0">Issued by: {{ $issuedBy }}</p>
                @endif
                <p class="small text-muted mb-0">Generated on: {{ date('Y-m-d H:i:s') }}</p>
            </div>
            <div class="col-6 text-end">
                <div style="border-bottom: 1px dashed #000; width: 180px; display: inline-block; margin-bottom: 5px;"></div>
                <p class="small fw-bold mb-0">Authorized Signatory</p>
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between text-muted small">
        <div>{{ $footerText }}</div>
        <div>
            <span>{{ $copyright }}</span>
            @if($showPoweredBy && $poweredBy)
                <span class="ms-2">| {{ $poweredBy }}</span>
            @endif
        </div>
    </div>
</div>
