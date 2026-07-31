@extends('admin.layouts.app')
@section('title', 'Logo & Icon')
@section('page-title', 'Logo & Icon')

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;max-width:1100px;align-items:start;">
<div class="jobstation-card" style="padding:10px;">@include('admin.settings._nav', ['active' => 'logo'])</div>
<div>
<form method="POST" action="{{ route('admin.settings.logo.update') }}" enctype="multipart/form-data">
@csrf
<div class="jobstation-card" style="padding:24px;">
    <div style="display:flex;flex-direction:column;gap:24px;">

        <div>
            <label style="display:block;font-size:13px;color:var(--fg-2);font-weight:500;margin-bottom:10px;">Site Logo</label>
            @if(!empty($settings->logo))
            <div style="margin-bottom:12px;">
                <img src="{{ fileUrl(config('jobstation.upload_paths.logos'), $settings->logo) }}"
                     style="height:48px;object-fit:contain;border-radius:8px;border:1px solid var(--border);background:var(--surface-2);padding:8px;" alt="Logo">
                <div style="font-size:12px;color:var(--fg-4);margin-top:5px;">Current logo — upload to replace</div>
            </div>
            @endif
            <input type="file" name="logo" accept="image/*">
            <div style="font-size:12px;color:var(--fg-4);margin-top:5px;">PNG/SVG recommended · Max 2MB</div>
        </div>

        <div style="border-top:1px solid var(--border);padding-top:20px;">
            <label style="display:block;font-size:13px;color:var(--fg-2);font-weight:500;margin-bottom:10px;">Favicon</label>
            @if(!empty($settings->favicon))
            <div style="margin-bottom:12px;">
                <img src="{{ fileUrl(config('jobstation.upload_paths.logos'), $settings->favicon) }}"
                     style="width:32px;height:32px;object-fit:contain;border-radius:6px;border:1px solid var(--border);background:var(--surface-2);padding:4px;" alt="Favicon">
                <div style="font-size:12px;color:var(--fg-4);margin-top:5px;">Current favicon — upload to replace</div>
            </div>
            @endif
            <input type="file" name="favicon" accept="image/*">
            <div style="font-size:12px;color:var(--fg-4);margin-top:5px;">ICO/PNG · 32×32px recommended · Max 512KB</div>
        </div>

        <div>
            <button type="submit" class="btn-primary" style="padding:9px 20px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
                <i data-lucide="upload" style="width:14px;height:14px;"></i> Upload
            </button>
        </div>
    </div>
</div>
</form>
</div>
</div>

@endsection
