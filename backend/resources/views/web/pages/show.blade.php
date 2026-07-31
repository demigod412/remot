@extends('web.layouts.app')

@section('title', $page->name . ' — ' . (gs()->site_name ?? config('app.name')))
@section('meta_description', $page->name . ' — ' . (gs()->site_name ?? config('app.name')))

@section('content')

{{-- ── Page header ──────────────────────────────────────────────── --}}
<div style="border-bottom:1px solid var(--border);background:var(--bg-card);padding:48px 40px 32px;">
    <div style="max-width:760px;margin:0 auto;">
        {{-- Breadcrumb --}}
        <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--muted);margin-bottom:18px;">
            <a href="{{ route('home') }}" style="color:var(--muted);text-decoration:none;transition:color .14s;"
               onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">{{ gs()->site_name ?? config('app.name') }}</a>
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.5 2l3 4-3 4"/></svg>
            <span style="color:var(--text);font-weight:500;">{{ $page->name }}</span>
        </div>
        <h1 style="font-size:clamp(26px,4vw,38px);font-weight:800;color:var(--text);letter-spacing:-0.5px;line-height:1.2;margin:0 0 10px;">{{ $page->name }}</h1>
        <div style="display:flex;align-items:center;gap:16px;font-size:13px;color:var(--muted);">
            <span>{{ $page->updated_at?->format('M j, Y') }}</span>
            <span style="width:3px;height:3px;border-radius:50%;background:var(--muted);display:inline-block;"></span>
            <span>{{ gs()->site_name ?? config('app.name') }}</span>
        </div>
    </div>
</div>

{{-- ── Body ─────────────────────────────────────────────────────── --}}
<div style="max-width:760px;margin:0 auto;padding:40px 40px 80px;">

    @forelse($page->secs ?? [] as $sec)
        @if(!empty($sec['content']))
        <div class="policy-content">
            {!! $sec['content'] !!}
        </div>
        @endif
        @if(!empty($sec['image']))
        <div style="border-radius:12px;overflow:hidden;margin:28px 0;">
            <img src="{{ fileUrl($sec['image']) }}" alt="" style="width:100%;display:block;">
        </div>
        @endif
    @empty
        <div style="text-align:center;padding:80px 0;color:var(--muted);">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:0.35;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <div style="font-size:15px;font-weight:500;color:var(--text);margin-bottom:6px;">{{ __('No content available') }}</div>
            <p style="font-size:13.5px;">{{ __('This page has no content yet.') }}</p>
        </div>
    @endforelse

</div>

<style>
.policy-content {
    font-size: 15px;
    line-height: 1.85;
    color: var(--text);
}
.policy-content h1,
.policy-content h2,
.policy-content h3 {
    font-weight: 700;
    color: var(--text);
    line-height: 1.3;
    margin: 36px 0 12px;
}
.policy-content h1 { font-size: 28px; letter-spacing: -0.3px; }
.policy-content h2 { font-size: 20px; padding-top: 8px; border-top: 1px solid var(--border); margin-top: 40px; }
.policy-content h3 { font-size: 16px; color: var(--text); font-weight: 600; }
.policy-content p  { margin-bottom: 16px; color: var(--muted); }
.policy-content strong { color: var(--text); font-weight: 600; }
.policy-content ul,
.policy-content ol  { padding-left: 22px; margin-bottom: 16px; color: var(--muted); }
.policy-content li  { margin-bottom: 8px; }
.policy-content a   { color: var(--accent); text-decoration: none; font-weight: 500; }
.policy-content a:hover { text-decoration: underline; }
.policy-content blockquote {
    border-left: 3px solid var(--accent);
    padding: 14px 20px;
    background: var(--accent-bg);
    border-radius: 0 8px 8px 0;
    margin: 24px 0;
    color: var(--muted);
}
.policy-content code {
    background: var(--border);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 13px;
    font-family: ui-monospace, monospace;
}
.policy-content table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 14px;
}
.policy-content th,
.policy-content td {
    padding: 10px 14px;
    border: 1px solid var(--border);
    text-align: left;
}
.policy-content th {
    background: var(--bg);
    font-weight: 600;
    color: var(--text);
}
@media (max-width: 640px) {
    .policy-content { font-size: 14px; }
    .policy-content h2 { font-size: 17px; }
}
</style>

@endsection
