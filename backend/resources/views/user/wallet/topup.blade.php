@extends('user.layouts.app')
@section('title', 'Add Coins')
@section('page-title', 'Add Coins')

@section('content')
<div style="display:grid; grid-template-columns:1fr 290px; gap:20px;" class="topup-grid"
     x-data="{ selectedChannel: '', selectedPackage: null, customAmount: '' }">

    {{-- LEFT --}}
    <div>

        {{-- Coin packages --}}
        @if($packages->count())
        <div class="card" style="padding:20px; margin-bottom:14px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Coin packages</h4>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;" class="pkg-grid">
                @foreach($packages as $pkg)
                <button type="button"
                        @click="selectedPackage = {{ $pkg->id }}; customAmount = ''"
                        :class="selectedPackage === {{ $pkg->id }} ? 'pkg-selected' : ''"
                        class="pkg-card"
                        style="position:relative; padding:16px 12px; border-radius:10px; border:1px solid var(--border); background:var(--surface-2); text-align:center; cursor:pointer; transition:all .15s; outline:none;">
                    @if($pkg->is_popular)
                    <div style="position:absolute; top:-9px; left:50%; transform:translateX(-50%); background:var(--accent); color:#000; font-size:9px; font-weight:700; padding:2px 9px; border-radius:999px; white-space:nowrap; letter-spacing:.04em;">POPULAR</div>
                    @endif
                    <div class="mono" style="font-size:22px; font-weight:700; color:var(--coin); margin-bottom:1px;">{{ number_format($pkg->coins) }}</div>
                    <div style="font-size:10.5px; color:var(--fg-4); margin-bottom:5px;">{{ coinSymbol() }} coins</div>
                    @if($pkg->bonus_coins)
                    <div style="font-size:10px; color:#22C55E; margin-bottom:6px;">+{{ number_format($pkg->bonus_coins) }} bonus</div>
                    @endif
                    <div style="font-size:13px; font-weight:600; color:var(--fg);">{{ $pkg->currency }} {{ number_format($pkg->price, 2) }}</div>
                </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Payment method --}}
        <div class="card" style="padding:20px; margin-bottom:14px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Payment method</h4>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;" class="pkg-grid">
                @foreach($channels as $channel)
                <button type="button"
                        @click="selectedChannel = '{{ $channel->code }}'"
                        :class="selectedChannel === '{{ $channel->code }}' ? 'pkg-selected' : ''"
                        class="pkg-card"
                        style="padding:14px 12px; border-radius:10px; border:1px solid var(--border); background:var(--surface-2); text-align:center; cursor:pointer; transition:all .15s; outline:none;">
                    <div style="font-size:13px; font-weight:500; color:var(--fg); margin-bottom:4px;">{{ $channel->name }}</div>
                    <div style="font-size:10.5px; color:var(--fg-3);">{{ $channel->is_manual ? 'Manual review' : 'Instant' }}</div>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Proceed --}}
        <div class="card" style="padding:20px;">
            <form method="POST" action="{{ route('user.wallet.topup.insert') }}">
                @csrf
                <input type="hidden" name="channel_code" :value="selectedChannel">
                <input type="hidden" name="package_id" :value="selectedPackage">

                <div x-show="!selectedPackage" style="margin-bottom:16px;">
                    <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Custom amount (USD)</label>
                    <input type="number" name="amount" x-model="customAmount" step="0.01" min="0.01"
                           style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 12px; color:var(--fg); font-size:14px; outline:none;"
                           placeholder="Enter amount in USD">
                </div>

                <button type="submit"
                        x-bind:disabled="!selectedChannel || (!selectedPackage && !customAmount)"
                        x-bind:style="(!selectedChannel || (!selectedPackage && !customAmount)) ? 'opacity:.45; pointer-events:none;' : ''"
                        class="btn btn-primary"
                        style="width:100%; justify-content:center; padding:11px; font-size:13.5px; display:flex; align-items:center; gap:7px;">
                    <i data-lucide="credit-card" style="width:14px; height:14px;"></i>
                    <span x-text="selectedChannel ? 'Proceed to payment' : 'Select a payment method first'"></span>
                </button>
            </form>
        </div>
    </div>

    {{-- RIGHT --}}
    <div>
        <div class="card" style="padding:22px; margin-bottom:14px; background:linear-gradient(135deg,rgba(47,84,235,0.08),transparent); border-color:rgba(47,84,235,0.2);">
            <div style="font-size:11px; color:var(--fg-3); margin-bottom:8px; text-transform:uppercase; letter-spacing:.08em;">Current balance</div>
            <div style="display:flex; align-items:baseline; gap:6px;">
                <span style="font-size:18px; color:var(--coin); font-family:ui-monospace,monospace; font-weight:600;">{{ coinSymbol() }}</span>
                <span class="mono" style="font-size:32px; font-weight:600; letter-spacing:-1.5px; line-height:1;">{{ number_format(auth()->user()->coin_balance) }}</span>
            </div>
        </div>

        <div class="card" style="padding:20px;">
            <div style="font-size:12.5px; font-weight:600; color:var(--fg); margin-bottom:14px;">Good to know</div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                @foreach([
                    ['icon'=>'zap','text'=>'Automatic payments are credited instantly after confirmation'],
                    ['icon'=>'shield','text'=>'Manual payments are verified within 24 hours'],
                    ['icon'=>'gift','text'=>'Packages include bonus coins on top of what you pay for'],
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
.pkg-card:focus { outline: none; }
.pkg-selected { border-color: var(--accent) !important; background: rgba(47,84,235,0.08) !important; }
@media (max-width: 860px) { .topup-grid { grid-template-columns: 1fr !important; } }
@media (max-width: 560px) { .pkg-grid { grid-template-columns: repeat(2,1fr) !important; } }
</style>
@endsection
