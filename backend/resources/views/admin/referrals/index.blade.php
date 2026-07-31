@extends('admin.layouts.app')
@section('title', 'Referrals')
@section('page-title', 'Referral Management')

@section('content')

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
    <div class="jobstation-card" style="padding:18px 20px;">
        <div class="label" style="margin-bottom:6px;">Total Referrals</div>
        <div style="font-size:26px;font-weight:700;color:var(--fg);">{{ number_format($stats['total_referrals']) }}</div>
    </div>
    <div class="jobstation-card" style="padding:18px 20px;">
        <div class="label" style="margin-bottom:6px;">Total Coins Paid Out</div>
        <div class="coin-badge" style="font-size:20px;font-weight:700;">{{ formatCoins($stats['total_earnings']) }}</div>
    </div>
</div>

{{-- Top referrers --}}
@if($stats['top_referrers']->isNotEmpty())
<div class="jobstation-card" style="padding:20px;margin-bottom:24px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:14px;">Top Referrers</div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        @foreach($stats['top_referrers'] as $u)
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
            <a href="{{ route('admin.users.show', $u->id) }}" style="color:var(--fg);text-decoration:none;"
               onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg)'">
                {{ $u->username }}
            </a>
            <span style="color:var(--fg-3);">{{ $u->referred_users_count }} referral{{ $u->referred_users_count !== 1 ? 's' : '' }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Search + table --}}
<div class="jobstation-card" style="padding:0;overflow:hidden;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
        <form method="GET" style="display:flex;gap:8px;flex:1;">
            <input name="search" value="{{ request('search') }}" placeholder="Search by username or email…"
                   style="flex:1;font-size:13px;" autocomplete="off">
            <button class="btn btn-primary" style="padding:7px 16px;font-size:13px;">Search</button>
            @if(request('search'))
            <a href="{{ route('admin.referrals.index') }}" class="btn" style="padding:7px 14px;font-size:13px;">Clear</a>
            @endif
        </form>
    </div>

    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 16px;text-align:left;color:var(--fg-3);font-weight:500;">Referrer</th>
                <th style="padding:10px 16px;text-align:left;color:var(--fg-3);font-weight:500;">Referred User</th>
                <th style="padding:10px 16px;text-align:right;color:var(--fg-3);font-weight:500;">Coins Earned</th>
                <th style="padding:10px 16px;text-align:right;color:var(--fg-3);font-weight:500;">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($earnings as $e)
            <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:10px 16px;">
                    @if($e->earner)
                    <a href="{{ route('admin.users.show', $e->earner_id) }}"
                       style="color:var(--fg);text-decoration:none;"
                       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg)'">
                        {{ $e->earner->username }}
                    </a>
                    <div style="font-size:11.5px;color:var(--fg-4);">{{ $e->earner->email }}</div>
                    @else
                    <span style="color:var(--fg-4);">Deleted user</span>
                    @endif
                </td>
                <td style="padding:10px 16px;">
                    @if($e->referredUser)
                    <a href="{{ route('admin.users.show', $e->referred_user_id) }}"
                       style="color:var(--fg);text-decoration:none;"
                       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg)'">
                        {{ $e->referredUser->username }}
                    </a>
                    <div style="font-size:11.5px;color:var(--fg-4);">{{ $e->referredUser->email }}</div>
                    @else
                    <span style="color:var(--fg-4);">Deleted user</span>
                    @endif
                </td>
                <td style="padding:10px 16px;text-align:right;">
                    <span class="coin-badge">{{ formatCoins($e->coins_earned) }}</span>
                </td>
                <td style="padding:10px 16px;text-align:right;color:var(--fg-3);">
                    {{ $e->created_at->format('M d, Y') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding:32px;text-align:center;color:var(--fg-4);">No referral records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($earnings->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);">
        {{ $earnings->links() }}
    </div>
    @endif
</div>

@endsection
