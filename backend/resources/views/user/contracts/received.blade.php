@extends('user.layouts.app')
@section('title', 'Received Contracts')
@section('page-title', 'Received Contracts')

@section('content')

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
        @foreach(['' => 'All', '0' => 'Offered', '1' => 'Accepted', '2' => 'Submitted', '3' => 'Completed', '4' => 'Declined', '5' => 'Cancelled', '6' => 'Disputed'] as $val => $label)
        <a href="{{ route('user.contracts.received', $val !== '' ? ['status' => $val] : []) }}"
           style="font-size:12px; font-weight:500; padding:5px 11px; border-radius:7px; text-decoration:none; transition:all .14s;
                  {{ request('status') === $val ? 'background:var(--accent); color:white; border:1px solid var(--accent);' : 'background:var(--surface); color:var(--fg-2); border:1px solid var(--border);' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

@forelse($contracts as $contract)
<div class="card" style="padding:16px 18px; margin-bottom:10px; transition:box-shadow .14s;"
     onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.07)'" onmouseout="this.style.boxShadow=''">
    <div style="display:flex; align-items:flex-start; gap:14px;">

        <div style="width:38px; height:38px; border-radius:10px; background:rgba(96,165,250,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="file-input" style="width:18px; height:18px; color:#60A5FA;"></i>
        </div>

        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:6px;">
                <div style="font-size:14px; font-weight:600; color:var(--fg); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $contract->title }}</div>
                <span class="badge badge-{{ $contract->statusColor }}" style="flex-shrink:0; font-size:11px;">{{ $contract->statusLabel }}</span>
            </div>

            <div style="font-size:12px; color:var(--fg-3); margin-bottom:4px; display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                <span>From: <span style="color:var(--fg-2);">{{ $contract->employer->fullname ?: $contract->employer->username }}</span></span>
                @include('user.partials.rating-stars', ['user' => $contract->employer])
                <span style="color:var(--border-strong);">·</span>
                @if($contract->status === 3 && $contract->worker_payout > 0)
                    <span class="mono" style="color:#22C55E; font-weight:600;">{{ formatCoins($contract->worker_payout) }} earned</span>
                    <span style="color:var(--fg-4);">(of {{ formatCoins($contract->amount) }})</span>
                @else
                    <span class="mono" style="color:#E6C400; font-weight:600;">{{ formatCoins($contract->amount) }}</span>
                @endif
                @if($contract->deadline_at)
                <span style="color:var(--border-strong);">·</span>
                <span>Due {{ $contract->deadline_at->format('M d, Y') }}</span>
                @endif
                @if($contract->isOverdue)
                <span style="color:var(--danger); display:inline-flex; align-items:center; gap:3px;">
                    <i data-lucide="alert-circle" style="width:11px; height:11px;"></i> Overdue
                </span>
                @endif
            </div>

            <div style="font-size:11px; color:var(--fg-4); margin-bottom:10px;">
                Ref: {{ $contract->reference }} · {{ $contract->created_at->diffForHumans() }}
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="{{ route('user.contracts.show', $contract->id) }}"
                   style="font-size:12px; font-weight:500; padding:5px 12px; border-radius:7px; background:var(--accent-soft); color:var(--accent); text-decoration:none; border:1px solid rgba(47,84,235,0.2); transition:all .14s;"
                   onmouseover="this.style.background='rgba(47,84,235,0.18)'" onmouseout="this.style.background='var(--accent-soft)'">
                    View
                </a>

                @if($contract->status === 0)
                <form method="POST" action="{{ route('user.contracts.accept', $contract->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit"
                            style="font-size:12px; font-weight:500; padding:5px 12px; border-radius:7px; background:rgba(34,197,94,0.1); color:#22C55E; border:1px solid rgba(34,197,94,0.2); cursor:pointer; font-family:inherit; transition:all .14s;"
                            onmouseover="this.style.background='rgba(34,197,94,0.18)'" onmouseout="this.style.background='rgba(34,197,94,0.1)'"
                            onclick="return confirm('Accept this contract? {{ formatCoins($contract->amount) }} will be held in escrow.')">
                        Accept
                    </button>
                </form>
                @endif

                @if($contract->status === 1)
                <a href="{{ route('user.contracts.show', $contract->id) }}"
                   style="font-size:12px; font-weight:500; padding:5px 12px; border-radius:7px; background:rgba(96,165,250,0.1); color:#60A5FA; text-decoration:none; border:1px solid rgba(96,165,250,0.2); transition:all .14s;"
                   onmouseover="this.style.background='rgba(96,165,250,0.18)'" onmouseout="this.style.background='rgba(96,165,250,0.1)'">
                    Submit Work
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@empty
<div class="card" style="padding:56px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">📥</div>
    <div style="font-size:15px; font-weight:500; color:var(--fg); margin-bottom:6px;">No contracts received yet</div>
    <div style="font-size:13px; color:var(--fg-3);">Contract offers sent to you will appear here.</div>
</div>
@endforelse

{{ $contracts->withQueryString()->links() }}
@endsection
