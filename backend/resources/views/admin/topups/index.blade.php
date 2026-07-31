@extends('admin.layouts.app')
@section('title', 'Coin Top-ups')
@section('page-title', 'Coin Top-ups')

@section('content')

{{-- 4 Stat Tiles --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total top-ups</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;">{{ number_format($stats['total']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">all time</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Pending review</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#F59E0B;">{{ number_format($stats['pending']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">awaiting approval</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Successful</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#22C55E;">{{ number_format($stats['successful']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">completed</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Initiated</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:var(--fg-2);">{{ number_format($stats['initiated']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">in progress</div>
    </div>

</div>

{{-- Table Card --}}
<div class="jobstation-card" style="padding:0;overflow:hidden;">

    {{-- Toolbar --}}
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="{{ route('admin.topups.index') }}" style="display:flex;gap:10px;align-items:center;flex:1;flex-wrap:wrap;">

            <div style="position:relative;flex:1;min-width:200px;max-width:340px;">
                <i data-lucide="search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input name="search" value="{{ request('search') }}" placeholder="Search by reference, username…"
                       style="padding-left:34px;width:100%;font-size:12.5px;">
            </div>

            <select name="status" style="width:auto;padding:7px 10px;font-size:12px;">
                <option value="">All statuses</option>
                <option value="0" {{ request('status')=='0' ? 'selected' : '' }}>Initiated</option>
                <option value="2" {{ request('status')=='2' ? 'selected' : '' }}>Pending review</option>
                <option value="1" {{ request('status')=='1' ? 'selected' : '' }}>Successful</option>
                <option value="3" {{ request('status')=='3' ? 'selected' : '' }}>Failed</option>
            </select>

            <button type="submit" class="btn btn-sm">Filter</button>
            @if(request()->hasAny(['search','status']))
            <a href="{{ route('admin.topups.index') }}" class="btn btn-sm btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table Header --}}
    <div style="display:grid;grid-template-columns:120px 1.5fr 120px 100px 100px 90px 90px;gap:14px;padding:11px 18px;font-size:10.5px;color:var(--fg-3);text-transform:uppercase;letter-spacing:0.06em;font-weight:500;background:var(--bg-2);border-bottom:1px solid var(--border);">
        <span>Reference</span>
        <span>User</span>
        <span>Channel</span>
        <span>Amount</span>
        <span>Coins</span>
        <span>Date</span>
        <span>Status</span>
    </div>

    {{-- Rows --}}
    @forelse($topups as $topup)
    @php
        $statusMap   = [0=>'status-draft', 1=>'status-approved', 2=>'status-pending', 3=>'status-rejected'];
        $statusLabel = [0=>'Initiated', 1=>'Successful', 2=>'Pending', 3=>'Failed'];
        $ini  = strtoupper(substr($topup->user?->username ?? 'U', 0, 1));
        $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
        $clr  = $clrs[ord($ini) % count($clrs)];
    @endphp
    <div style="display:grid;grid-template-columns:120px 1.5fr 120px 100px 100px 90px 90px;gap:14px;padding:13px 18px;align-items:center;border-bottom:1px solid var(--border);font-size:12.5px;">

        <a href="{{ route('admin.topups.show', $topup->id) }}"
           class="mono" style="font-size:11px;color:var(--accent);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ Str::limit($topup->reference ?? ('TX-'.str_pad($topup->id,6,'0',STR_PAD_LEFT)), 16) }}
        </a>

        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
            <div style="width:22px;height:22px;border-radius:50%;background:{{ $clr }};display:flex;align-items:center;justify-content:center;color:white;font-size:10px;font-weight:600;flex-shrink:0;">{{ $ini }}</div>
            <div style="min-width:0;">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $topup->user?->username ?? '—' }}</div>
                <div style="font-size:10.5px;color:var(--fg-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $topup->user?->email ?? '' }}</div>
            </div>
        </div>

        <span style="font-size:11.5px;color:var(--fg-2);">{{ $topup->channel?->name ?? $topup->channel_code ?? '—' }}</span>

        <span class="mono">{{ $topup->pay_currency ?? '' }} {{ number_format($topup->amount, 2) }}</span>

        <span class="mono" style="color:var(--coin);">{{ coinSymbol() }}{{ number_format($topup->coins_credited ?? 0) }}</span>

        <span style="font-size:11px;color:var(--fg-3);">{{ $topup->created_at->format('M j, Y') }}</span>

        <span class="status-pill {{ $statusMap[$topup->status] ?? 'status-draft' }}" style="font-size:10.5px;width:fit-content;">
            {{ $statusLabel[$topup->status] ?? '—' }}
        </span>

    </div>
    @empty
    <div style="padding:48px;text-align:center;color:var(--fg-3);">
        <i data-lucide="arrow-down-circle" style="width:32px;height:32px;margin:0 auto 12px;display:block;opacity:0.3;"></i>
        <div style="font-size:13px;">No top-ups found.</div>
    </div>
    @endforelse

</div>

{{-- Pagination --}}
@if($topups->hasPages())
<div style="display:flex;justify-content:flex-end;margin-top:16px;font-size:12.5px;">
    {{ $topups->withQueryString()->links() }}
</div>
@endif

@endsection
