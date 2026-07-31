@extends('admin.layouts.app')
@section('title', 'Firebase Settings')
@section('page-title', 'Firebase Settings')

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;max-width:1100px;align-items:start;">
<div class="jobstation-card" style="padding:10px;">@include('admin.settings._nav', ['active' => 'firebase'])</div>
<div>
<form method="POST" action="{{ route('admin.settings.firebase.update') }}" enctype="multipart/form-data">
@csrf
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- What is Firebase --}}
    <div class="jobstation-card" style="padding:20px 24px;border-left:3px solid var(--accent);">
        <div style="font-size:13px;color:var(--fg-2);line-height:1.7;">
            Firebase powers two features: <strong style="color:var(--fg);">Push Notifications</strong> (FCM) and
            <strong style="color:var(--fg);">Social Login</strong> (Google / Apple via Firebase Auth).
            Get these values from <a href="https://console.firebase.google.com/" target="_blank" style="color:var(--accent);">console.firebase.google.com</a>.
        </div>
    </div>

    {{-- Project & API Key --}}
    <div class="jobstation-card" style="padding:24px;">
        <div style="font-weight:600;font-size:15px;color:var(--fg);margin-bottom:20px;">Project Credentials</div>

        <div style="display:flex;flex-direction:column;gap:18px;">

            {{-- Project ID --}}
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">
                    Firebase Project ID
                </label>
                <input type="text" name="project_id"
                       value="{{ old('project_id', $fbConfig['project_id'] ?? '') }}"
                       placeholder="e.g. my-app-12345"
                       style="width:100%;font-size:13px;">
                <div style="font-size:11.5px;color:var(--fg-3);margin-top:5px;">
                    Found in Firebase console → Project Settings → General → Project ID.
                    Also auto-filled when you upload the service account JSON below.
                </div>
                @error('project_id')
                    <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Web API Key --}}
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">
                    Web API Key
                    <span style="font-size:11.5px;color:var(--fg-3);font-weight:400;margin-left:6px;">(for Social Login)</span>
                </label>
                <input type="password" name="web_api_key"
                       value="{{ old('web_api_key', $fbConfig['web_api_key'] ?? '') }}"
                       placeholder="AIzaSy…"
                       style="width:100%;font-size:13px;"
                       autocomplete="new-password">
                <div style="font-size:11.5px;color:var(--fg-3);margin-top:5px;">
                    Firebase console → Project Settings → General → Web API Key.
                    Used to verify Firebase ID tokens from Google / Apple sign-in on the mobile app.
                </div>
                @error('web_api_key')
                    <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>

    {{-- Service Account JSON --}}
    <div class="jobstation-card" style="padding:24px;">
        <div style="font-weight:600;font-size:15px;color:var(--fg);margin-bottom:6px;">
            Service Account JSON
            <span style="font-size:11.5px;color:var(--fg-3);font-weight:400;margin-left:6px;">(for Push Notifications)</span>
        </div>
        <p style="font-size:12.5px;color:var(--fg-3);margin-bottom:20px;line-height:1.6;">
            Firebase console → Project Settings → Service accounts → Generate new private key.
            Download the JSON file and upload it here. It is stored securely outside the web root.
        </p>

        {{-- Current status --}}
        @if($credExists)
        <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:9px;
                    background:color-mix(in srgb,#22c55e 10%,var(--surface-2));
                    border:1px solid color-mix(in srgb,#22c55e 30%,var(--border));
                    margin-bottom:18px;">
            <i data-lucide="check-circle-2" style="width:16px;height:16px;color:#22c55e;flex-shrink:0;"></i>
            <div>
                <div style="font-size:13px;color:var(--fg);font-weight:500;">Service account file uploaded</div>
                <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;font-family:monospace;word-break:break-all;">
                    {{ $credPath }}
                </div>
            </div>
        </div>
        @else
        <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:9px;
                    background:color-mix(in srgb,#f59e0b 10%,var(--surface-2));
                    border:1px solid color-mix(in srgb,#f59e0b 30%,var(--border));
                    margin-bottom:18px;">
            <i data-lucide="alert-triangle" style="width:16px;height:16px;color:#f59e0b;flex-shrink:0;"></i>
            <div style="font-size:13px;color:var(--fg);">
                No service account file uploaded yet. Push notifications will not work until you upload one.
            </div>
        </div>
        @endif

        {{-- Upload field --}}
        <div>
            <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:8px;">
                {{ $credExists ? 'Replace service account file' : 'Upload service account file' }}
            </label>

            <label id="json-drop-zone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                        gap:8px;padding:32px 20px;border-radius:10px;cursor:pointer;
                        border:2px dashed var(--border);background:var(--surface-2);
                        transition:border-color .15s,background .15s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.background='color-mix(in srgb,var(--accent) 5%,var(--surface-2))';"
                   onmouseout="if(!document.getElementById('json-file').files.length){this.style.borderColor='var(--border)';this.style.background='var(--surface-2)';}">
                <input type="file" id="json-file" name="credentials_json" accept=".json,application/json"
                       style="display:none;" onchange="onJsonPicked(this)">
                <i data-lucide="upload-cloud" id="upload-icon" style="width:28px;height:28px;color:var(--fg-3);"></i>
                <div id="upload-label" style="font-size:13px;color:var(--fg-2);text-align:center;">
                    Click to choose a <code>.json</code> file, or drag &amp; drop here
                </div>
                <div id="upload-hint" style="font-size:11.5px;color:var(--fg-3);">Max 512 KB</div>
            </label>

            @error('credentials_json')
                <div style="font-size:12px;color:#EF4444;margin-top:6px;">{{ $message }}</div>
            @enderror
        </div>

        {{-- What fields we expect --}}
        <div style="margin-top:16px;padding:12px 14px;border-radius:8px;background:var(--surface-2);
                    border:1px solid var(--border);font-size:12px;color:var(--fg-3);line-height:1.7;">
            <strong style="color:var(--fg-2);">The file must contain:</strong>
            <code style="color:var(--accent);">"type": "service_account"</code>,
            <code style="color:var(--accent);">"project_id"</code>,
            <code style="color:var(--accent);">"private_key"</code>, and
            <code style="color:var(--accent);">"client_email"</code>.
            Any valid file downloaded from Firebase will have these automatically.
        </div>
    </div>

    {{-- Save --}}
    <div class="jobstation-card" style="padding:16px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;">
        <div style="font-size:12.5px;color:var(--fg-3);">
            Changes take effect immediately. No server restart required.
        </div>
        <button type="submit" class="btn btn-primary" style="padding:9px 28px;white-space:nowrap;">
            Save Firebase Settings
        </button>
    </div>

</div>
</form>
</div>
</div>

@push('scripts')
<script>
function onJsonPicked(input) {
    const zone  = document.getElementById('json-drop-zone');
    const icon  = document.getElementById('upload-icon');
    const label = document.getElementById('upload-label');
    const hint  = document.getElementById('upload-hint');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        zone.style.borderColor = 'var(--accent)';
        zone.style.background  = 'color-mix(in srgb,var(--accent) 5%,var(--surface-2))';
        label.innerHTML = '<strong style="color:var(--fg);">' + file.name + '</strong>';
        hint.textContent = (file.size / 1024).toFixed(1) + ' KB — ready to upload';
    }
}

// Drag & drop support
const zone = document.getElementById('json-drop-zone');
zone.addEventListener('dragover',  e => { e.preventDefault(); zone.style.borderColor = 'var(--accent)'; });
zone.addEventListener('dragleave', () => { if (!document.getElementById('json-file').files.length) zone.style.borderColor = 'var(--border)'; });
zone.addEventListener('drop', e => {
    e.preventDefault();
    const dt = e.dataTransfer;
    if (dt.files.length) {
        document.getElementById('json-file').files = dt.files;
        onJsonPicked(document.getElementById('json-file'));
    }
});
</script>
@endpush

@endsection
