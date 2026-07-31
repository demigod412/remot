@extends('user.layouts.app')
@section('title', __('Referred Users'))
@section('page-title', __('Referred Users'))

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
                <th style="padding:12px 18px; text-align:left; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('User') }}</th>
                <th class="hide-sm" style="padding:12px 18px; text-align:left; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Username') }}</th>
                <th style="padding:12px 18px; text-align:center; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Email Verified') }}</th>
                <th style="padding:12px 18px; text-align:right; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Joined') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($referred as $u)
            <tr style="border-bottom:1px solid var(--border); transition:background .1s;"
                onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
                <td style="padding:13px 18px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:32px; height:32px; border-radius:50%; background:rgba(47,84,235,0.1); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:var(--accent); flex-shrink:0;">
                            {{ strtoupper(substr($u->firstname, 0, 1)) }}
                        </div>
                        <span style="color:var(--fg-2);">{{ $u->fullname }}</span>
                    </div>
                </td>
                <td class="hide-sm" style="padding:13px 18px; color:var(--fg-4); font-size:12.5px;" class="mono">
                    {{ '@' . $u->username }}
                </td>
                <td style="padding:13px 18px; text-align:center;">
                    @if($u->email_verified)
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    @else
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--fg-4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    @endif
                </td>
                <td style="padding:13px 18px; text-align:right; font-size:12px; color:var(--fg-4);">
                    {{ $u->created_at->format('M d, Y') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding:56px 18px; text-align:center; color:var(--fg-3);">
                    <div style="font-size:28px; margin-bottom:10px;">👥</div>
                    <div style="font-size:14px; font-weight:500; margin-bottom:4px; color:var(--fg);">{{ __('No referred users yet') }}</div>
                    <div style="font-size:13px;">{{ __('Share your referral link to start earning commissions.') }}</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:20px;">{{ $referred->links() }}</div>

<style>
@media (max-width: 768px) { .hide-sm { display: none !important; } }
</style>
@endsection
