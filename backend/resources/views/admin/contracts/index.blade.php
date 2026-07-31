@extends('admin.layouts.app')
@section('title', 'Contracts')
@section('page-title', 'Contracts')

@section('content')

{{-- Stat Tiles --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;">
    <div class="jobstation-card" style="padding:16px;">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(47,84,235,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="file-text" style="width:16px;height:16px;color:var(--accent);"></i>
        </div>
        <div class="mono" style="font-size:22px;font-weight:700;color:var(--fg);letter-spacing:-0.5px;">{{ number_format($stats['total']) }}</div>
        <div style="font-size:11px;color:var(--fg-3);margin-top:2px;">Total Contracts</div>
    </div>
    <div class="jobstation-card" style="padding:16px;">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(96,165,250,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="loader" style="width:16px;height:16px;color:#60A5FA;"></i>
        </div>
        <div class="mono" style="font-size:22px;font-weight:700;color:var(--fg);letter-spacing:-0.5px;">{{ number_format($stats['active']) }}</div>
        <div style="font-size:11px;color:var(--fg-3);margin-top:2px;">Active</div>
    </div>
    <div class="jobstation-card" style="padding:16px;">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(34,197,94,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="check-circle" style="width:16px;height:16px;color:#22C55E;"></i>
        </div>
        <div class="mono" style="font-size:22px;font-weight:700;color:var(--fg);letter-spacing:-0.5px;">{{ number_format($stats['completed']) }}</div>
        <div style="font-size:11px;color:var(--fg-3);margin-top:2px;">Completed</div>
    </div>
    <div class="jobstation-card" style="padding:16px;">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="alert-triangle" style="width:16px;height:16px;color:#EF4444;"></i>
        </div>
        <div class="mono" style="font-size:22px;font-weight:700;color:var(--fg);letter-spacing:-0.5px;">{{ number_format($stats['disputed']) }}</div>
        <div style="font-size:11px;color:var(--fg-3);margin-top:2px;">Disputed</div>
    </div>
    <div class="jobstation-card" style="padding:16px;">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,213,71,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="coins" style="width:16px;height:16px;color:var(--coin);"></i>
        </div>
        <div class="mono" style="font-size:22px;font-weight:700;color:var(--coin);letter-spacing:-0.5px;">{{ formatCoins($stats['commission']) }}</div>
        <div style="font-size:11px;color:var(--fg-3);margin-top:2px;">Commission Earned</div>
    </div>
</div>

{{-- Filter bar --}}
<div class="jobstation-card" style="padding:14px 16px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.contracts.index') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:180px;position:relative;">
            <i data-lucide="search" style="width:14px;height:14px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--fg-3);pointer-events:none;"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Title or reference…"
                   style="width:100%;padding:8px 10px 8px 32px;font-size:13px;box-sizing:border-box;">
        </div>
        <div>
            <select name="status" style="font-size:13px;padding:8px 10px;min-width:130px;">
                <option value="">All Statuses</option>
                @foreach([0=>'Offered',1=>'Accepted',2=>'Submitted',3=>'Completed',4=>'Declined',5=>'Cancelled',6=>'Disputed'] as $val => $lbl)
                <option value="{{ $val }}" @selected(request('status') !== null && request('status') == $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="font-size:13px;padding:8px 16px;">Filter</button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('admin.contracts.index') }}" class="btn" style="font-size:13px;padding:8px 14px;">Clear</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="jobstation-card" style="padding:0;overflow:hidden;">
    {{-- Header --}}
    <div style="display:grid;grid-template-columns:120px 1fr 160px 100px 110px 110px 100px 72px;padding:10px 16px;border-bottom:1px solid var(--border);">
        <span class="label">Reference</span>
        <span class="label">Title</span>
        <span class="label">Employer → Worker</span>
        <span class="label">Amount</span>
        <span class="label">Commission</span>
        <span class="label">Status</span>
        <span class="label">Date</span>
        <span class="label"></span>
    </div>

    @forelse($contracts as $contract)
    <div style="display:grid;grid-template-columns:120px 1fr 160px 100px 110px 110px 100px 72px;padding:12px 16px;border-bottom:1px solid var(--border);align-items:center;{{ $contract->status === 6 ? 'background:rgba(239,68,68,0.04);' : '' }}"
         onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='{{ $contract->status === 6 ? 'rgba(239,68,68,0.04)' : 'transparent' }}'">
        <span class="mono" style="font-size:11px;color:var(--fg-3);">{{ $contract->reference }}</span>
        <span style="font-size:13px;color:var(--fg-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding-right:12px;">{{ $contract->title }}</span>
        <div style="font-size:12px;">
            <div style="color:var(--fg-2);">{{ $contract->employer?->username ?? '—' }}</div>
            <div style="color:var(--fg-3);">→ {{ $contract->worker?->username ?? '—' }}</div>
        </div>
        <span style="font-size:12px;color:var(--accent);font-weight:500;">{{ formatCoins($contract->amount) }}</span>
        <span style="font-size:12px;">
            @if($contract->status === 3 && $contract->commission_amount > 0)
                <span style="color:var(--coin);">{{ formatCoins($contract->commission_amount) }}</span>
            @else
                <span style="color:var(--fg-4);">—</span>
            @endif
        </span>
        <span>
            <span class="{{ $contract->statusColor }}" style="font-size:11px;">{{ $contract->statusLabel }}</span>
        </span>
        <span style="font-size:12px;color:var(--fg-3);">{{ $contract->created_at->format('M d, Y') }}</span>
        <span>
            <a href="{{ route('admin.contracts.show', $contract->id) }}" class="btn btn-sm">View</a>
        </span>
    </div>
    @empty
    <div style="padding:48px;text-align:center;color:var(--fg-3);font-size:13px;">
        <i data-lucide="file-text" style="width:28px;height:28px;margin:0 auto 10px;display:block;opacity:0.3;"></i>
        No contracts found.
    </div>
    @endforelse

    <div style="padding:12px 16px;border-top:1px solid var(--border);">
        {{ $contracts->links() }}
    </div>
</div>

@endsection
