@extends('admin.layouts.app')
@section('title', 'Custom CSS')
@section('page-title', 'Custom CSS')

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;max-width:1100px;align-items:start;">
<div class="jobstation-card" style="padding:10px;">@include('admin.settings._nav', ['active' => 'css'])</div>
<div>
<form method="POST" action="{{ route('admin.settings.css.update') }}">
@csrf
<div class="jobstation-card" style="padding:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div style="font-weight:600;font-size:15px;color:var(--fg);">Custom CSS</div>
        <span style="font-size:12px;color:var(--fg-4);">Injected into every public page</span>
    </div>
    <textarea name="custom_css" rows="22"
              placeholder="/* Add your custom styles here */&#10;body { }"
              style="font-family:ui-monospace,monospace;font-size:12.5px;resize:vertical;tab-size:2;line-height:1.6;">{{ old('custom_css', $settings->custom_css) }}</textarea>
    <div style="margin-top:16px;">
        <button type="submit" class="btn-primary" style="padding:9px 20px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save CSS
        </button>
    </div>
</div>
</form>
</div>
</div>

@endsection
