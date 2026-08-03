<div class="row border-top-e6 mt-4 ps-4 pt-4">
    <div class="d-flex justify-content-between align-items-center text-muted small">
        <div>
            <span>{{ setting('footer_copyright', '© ' . date('Y') . ' ' . setting('software_name', 'DoorKnob')) }}</span>
            @if(setting('show_powered_by', '1') && setting('powered_by_text'))
                <span class="ms-2">| {{ setting('powered_by_text') }}</span>
            @endif
        </div>
        @if(setting('developer_name'))
            <div>
                Developed by <a href="{{ setting('developer_website', '#') }}" target="_blank" class="text-decoration-none fw-bold text-primary">{{ setting('developer_name') }}</a>
            </div>
        @endif
    </div>
</div>