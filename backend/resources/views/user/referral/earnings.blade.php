@extends('user.layouts.app')
@section('title', __('Referral Earnings'))
@section('page-title', __('Referral Earnings'))

@section('content')

<div style="margin-bottom:18px;">
    <a href="{{ route('user.referral.index') }}"
       style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:var(--fg-3); text-decoration:none; transition:color .15s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">
        <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M11 4L6 9l5 5"/></svg>
        {{ __('Back to Referrals') }}
    </a>
</div>

<div class="card" style="padding:0; overflow:hidden;">
    <table style="width:100%; border-collapse:collapse; font-size:13.5px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:12px 18px; text-align:left; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Referred User') }}</th>
                <th style="padding:12px 18px; text-align:right; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Commission') }}</th>
                <th style="padding:12px 18px; text-align:center; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Status') }}</th>
                <th class="hide-sm" style="padding:12px 18px; text-align:right; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($earnings as $earning)
            <tr style="border-bottom:1px solid var(--border); transition:background .1s;"
                onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
                <td style="padding:13px 18px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:32px; height:32px; border-radius:50%; background:rgba(47,84,235,0.1); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:var(--accent); flex-shrink:0;">
                            {{ strtoupper(substr($earning->referredUser->firstname ?? 'U', 0, 1)) }}
                        </div>
                        <span style="color:var(--fg-2);">{{ $earning->referredUser->fullname ?? __('Unknown') }}</span>
                    </div>
                </td>
                <td style="padding:13px 18px; text-align:right; font-weight:600; color:#22C55E;" class="mono">
                    {{ formatCoins($earning->coins_earned) }}
                </td>
                <td style="padding:13px 18px; text-align:center;">
                    <span style="font-size:11px; font-weight:600; padding:2px 9px; border-radius:999px; background:rgba(34,197,94,0.1); color:#22C55E; border:1px solid rgba(34,197,94,0.18);">
                        {{ __('Paid') }}
                    </span>
                </td>
                <td class="hide-sm" style="padding:13px 18px; text-align:right; font-size:12px; color:var(--fg-4);">
                    {{ $earning->created_at->format('M d, Y') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding:56px 18px; text-align:center; color:var(--fg-3);">
                    <div style="font-size:28px; margin-bottom:10px;">💰</div>
                    <div style="font-size:14px; font-weight:500; margin-bottom:4px; color:var(--fg);">{{ __('No referral earnings yet') }}</div>
                    <div style="font-size:13px;">{{ __('Earnings will appear here when your referrals make transactions.') }}</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:20px;">{{ $earnings->links() }}</div>

<style>
@media (max-width: 768px) { .hide-sm { display: none !important; } }
</style>
@endsection
