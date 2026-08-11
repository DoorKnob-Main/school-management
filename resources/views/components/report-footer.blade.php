@props([
    'issuedBy' => null,
    'showSignature' => true,
    'showStamp' => true,
])

@php
    $footerText = setting('report_footer_text', 'This is a computer-generated document. Official verified record.');
    $footerCustomNote = setting('report_footer_custom_text', '');
    $copyright = setting('footer_copyright', '© ' . date('Y') . ' ' . setting('organization_name', setting('software_name', 'DoorKnob')));
    $showPoweredBy = setting('show_powered_by', '1');
    $poweredBy = setting('powered_by_text', 'Powered by DoorKnob ERP');
    $showSignatures = (bool)setting('report_show_signatures', 1);
    $principalName = setting('principal_name', '');
    $principalSig = setting_asset('report_principal_signature');
    $authorizedSig = setting_asset('report_authorized_signature');
    $schoolStamp = setting_asset('report_school_stamp');
    $sigLabel1 = setting('report_signature_label_1', 'Principal Signature');
    $sigLabel2 = setting('report_signature_label_2', 'Authorized Signatory');
@endphp

<div class="report-footer pt-4 mt-4 border-top" style="font-size: 0.85rem;">
    @if($showSignature && $showSignatures)
        <div class="row mb-4 align-items-end">
            {{-- Authorized Signatory / Cashier --}}
            <div class="col-4 text-center">
                <div style="height: 45px;" class="d-flex align-items-end justify-content-center mb-1">
                    @if($authorizedSig)
                        <img src="{{ $authorizedSig }}" alt="Signature" style="max-height: 40px; max-width: 120px;">
                    @endif
                </div>
                <div style="border-bottom: 1px dashed #64748b; width: 140px; margin: 0 auto 4px auto;"></div>
                <p class="small fw-bold mb-0 text-dark">{{ $sigLabel2 }}</p>
                <small class="text-muted" style="font-size: 0.72rem;">{{ $issuedBy ? 'Issued by: ' . $issuedBy : 'Office Administration' }}</small>
            </div>

            {{-- School Seal / Stamp --}}
            <div class="col-4 text-center">
                <div style="height: 50px;" class="d-flex align-items-center justify-content-center">
                    @if($schoolStamp)
                        <img src="{{ $schoolStamp }}" alt="Official Stamp" style="max-height: 48px; max-width: 80px; opacity: 0.85;">
                    @else
                        <div style="border: 2px dashed #cbd5e1; border-radius: 50%; width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center; font-size: 8px; color: #94a3b8; font-weight: bold; text-transform: uppercase;">
                            Seal
                        </div>
                    @endif
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">Official Institutional Seal</small>
            </div>

            {{-- Principal Signature --}}
            <div class="col-4 text-center">
                <div style="height: 45px;" class="d-flex align-items-end justify-content-center mb-1">
                    @if($principalSig)
                        <img src="{{ $principalSig }}" alt="Principal Signature" style="max-height: 40px; max-width: 120px;">
                    @endif
                </div>
                <div style="border-bottom: 1px dashed #64748b; width: 140px; margin: 0 auto 4px auto;"></div>
                <p class="small fw-bold mb-0 text-dark">{{ $sigLabel1 }}</p>
                <small class="text-muted" style="font-size: 0.72rem;">{{ $principalName ?: 'Head of Institution' }}</small>
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between text-muted small" style="font-size: 0.78rem;">
        <div>
            <span>{{ $footerText }}</span>
            @if($footerCustomNote)
                <span class="d-block text-secondary" style="font-size: 0.72rem;">{{ $footerCustomNote }}</span>
            @endif
        </div>
        <div class="text-end">
            <span>{{ $copyright }}</span>
            @if($showPoweredBy && $poweredBy)
                <span class="ms-2">| {{ $poweredBy }}</span>
            @endif
            <small class="d-block text-secondary" style="font-size: 0.7rem;">Generated on {{ date('Y-m-d H:i:s') }}</small>
        </div>
    </div>
</div>
