@extends('admin.layouts.app')
@section('title', 'Edit Language')
@section('page-title', 'Edit Language')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.languages.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Languages</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">{{ $language->name }}</span>
</div>

<div style="max-width:380px;">
    <div class="jobstation-card" style="padding:24px;">
        <form method="POST" action="{{ route('admin.languages.update', $language->id) }}">
            @csrf @method('PUT')

            @if(session('success'))
            <div style="padding:10px 14px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:8px;font-size:13px;color:#22C55E;margin-bottom:16px;">{{ session('success') }}</div>
            @endif

            <div style="display:flex;flex-direction:column;gap:16px;">
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Language Name <span style="color:#EF4444;">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $language->name) }}" required>
                </div>
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Code</label>
                    <input type="text" value="{{ $language->code }}" disabled
                           style="background:var(--surface-2);color:var(--fg-3);cursor:not-allowed;font-family:ui-monospace,monospace;">
                    <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Code cannot be changed</div>
                </div>
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Flag Emoji</label>
                    <input type="text" name="icon" value="{{ old('icon', $language->icon) }}" style="font-size:20px;">
                </div>
                <label style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--fg-2);cursor:pointer;">
                    <input type="checkbox" name="text_align" value="1" style="width:15px;height:15px;accent-color:var(--accent);"
                           {{ old('text_align', $language->text_align) ? 'checked' : '' }}>
                    RTL (Right to Left)
                </label>
                <div style="display:flex;gap:10px;padding-top:4px;">
                    <button type="submit" class="btn btn-primary" style="padding:9px 22px;">Save</button>
                    <a href="{{ route('admin.languages.index') }}" class="btn" style="padding:9px 18px;">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
