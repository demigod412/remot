@extends('admin.layouts.app')
@section('title', 'Cashout #' . $cashout->reference)
@section('page-title', 'Cashout Review')

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.cashouts.index') }}" style="color:var(--fg-3);text-decoration:none;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Cashouts</a>
    <i data-lucide="chevron-right" style="width:12px;height:12px;"></i>
    <span class="mono" style="color:var(--fg-2);">{{ $cashout->reference }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    {{-- Left: Cashout Details --}}
    <div>
        <div class="jobstation-card" style="padding:0;overflow:hidden;">
            {{-- Card header --}}
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:14px;font-weight:600;">Cashout Details</div>
                @php
                    $statusStyles = [
                        0 => 'color:#F59E0B;background:rgba(245,158,11,0.1)',
                        1 => 'color:#22C55E;background:rgba(34,197,94,0.1)',
                        2 => 'color:#EF4444;background:rgba(239,68,68,0.1)',
                    ];
                @endphp
                <span style="font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;{{ $statusStyles[$cashout->status] ?? 'color:var(--fg-3);background:var(--surface-2)' }};">
                    {{ $cashout->status_label }}
                </span>
            </div>

            {{-- Detail grid --}}
            <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <div class="label" style="margin-bottom:4px;">Reference</div>
                    <div class="mono" style="font-size:13px;color:var(--fg-2);">{{ $cashout->reference }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Payout Method</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $cashout->payoutMethod?->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Coins Requested</div>
                    <div class="mono" style="font-size:20px;font-weight:700;color:var(--coin);letter-spacing:-0.5px;">{{ formatCoins($cashout->coin_amount) }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Payout Amount</div>
                    <div style="font-size:20px;font-weight:700;color:#22C55E;letter-spacing:-0.5px;">{{ number_format($cashout->payout_amount, 2) }} {{ $cashout->payout_currency }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Fee Deducted</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ formatCoins($cashout->fee) }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Net Coins Deducted</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ formatCoins($cashout->net_coins_deducted) }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Rate</div>
                    <div style="font-size:13px;color:var(--fg-2);">1 coin = {{ $cashout->coin_to_currency_rate }} {{ $cashout->payout_currency }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Submitted</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $cashout->created_at->format('M d, Y H:i') }}</div>
                </div>
            </div>

            {{-- Payout Account Details --}}
            @if($cashout->payout_details && count($cashout->payout_details))
            <div style="padding:0 20px 20px;">
                <div style="padding-top:16px;border-top:1px solid var(--border);">
                    <div class="label" style="margin-bottom:10px;letter-spacing:0.05em;">Payout Account Details</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        @foreach($cashout->payout_details as $key => $value)
                        <div style="padding:10px 12px;border-radius:8px;background:var(--surface-2);">
                            <div style="font-size:11px;color:var(--fg-3);margin-bottom:2px;">{{ ucwords(str_replace('_',' ',$key)) }}</div>
                            <div style="font-size:13px;color:var(--fg);font-weight:500;word-break:break-all;">{{ $value }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($cashout->admin_note)
            <div style="padding:0 20px 20px;">
                <div style="padding:12px 14px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);">
                    <div class="label" style="margin-bottom:4px;">Admin Note</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $cashout->admin_note }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Right: User + Actions --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- User card --}}
        <div class="jobstation-card" style="padding:16px;">
            <div class="label" style="margin-bottom:10px;">User</div>
            @php
                $ini = strtoupper(substr($cashout->user?->username ?? '?', 0, 1));
                $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
                $clr = $clrs[ord($ini) % count($clrs)];
            @endphp
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="width:36px;height:36px;border-radius:50%;background:{{ $clr }};display:flex;align-items:center;justify-content:center;color:white;font-size:13px;font-weight:600;flex-shrink:0;">{{ $ini }}</div>
                <div style="flex:1;min-width:0;">
                    <a href="{{ route('admin.users.show', $cashout->user_id) }}"
                       style="font-size:13px;font-weight:500;color:var(--fg);text-decoration:none;"
                       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg)'">
                        {{ $cashout->user?->fullname ?? 'Unknown' }}
                    </a>
                    <div style="font-size:11px;color:var(--fg-3);">{{ $cashout->user?->email }}</div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--border);font-size:12px;color:var(--fg-3);">
                <span>Current Balance</span>
                <span class="coin-badge">{{ formatCoins($cashout->user?->coin_balance ?? 0) }}</span>
            </div>
        </div>

        {{-- Review Actions --}}
        @if($cashout->status == 0)
        <div class="jobstation-card" style="padding:16px;" x-data="{ rejectOpen: false }">
            <div style="font-size:13px;font-weight:600;margin-bottom:14px;">Review Actions</div>

            <form method="POST" action="{{ route('admin.cashouts.approve', $cashout->id) }}" style="margin-bottom:12px;">
                @csrf
                <div style="margin-bottom:12px;">
                    <div class="label" style="margin-bottom:5px;">Admin Note (optional)</div>
                    <input type="text" name="admin_note" placeholder="e.g. Paid via bank transfer"
                           style="width:100%;font-size:13px;box-sizing:border-box;">
                </div>
                <button type="submit"
                        onclick="return confirm('Confirm cashout approval? Ensure payment is sent before approving.')"
                        style="width:100%;padding:10px;border-radius:8px;background:#22C55E;border:none;color:white;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <i data-lucide="check" style="width:14px;height:14px;"></i> Mark as Paid / Approve
                </button>
            </form>

            <div style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--fg-4);margin-bottom:12px;">
                <div style="flex:1;height:1px;background:var(--border);"></div>or<div style="flex:1;height:1px;background:var(--border);"></div>
            </div>

            <button @click="rejectOpen = !rejectOpen" class="btn btn-danger" style="width:100%;justify-content:center;">
                <i data-lucide="x" style="width:13px;height:13px;"></i> Reject & Refund
            </button>
            <div x-show="rejectOpen" x-cloak x-transition style="margin-top:10px;">
                <form method="POST" action="{{ route('admin.cashouts.reject', $cashout->id) }}">
                    @csrf
                    <textarea name="admin_note" rows="2" placeholder="Reason for rejection…"
                              style="width:100%;font-size:12.5px;resize:none;margin-bottom:8px;box-sizing:border-box;" required></textarea>
                    <button type="submit"
                            style="width:100%;padding:9px;border-radius:8px;background:#EF4444;border:none;color:white;font-size:13px;font-weight:500;cursor:pointer;">
                        Reject & Refund Coins
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>

@endsection
