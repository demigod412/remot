@extends('admin.layouts.app')
@section('title', 'License')
@section('page-title', 'License')

@section('content')

@php
    $verified = $license['verified'] ?? false;
    $mode     = $license['mode'] ?? 'offline';
    // Colour: green when live-verified, amber when offline/degraded but accepted, red when not verified.
    $tone = ! $verified ? '#ef4444' : ($mode === 'live' ? '#22c55e' : '#f59e0b');
    $modeLabel = [
        'live'     => 'Live — verified with Envato',
        'offline'  => 'Offline — validated by code format',
        'degraded' => 'Accepted — Envato could not be reached',
    ][$mode] ?? ucfirst($mode);
@endphp

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;max-width:1100px;align-items:start;">
<div class="jobstation-card" style="padding:10px;">@include('admin.settings._nav', ['active' => 'license'])</div>
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- Status banner --}}
    <div class="jobstation-card" style="padding:20px 24px;border-left:3px solid {{ $tone }};">
        <div style="display:flex;align-items:center;gap:12px;">
            <i data-lucide="{{ $verified ? 'shield-check' : 'shield-alert' }}"
               style="width:22px;height:22px;color:{{ $tone }};flex-shrink:0;"></i>
            <div style="flex:1;">
                <div style="font-weight:600;font-size:15px;color:var(--fg);">
                    {{ $verified ? 'License active' : 'License not verified' }}
                </div>
                <div style="font-size:12.5px;color:var(--fg-2);margin-top:3px;">{{ $modeLabel }}</div>
            </div>
            <span style="font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;
                         padding:5px 11px;border-radius:999px;color:{{ $tone }};
                         background:color-mix(in srgb,{{ $tone }} 12%,var(--surface-2));
                         border:1px solid color-mix(in srgb,{{ $tone }} 32%,var(--border));">
                {{ $verified ? 'Valid' : 'Action needed' }}
            </span>
        </div>
    </div>

    {{-- Details --}}
    <div class="jobstation-card" style="padding:24px;">
        <div style="font-weight:600;font-size:15px;color:var(--fg);margin-bottom:18px;">Purchase details</div>
        @php
            $rows = [
                ['Purchase code',   $license['masked'] ?? '— not recorded —', 'key-round'],
                ['Buyer',           $license['buyer'] ?? '—',                 'user'],
                ['Item',            $license['item'] ?? '—',                  'package'],
                ['License type',    $license['license_type'] ?? '—',          'file-badge'],
                ['Supported until', $license['supported_until'] ?? '—',       'life-buoy'],
                ['Verified at',     $license['verified_at'] ?? '—',           'check'],
                ['Last checked',    $license['checked_at'] ?? '—',            'refresh-cw'],
            ];
        @endphp
        <div style="display:flex;flex-direction:column;">
            @foreach($rows as [$label, $value, $icon])
            <div style="display:flex;align-items:center;gap:12px;padding:11px 0;
                        border-bottom:{{ ! $loop->last ? '1px solid var(--border)' : 'none' }};">
                <i data-lucide="{{ $icon }}" style="width:15px;height:15px;color:var(--fg-3);flex-shrink:0;"></i>
                <div style="width:150px;font-size:12.5px;color:var(--fg-3);">{{ $label }}</div>
                <div style="flex:1;font-size:13px;color:var(--fg);font-family:monospace;word-break:break-all;">{{ $value }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Re-verify --}}
    <div class="jobstation-card" style="padding:24px;">
        <div style="font-weight:600;font-size:15px;color:var(--fg);margin-bottom:6px;">Verification mode</div>
        @if($liveMode)
            <p style="font-size:12.5px;color:var(--fg-2);line-height:1.7;margin-bottom:18px;">
                Live verification is <strong style="color:var(--fg);">enabled</strong> — an Envato API token is configured,
                so purchase codes are checked directly against Envato. Use the button below to re-check this
                installation's code now.
            </p>
        @else
            <p style="font-size:12.5px;color:var(--fg-2);line-height:1.7;margin-bottom:18px;">
                Live verification is <strong style="color:var(--fg);">off</strong>. Codes are validated by format only.
                To enable live checks, set <code style="color:var(--accent);">ENVATO_API_TOKEN</code> and
                <code style="color:var(--accent);">ENVATO_ITEM_ID</code> in your <code>.env</code> file, then re-check.
            </p>
        @endif

        <form method="POST" action="{{ route('admin.settings.license.reverify') }}">
            @csrf
            <button type="submit" class="btn btn-primary" style="padding:9px 22px;display:inline-flex;align-items:center;gap:8px;"
                    {{ ($license['purchase_code'] ?? null) ? '' : 'disabled' }}>
                <i data-lucide="refresh-cw" style="width:15px;height:15px;"></i>
                Re-verify license now
            </button>
            @if(! ($license['purchase_code'] ?? null))
                <span style="font-size:12px;color:var(--fg-3);margin-left:10px;">No purchase code recorded for this install.</span>
            @endif
        </form>
    </div>

</div>
</div>

@endsection
