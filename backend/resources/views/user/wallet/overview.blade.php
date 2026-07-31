@extends('user.layouts.app')
@section('title', 'Wallet')
@section('page-title', 'Wallet')

@section('content')

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px;" class="wallet-grid">

    {{-- ── LEFT COLUMN ─────────────────────────────────────────── --}}
    <div>

        {{-- Balance Hero --}}
        @php
            $walletCurrencies = gs()->currencies ?? [];
            $walletDefaultCode = gs()->default_currency ?? ($walletCurrencies[0]['code'] ?? null);
        @endphp
        <div class="card" style="padding:28px; background:linear-gradient(135deg,#1a1533,var(--surface)); border-color:rgba(47,84,235,0.25); margin-bottom:16px; position:relative; overflow:hidden;"
             x-data="{
                currencies: {{ json_encode($walletCurrencies) }},
                sel: '{{ $walletDefaultCode }}',
                balance: {{ (float) $user->coin_balance }},
                get cur() { return this.currencies.find(c => c.code === this.sel) || null },
                get fiat() { return this.cur ? (this.balance * this.cur.rate).toLocaleString('en-US', {minimumFractionDigits:0,maximumFractionDigits:0}) : null }
             }">
            <div style="position:absolute; right:-40px; top:-40px; width:200px; height:200px; border-radius:50%; background:radial-gradient(circle,rgba(47,84,235,0.25),transparent 70%); pointer-events:none;"></div>
            <div style="position:relative;">
                <div style="font-size:11.5px; color:var(--fg-3); margin-bottom:10px; text-transform:uppercase; letter-spacing:0.08em;">Available balance</div>
                <div style="display:flex; align-items:baseline; gap:8px; margin-bottom:8px;">
                    <span style="font-size:44px; color:var(--coin); font-family:ui-monospace,monospace; font-weight:600; line-height:1;">{{ coinSymbol() }}</span>
                    <span class="mono" style="font-size:clamp(36px,5vw,64px); font-weight:600; letter-spacing:-2.5px; line-height:1;">{{ number_format($user->coin_balance) }}</span>
                </div>
                {{-- Fiat equivalent --}}
                <template x-if="cur && currencies.length > 0">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                        <span style="font-size:14px; color:var(--fg-2);">≈ <span x-text="(cur ? cur.symbol + ' ' : '') + fiat" style="font-weight:600; color:var(--fg);"></span></span>
                        <select x-model="sel" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:3px 8px; color:var(--fg); font-size:12px; font-family:ui-monospace,monospace; outline:none; cursor:pointer;">
                            <template x-for="c in currencies" :key="c.code">
                                <option :value="c.code" x-text="c.code"></option>
                            </template>
                        </select>
                    </div>
                </template>
                <div style="font-size:13px; color:var(--fg-2); margin-bottom:24px;">Lifetime earned: <span class="mono" style="color:var(--fg);">{{ coinSymbol() }}{{ number_format($stats['total_earned']) }}</span></div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('user.wallet.cashout') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="download" style="width:14px; height:14px;"></i> Withdraw
                    </a>
                    <a href="{{ route('user.wallet.topup') }}" class="btn" style="display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="upload" style="width:14px; height:14px;"></i> Top up
                    </a>
                    <a href="{{ route('user.wallet.ledger') }}" class="btn" style="display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="list" style="width:14px; height:14px;"></i> Full history
                    </a>
                </div>
            </div>
        </div>

        {{-- Stat tiles --}}
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px;" class="wallet-stats">
            <div class="card" style="padding:16px;">
                <div class="label" style="margin-bottom:8px;">Total topped up</div>
                <div style="display:flex; align-items:baseline; gap:3px;">
                    <span style="font-size:12px; color:var(--coin); font-family:ui-monospace,monospace;">{{ coinSymbol() }}</span>
                    <span class="mono" style="font-size:20px; font-weight:600; color:var(--fg);">{{ number_format($stats['total_topup']) }}</span>
                </div>
            </div>
            <div class="card" style="padding:16px;">
                <div class="label" style="margin-bottom:8px;">Total withdrawn</div>
                <div style="display:flex; align-items:baseline; gap:3px;">
                    <span style="font-size:12px; color:var(--coin); font-family:ui-monospace,monospace;">{{ coinSymbol() }}</span>
                    <span class="mono" style="font-size:20px; font-weight:600; color:var(--fg);">{{ number_format($stats['total_cashout']) }}</span>
                </div>
            </div>
            <div class="card" style="padding:16px;">
                <div class="label" style="margin-bottom:8px;">Total earned</div>
                <div style="display:flex; align-items:baseline; gap:3px;">
                    <span style="font-size:12px; color:#22C55E; font-family:ui-monospace,monospace;">{{ coinSymbol() }}</span>
                    <span class="mono" style="font-size:20px; font-weight:600; color:#22C55E;">{{ number_format($stats['total_earned']) }}</span>
                </div>
            </div>
        </div>

        {{-- Transactions --}}
        <div class="card" style="padding:0; overflow:hidden;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border);">
                <div style="font-size:13px; font-weight:600;">Recent transactions</div>
                <a href="{{ route('user.wallet.ledger') }}" style="font-size:12px; color:var(--accent); text-decoration:none;">View all →</a>
            </div>

            {{-- Recent top-ups --}}
            @foreach($recentTopups as $i => $tx)
            <div style="display:flex; align-items:center; gap:14px; padding:12px 18px; border-bottom:1px solid var(--border);">
                <div style="width:32px; height:32px; border-radius:8px; background:rgba(34,197,94,0.12); color:#22C55E; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i data-lucide="arrow-down" style="width:14px; height:14px;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:500; color:var(--fg);">Coin top-up</div>
                    <div style="font-size:11px; color:var(--fg-3);">{{ $tx->created_at->format('M d, Y') }}</div>
                </div>
                <span class="mono" style="font-size:14px; font-weight:500; color:#22C55E; flex-shrink:0;">+{{ coinSymbol() }}{{ number_format($tx->coins_credited) }}</span>
            </div>
            @endforeach

            {{-- Recent cashouts --}}
            @foreach($recentCashouts as $tx)
            <div style="display:flex; align-items:center; gap:14px; padding:12px 18px; border-bottom:1px solid var(--border);">
                <div style="width:32px; height:32px; border-radius:8px; background:rgba(239,68,68,0.10); color:#EF4444; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i data-lucide="arrow-up" style="width:14px; height:14px;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:500; color:var(--fg);">Withdrawal
                        @if($tx->payoutMethod)— {{ $tx->payoutMethod->name ?? '' }}@endif
                    </div>
                    <div style="font-size:11px; color:var(--fg-3);">{{ $tx->created_at->format('M d, Y') }}
                        @if($tx->status == 0) · <span style="color:#F59E0B;">Pending</span>
                        @elseif($tx->status == 1) · <span style="color:#22C55E;">Approved</span>
                        @elseif($tx->status == 2) · <span style="color:#EF4444;">Rejected</span>
                        @endif
                    </div>
                </div>
                <span class="mono" style="font-size:14px; font-weight:500; color:var(--fg); flex-shrink:0;">−{{ coinSymbol() }}{{ number_format($tx->net_coins_deducted) }}</span>
            </div>
            @endforeach

            @if($recentTopups->isEmpty() && $recentCashouts->isEmpty())
            <div style="padding:40px 24px; text-align:center; color:var(--fg-3);">
                <div style="font-size:28px; margin-bottom:10px;">💰</div>
                <div style="font-size:13.5px; font-weight:500; color:var(--fg); margin-bottom:6px;">No transactions yet</div>
                <a href="{{ route('user.wallet.topup') }}" style="font-size:12.5px; color:var(--accent); text-decoration:none;">Top up your balance →</a>
            </div>
            @endif
        </div>
    </div>

    {{-- ── RIGHT COLUMN ─────────────────────────────────────────── --}}
    <div>

        {{-- Withdraw CTA --}}
        <div class="card" style="padding:20px; margin-bottom:14px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Quick actions</h4>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <a href="{{ route('user.wallet.cashout') }}" class="btn btn-primary" style="justify-content:center; font-size:13px; padding:10px;">
                    <i data-lucide="download" style="width:14px; height:14px;"></i> Withdraw funds
                </a>
                <a href="{{ route('user.wallet.topup') }}" class="btn" style="justify-content:center; font-size:13px; padding:10px;">
                    <i data-lucide="upload" style="width:14px; height:14px;"></i> Top up balance
                </a>
                <a href="{{ route('user.wallet.cashout.history') }}" class="btn" style="justify-content:center; font-size:13px; padding:10px;">
                    <i data-lucide="history" style="width:14px; height:14px;"></i> Withdrawal history
                </a>
            </div>
        </div>

        {{-- KYC notice if not verified --}}
        @php $kycStatus = $user->kyc_status ?? 0; @endphp
        @if($kycStatus == 0)
        <div class="card" style="padding:16px; margin-bottom:14px; border-color:rgba(245,158,11,0.3); background:rgba(245,158,11,0.05);">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <i data-lucide="shield-alert" style="width:16px; height:16px; color:#F59E0B; flex-shrink:0;"></i>
                <div style="font-size:13px; font-weight:600; color:#F59E0B;">KYC verification required</div>
            </div>
            <div style="font-size:12px; color:var(--fg-2); margin-bottom:12px; line-height:1.5;">Complete identity verification to withdraw your earnings.</div>
            <a href="{{ route('user.profile.kyc') }}" class="btn" style="font-size:12px; padding:7px 14px; border-color:rgba(245,158,11,0.4); color:#F59E0B; width:100%; justify-content:center;">
                Verify now →
            </a>
        </div>
        @elseif($kycStatus == 1)
        <div class="card" style="padding:14px; margin-bottom:14px; border-color:rgba(34,197,94,0.25); background:rgba(34,197,94,0.05);">
            <div style="display:flex; align-items:center; gap:8px;">
                <i data-lucide="shield-check" style="width:15px; height:15px; color:#22C55E; flex-shrink:0;"></i>
                <div style="font-size:13px; font-weight:500; color:#22C55E;">KYC verified — withdrawals enabled</div>
            </div>
        </div>
        @endif

        {{-- Balance breakdown --}}
        <div class="card" style="padding:20px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Balance breakdown</h4>
            @php
                $earnedFromWork = $stats['total_earned'];
                $total = max($stats['total_topup'] + $earnedFromWork, 1);
            @endphp
            @foreach([
                ['label' => 'From tasks', 'value' => $earnedFromWork, 'color' => 'var(--urgent)', 'pct' => round($earnedFromWork / $total * 100)],
                ['label' => 'Topped up', 'value' => $stats['total_topup'], 'color' => '#60A5FA', 'pct' => round($stats['total_topup'] / $total * 100)],
            ] as $r)
            <div style="margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:5px; color:var(--fg-2);">
                    <span>{{ $r['label'] }}</span>
                    <span class="mono" style="color:var(--fg);">{{ coinSymbol() }} {{ number_format($r['value']) }}</span>
                </div>
                <div style="height:5px; background:var(--surface-3); border-radius:3px; overflow:hidden;">
                    <div style="width:{{ $r['pct'] }}%; height:100%; background:{{ $r['color'] }}; border-radius:3px;"></div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>

<style>
@media (max-width: 900px) {
    .wallet-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 640px) {
    .wallet-stats { grid-template-columns: 1fr !important; }
}
</style>

@endsection
