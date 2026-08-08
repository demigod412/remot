@extends('user.layouts.app')
@section('title', 'Withdraw Earnings')
@section('page-title', 'Withdraw Earnings')

@section('content')
@php
    $windowStatus = app(\App\Services\WithdrawalPolicy::class)->windowStatus();
@endphp

@if (! $windowStatus['open'])
    {{-- Shown before the form, not after a rejected submit. Letting someone pick a
         method and type a wallet address only to be refused on the day of the month
         is a bad way to learn a rule that has nothing to do with them. --}}
    <div style="display:flex;align-items:flex-start;gap:10px;padding:14px 16px;border-radius:10px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.28);margin-bottom:18px;">
        <i data-lucide="calendar-clock" style="width:16px;height:16px;color:#F59E0B;flex-shrink:0;margin-top:1px;"></i>
        <div style="font-size:13px;color:var(--fg-2);line-height:1.6;">
            <strong style="color:#F59E0B;">Withdrawals are closed today.</strong>
            Requests can be made between the {{ $windowStatus['opens_on'] }} and the
            {{ $windowStatus['closes_on'] }} of each month. Your balance is unaffected and
            keeps earning in the meantime.
        </div>
    </div>
@endif

@php
    // Withdrawals draw on the USD earnings balance. This read coin_balance, which
    // gated the form on the wrong number entirely: a worker with plenty of coins
    // and no earnings was shown the form and would fail server-side validation.
    $minCashout  = gs()->min_cashout ?? 50;
    $userBalance = auth()->user()->usd_balance;
@endphp

{{-- Minimum cashout notice --}}
@if($userBalance < $minCashout)
<div style="display:flex; align-items:center; gap:12px; padding:14px 18px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:12px; margin-bottom:20px;">
    <i data-lucide="lock" style="width:18px; height:18px; color:#EF4444; flex-shrink:0;"></i>
    <div>
        <div style="font-size:13px; font-weight:600; color:#EF4444; margin-bottom:2px;">Insufficient balance</div>
        <div style="font-size:12px; color:var(--fg-3);">Minimum cashout is <strong class="mono" style="color:var(--fg);">{{ formatUsd($minCashout) }}</strong>. You have <strong class="mono" style="color:var(--fg);">{{ formatUsd($userBalance) }}</strong>. Keep earning to unlock.</div>
    </div>
</div>
@else
<div style="display:flex; align-items:center; gap:10px; padding:11px 16px; background:rgba(34,197,94,0.07); border:1px solid rgba(34,197,94,0.18); border-radius:10px; margin-bottom:20px; font-size:12.5px; color:var(--fg-3);">
    <i data-lucide="check-circle-2" style="width:14px; height:14px; color:#22C55E; flex-shrink:0;"></i>
    Minimum cashout: <strong class="mono" style="color:var(--fg); margin-left:4px;">{{ formatUsd($minCashout) }}</strong>
</div>
@endif

<div style="display:grid; grid-template-columns:1fr 300px; gap:20px;" class="cashout-grid">

    {{-- LEFT: Form --}}
    <div>
        @if($methods->isEmpty())
        <div class="card" style="padding:60px 24px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">💳</div>
            <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:8px;">No withdrawal methods available</div>
            <p style="font-size:13px; color:var(--fg-3); margin:0;">Please check back later or contact support.</p>
        </div>
        @else
        <form method="POST" action="{{ route('user.wallet.cashout.preview') }}">
            @csrf

            {{-- Method selection --}}
            <div class="card" style="padding:20px; margin-bottom:14px;">
                <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Select withdrawal method</h4>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($methods as $method)
                    <label class="cashout-method" style="display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:10px; border:1px solid var(--border); cursor:pointer; transition:border-color .15s;">
                        <input type="radio" name="payout_method_id" value="{{ $method->id }}" required
                               style="accent-color:var(--accent); width:16px; height:16px; flex-shrink:0;">
                        <div style="flex:1;">
                            <div style="font-size:13px; font-weight:500; color:var(--fg);">{{ $method->name }}</div>
                            <div style="font-size:11.5px; color:var(--fg-3); margin-top:2px;">
                                {{ $method->currency }}
                                · Min: <span class="mono">{{ formatUsd($method->min_coins) }}</span>
                                @if($method->max_coins)
                                · Max: <span class="mono">{{ formatUsd($method->max_coins) }}</span>
                                @endif
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('payout_method_id')
                <div style="font-size:11.5px; color:#EF4444; margin-top:8px;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Amount --}}
            <div class="card" style="padding:20px; margin-bottom:16px;">
                <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Withdrawal amount</h4>
                <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Coin amount</label>
                <input type="number" name="coin_amount" value="{{ old('coin_amount') }}" step="1" min="1"
                       style="width:100%; background:var(--surface-2); border:1px solid {{ $errors->has('coin_amount') ? '#EF4444' : 'var(--border)' }}; border-radius:8px; padding:10px 12px; color:var(--fg); font-size:15px; font-family:ui-monospace,monospace; outline:none; letter-spacing:-0.5px;"
                       placeholder="e.g. 1000" required>
                @error('coin_amount')
                <div style="font-size:11.5px; color:#EF4444; margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-size:13px; display:inline-flex; align-items:center; gap:7px;">
                <i data-lucide="eye" style="width:14px; height:14px;"></i> Preview withdrawal
            </button>
        </form>
        @endif
    </div>

    {{-- RIGHT: Sidebar --}}
    <div>
        {{-- Balance --}}
        <div class="card" style="padding:22px; margin-bottom:14px; background:linear-gradient(135deg,rgba(47,84,235,0.08),transparent); border-color:rgba(47,84,235,0.2);">
            <div style="font-size:11px; color:var(--fg-3); margin-bottom:8px; text-transform:uppercase; letter-spacing:.08em;">Available balance</div>
            {{-- Withdrawals draw on USD earnings, not the coin spending balance. --}}
            <div style="display:flex; align-items:baseline; gap:6px; margin-bottom:14px;">
                <span class="mono" style="font-size:34px; font-weight:600; letter-spacing:-1.5px; line-height:1;">{{ formatUsd(auth()->user()->usd_balance) }}</span>
            </div>
            <a href="{{ route('user.wallet.cashout.history') }}" style="font-size:12px; color:var(--accent); text-decoration:none;">View history →</a>
        </div>

        {{-- How it works --}}
        <div class="card" style="padding:20px;">
            <div style="font-size:12.5px; font-weight:600; color:var(--fg); margin-bottom:14px;">How withdrawals work</div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                @foreach([
                    ['icon'=>'clock','text'=>'Requests are processed within 1–3 business days'],
                    ['icon'=>'shield-check','text'=>'KYC verification required for first withdrawal'],
                    ['icon'=>'percent','text'=>'A small fee may apply depending on the method'],
                ] as $tip)
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <i data-lucide="{{ $tip['icon'] }}" style="width:14px; height:14px; color:var(--fg-3); margin-top:1px; flex-shrink:0;"></i>
                    <span style="font-size:12px; color:var(--fg-3); line-height:1.55;">{{ $tip['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
.cashout-method:has(input:checked) { border-color: var(--accent) !important; background: rgba(47,84,235,0.05); }
.cashout-method:hover { border-color: rgba(255,255,255,0.14) !important; }
@media (max-width: 860px) { .cashout-grid { grid-template-columns: 1fr !important; } }
</style>
@endsection
