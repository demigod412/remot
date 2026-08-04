@extends('user.layouts.app')
@section('title', 'Referral Program')
@section('page-title', 'Referrals')

@section('content')

{{-- Stats --}}
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px;" class="ref-stat-grid">
    <div class="card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Referred users</div>
        <div class="mono" style="font-size:28px; font-weight:600; color:var(--fg); letter-spacing:-0.5px;">{{ $stats['total_referred'] }}</div>
    </div>
    <div class="card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Total earned</div>
        <div style="display:flex; align-items:baseline; gap:3px;">
            <span class="mono" style="font-size:26px; font-weight:600; color:var(--coin); letter-spacing:-0.5px;">{{ formatCoins($stats['total_earned']) }}</span>
        </div>
    </div>
    <div class="card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Pending</div>
        <div style="display:flex; align-items:baseline; gap:3px;">
            <span class="mono" style="font-size:26px; font-weight:600; color:#F59E0B; letter-spacing:-0.5px;">{{ formatCoins($stats['pending']) }}</span>
        </div>
    </div>
</div>

{{-- Referral link --}}
<div class="card" style="padding:24px; margin-bottom:14px;" x-data="{ copied: false }">
    <div style="font-size:14px; font-weight:600; color:var(--fg); margin-bottom:6px;">Your referral link</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 16px; line-height:1.55;">
        Invite friends to join {{ gs()->site_name ?? 'Job Station' }}. When they register and complete tasks, you earn
        <span class="mono" style="color:var(--coin); font-weight:600;">{{ formatCoins(gs()->ref_commission ?? 0) }}</span> per referral.
    </p>
    <div style="display:flex; gap:8px;">
        <input type="text" value="{{ $referralLink }}" readonly
               style="flex:1; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--fg-2); font-size:12.5px; font-family:ui-monospace,monospace; outline:none;">
        <button @click="navigator.clipboard.writeText('{{ $referralLink }}').then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
                class="btn"
                style="padding:9px 16px; font-size:12.5px; display:flex; align-items:center; gap:6px; flex-shrink:0;"
                :style="copied ? 'border-color:rgba(34,197,94,0.4); color:#22C55E;' : ''">
            <i x-show="!copied" data-lucide="copy" style="width:14px; height:14px;"></i>
            <i x-show="copied" data-lucide="check" style="width:14px; height:14px; color:#22C55E;"></i>
            <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
        </button>
    </div>
</div>

{{-- Quick links --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;" class="ref-link-grid">
    <a href="{{ route('user.referral.referred') }}" class="card"
       style="padding:18px; display:flex; align-items:center; gap:14px; text-decoration:none; transition:border-color .15s;"
       onmouseover="this.style.borderColor='rgba(47,84,235,0.3)'" onmouseout="this.style.borderColor='var(--border)'">
        <div style="width:38px; height:38px; border-radius:10px; background:rgba(47,84,235,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="users" style="width:18px; height:18px; color:var(--accent);"></i>
        </div>
        <div>
            <div style="font-size:13.5px; font-weight:500; color:var(--fg);">Referred users</div>
            <div style="font-size:11.5px; color:var(--fg-3); margin-top:2px;">View people you referred</div>
        </div>
        <i data-lucide="chevron-right" style="width:16px; height:16px; color:var(--fg-4); margin-left:auto;"></i>
    </a>
    <a href="{{ route('user.referral.earnings') }}" class="card"
       style="padding:18px; display:flex; align-items:center; gap:14px; text-decoration:none; transition:border-color .15s;"
       onmouseover="this.style.borderColor='rgba(245,213,71,0.3)'" onmouseout="this.style.borderColor='var(--border)'">
        <div style="width:38px; height:38px; border-radius:10px; background:rgba(245,213,71,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="coins" style="width:18px; height:18px; color:var(--coin);"></i>
        </div>
        <div>
            <div style="font-size:13.5px; font-weight:500; color:var(--fg);">Referral earnings</div>
            <div style="font-size:11.5px; color:var(--fg-3); margin-top:2px;">Coin earnings history</div>
        </div>
        <i data-lucide="chevron-right" style="width:16px; height:16px; color:var(--fg-4); margin-left:auto;"></i>
    </a>
</div>

{{-- How it works --}}
<div class="card" style="padding:22px;">
    <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:18px;">How it works</div>
    <div style="display:flex; flex-direction:column; gap:18px;">
        @foreach([
            ['step'=>'1','title'=>'Share your link','desc'=>'Share your unique referral link with friends or on social media.'],
            ['step'=>'2','title'=>'They register','desc'=>'Your friend creates an account using your referral link.'],
            ['step'=>'3','title'=>'You earn coins','desc'=>'Once they complete their first task, coins are added to your balance automatically.'],
        ] as $step)
        <div style="display:flex; gap:14px; align-items:flex-start;">
            <div style="width:28px; height:28px; border-radius:50%; background:rgba(47,84,235,0.12); border:1px solid rgba(47,84,235,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span style="font-size:11.5px; font-weight:700; color:var(--accent);">{{ $step['step'] }}</span>
            </div>
            <div>
                <div style="font-size:13px; font-weight:500; color:var(--fg); margin-bottom:3px;">{{ $step['title'] }}</div>
                <div style="font-size:12px; color:var(--fg-3); line-height:1.55;">{{ $step['desc'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
@media (max-width: 640px) {
    .ref-stat-grid { grid-template-columns: 1fr 1fr !important; }
    .ref-link-grid { grid-template-columns: 1fr !important; }
}
</style>
@endsection
