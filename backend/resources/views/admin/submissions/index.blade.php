@extends('admin.layouts.app')
@section('title', 'Submissions')
@section('page-title', 'Moderation Queue')

@section('content')

{{-- Context banner when filtered by work --}}
@if($filterWork)
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(47,84,235,0.08);border:1px solid rgba(47,84,235,0.2);margin-bottom:16px;font-size:13px;">
    <i data-lucide="briefcase" style="width:15px;height:15px;color:var(--accent);flex-shrink:0;"></i>
    <span style="color:var(--fg-2);">Filtered by: <strong style="color:var(--fg);">{{ $filterWork->title }}</strong></span>
    <a href="{{ route('admin.submissions.index') }}" style="margin-left:auto;font-size:11.5px;color:var(--fg-3);text-decoration:none;">Clear ×</a>
</div>
@endif

{{-- 4 Stat Tiles --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;">{{ number_format($stats['total']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">all time</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Pending review</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#F59E0B;">{{ number_format($stats['pending']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">need moderation</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Approved</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#22C55E;">{{ number_format($stats['approved']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">paid out</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Rejected</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#EF4444;">{{ number_format($stats['rejected']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">declined</div>
    </div>

</div>

{{-- Filter Bar + Table --}}
<div class="jobstation-card" style="padding:0;overflow:hidden;">

    {{-- Toolbar --}}
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="{{ route('admin.submissions.index') }}" style="display:flex;gap:10px;align-items:center;flex:1;flex-wrap:wrap;">
            @if($filterWork)
                <input type="hidden" name="work_id" value="{{ $filterWork->id }}">
            @endif

            {{-- Search --}}
            <div style="position:relative;flex:1;min-width:200px;max-width:340px;">
                <i data-lucide="search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input name="search" value="{{ request('search') }}" placeholder="Search by worker, work title…"
                       style="padding-left:34px;width:100%;font-size:12.5px;">
            </div>

            {{-- Status filter --}}
            <select name="status" style="width:auto;padding:7px 10px;font-size:12px;">
                <option value="">All statuses</option>
                <option value="0" {{ request('status')=='0' ? 'selected' : '' }}>Applied</option>
                <option value="1" {{ request('status')=='1' ? 'selected' : '' }}>Under Review</option>
                <option value="2" {{ request('status')=='2' ? 'selected' : '' }}>Approved</option>
                <option value="3" {{ request('status')=='3' ? 'selected' : '' }}>Rejected</option>
            </select>

            <button type="submit" class="btn btn-sm">Filter</button>
            @if(request()->hasAny(['search','status']))
            <a href="{{ route('admin.submissions.index', $filterWork ? ['work_id'=>$filterWork->id] : []) }}" class="btn btn-sm btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table Header --}}
    <div style="display:grid;grid-template-columns:100px 1fr 130px 130px 90px 90px 110px;gap:14px;padding:11px 18px;font-size:10.5px;color:var(--fg-3);text-transform:uppercase;letter-spacing:0.06em;font-weight:500;background:var(--bg-2);border-bottom:1px solid var(--border);">
        <span>ID</span>
        <span>Work</span>
        <span>Worker</span>
        <span>Reward</span>
        <span>Submitted</span>
        <span>Status</span>
        <span></span>
    </div>

    {{-- Rows --}}
    @forelse($submissions as $sub)
    @php
        $statusMap = [0=>'pending',1=>'pending',2=>'approved',3=>'rejected'];
        $statusLabel = [0=>'Applied',1=>'Under Review',2=>'Approved',3=>'Rejected'];
        $ini = strtoupper(substr($sub->worker?->username ?? 'U', 0, 1));
        $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
        $clr  = $clrs[ord($ini) % count($clrs)];
    @endphp
    <div style="display:grid;grid-template-columns:100px 1fr 130px 130px 90px 90px 110px;gap:14px;padding:13px 18px;align-items:center;border-bottom:1px solid var(--border);font-size:12.5px;">

        <span class="mono" style="font-size:11px;color:var(--fg-3);">SB-{{ str_pad($sub->id, 5, '0', STR_PAD_LEFT) }}</span>

        <div style="min-width:0;">
            <div style="font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sub->work?->title ?? '—' }}</div>
            <div style="font-size:10.5px;color:var(--fg-3);">{{ coinSymbol() }}{{ number_format($sub->work?->coins_per_worker ?? 0) }} reward</div>
        </div>

        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
            <div style="width:22px;height:22px;border-radius:50%;background:{{ $clr }};display:flex;align-items:center;justify-content:center;color:white;font-size:10px;font-weight:600;flex-shrink:0;">{{ $ini }}</div>
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11.5px;">{{ $sub->worker?->username ?? '—' }}</span>
        </div>

        <span class="mono" style="color:var(--coin);">{{ coinSymbol() }}{{ number_format($sub->work?->coins_per_worker ?? 0) }}</span>

        <span style="font-size:11px;color:var(--fg-3);">{{ $sub->created_at->diffForHumans(null, true) }}</span>

        <span class="status-pill status-{{ $statusMap[$sub->status] ?? 'draft' }}" style="width:fit-content;">
            {{ $statusLabel[$sub->status] ?? 'Unknown' }}
        </span>

        <div style="display:flex;gap:6px;justify-content:flex-end;">
            <a href="{{ route('admin.submissions.show', $sub->id) }}" class="btn btn-sm">Review</a>
        </div>

    </div>
    @empty
    <div style="padding:48px;text-align:center;color:var(--fg-3);">
        <i data-lucide="inbox" style="width:32px;height:32px;margin:0 auto 12px;display:block;opacity:0.3;"></i>
        <div style="font-size:13px;">No submissions found.</div>
    </div>
    @endforelse

</div>

{{-- Pagination --}}
@if($submissions->hasPages())
<div style="display:flex;justify-content:flex-end;margin-top:16px;font-size:12.5px;">
    {{ $submissions->withQueryString()->links() }}
</div>
@endif

@endsection
