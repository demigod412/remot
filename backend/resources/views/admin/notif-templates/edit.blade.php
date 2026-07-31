@extends('admin.layouts.app')
@section('title', 'Edit Template')
@section('page-title', 'Edit Notification Template')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.notif-templates.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Templates</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">{{ $template->name }}</span>
</div>

<div style="max-width:780px;">
<form method="POST" action="{{ route('admin.notif-templates.update', $template->id) }}">
@csrf @method('PUT')
<div style="display:flex;flex-direction:column;gap:16px;">

    <div class="jobstation-card" style="padding:24px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border);">
            <div style="padding:8px;border-radius:10px;background:rgba(47,84,235,0.1);">
                <i data-lucide="mail" style="width:18px;height:18px;color:var(--accent);display:block;"></i>
            </div>
            <div>
                <div style="font-size:14px;font-weight:600;color:var(--fg);">{{ $template->name }}</div>
                <div style="font-size:11.5px;color:var(--fg-3);font-family:ui-monospace,monospace;">{{ $template->act }}</div>
            </div>
        </div>

        @if($template->shortcodes)
        <div style="margin-bottom:16px;padding:12px 14px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);">
            <div style="font-size:11px;color:var(--fg-3);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Available Shortcodes</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                @foreach(explode(',', $template->shortcodes) as $code)
                <code style="font-size:11.5px;padding:2px 8px;border-radius:5px;background:rgba(47,84,235,0.1);color:var(--accent);font-family:ui-monospace,monospace;">{{ trim($code) }}</code>
                @endforeach
            </div>
        </div>
        @endif

        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Email Subject <span style="color:#EF4444;">*</span></label>
                <input type="text" name="subj" value="{{ old('subj', $template->subj) }}" required>
            </div>

            {{-- Email Body --}}
            <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--surface-2);border-bottom:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:var(--fg);">
                        <i data-lucide="mail" style="width:14px;height:14px;color:var(--accent);"></i> Email Body
                    </div>
                    <label style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--fg-3);cursor:pointer;">
                        <input type="checkbox" name="email_status" value="1" style="width:14px;height:14px;accent-color:var(--accent);"
                               {{ old('email_status', $template->email_status) ? 'checked' : '' }}>
                        Enable
                    </label>
                </div>
                <div style="padding:14px;">
                    <textarea name="email_body" rows="8"
                              style="font-family:ui-monospace,monospace;font-size:12px;resize:vertical;width:100%;">{{ old('email_body', $template->email_body) }}</textarea>
                </div>
            </div>

            {{-- SMS Body --}}
            <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--surface-2);border-bottom:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:var(--fg);">
                        <i data-lucide="message-square" style="width:14px;height:14px;color:#22C55E;"></i> SMS Body
                    </div>
                    <label style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--fg-3);cursor:pointer;">
                        <input type="checkbox" name="sms_status" value="1" style="width:14px;height:14px;accent-color:var(--accent);"
                               {{ old('sms_status', $template->sms_status) ? 'checked' : '' }}>
                        Enable
                    </label>
                </div>
                <div style="padding:14px;">
                    <textarea name="sms_body" rows="4" maxlength="500"
                              style="resize:vertical;width:100%;font-size:13px;">{{ old('sms_body', $template->sms_body) }}</textarea>
                    <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Max 500 characters</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save Template
        </button>
        <a href="{{ route('admin.notif-templates.index') }}" class="btn" style="padding:9px 18px;">Cancel</a>
    </div>

</div>
</form>
</div>

@endsection
