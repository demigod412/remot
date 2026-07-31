@extends('admin.layouts.app')
@section('title', 'Top-up #' . $topup->reference)
@section('page-title', 'Top-up Review')

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.topups.index') }}" style="color:var(--fg-3);text-decoration:none;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Top-ups</a>
    <i data-lucide="chevron-right" style="width:12px;height:12px;"></i>
    <span class="mono" style="color:var(--fg-2);">{{ $topup->reference }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    {{-- Left: Transaction Details --}}
    <div>
        <div class="jobstation-card" style="padding:0;overflow:hidden;">
            {{-- Card header --}}
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:14px;font-weight:600;">Transaction Details</div>
                @php
                    $statusLabels = [0=>'Initiated',1=>'Completed',2=>'Pending',3=>'Failed'];
                    $statusStyles = [
                        0 => 'color:var(--fg-2);background:var(--surface-2)',
                        1 => 'color:#22C55E;background:rgba(34,197,94,0.1)',
                        2 => 'color:#F59E0B;background:rgba(245,158,11,0.1)',
                        3 => 'color:#EF4444;background:rgba(239,68,68,0.1)',
                    ];
                @endphp
                <span style="font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;{{ $statusStyles[$topup->status] ?? 'color:var(--fg-3);background:var(--surface-2)' }};">
                    {{ $topup->status_label }}
                </span>
            </div>

            {{-- Detail grid --}}
            <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <div class="label" style="margin-bottom:4px;">Reference</div>
                    <div class="mono" style="font-size:13px;color:var(--fg-2);">{{ $topup->reference }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Channel</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $topup->channel?->name ?? $topup->channel_code }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Amount Paid</div>
                    <div style="font-size:14px;font-weight:600;color:var(--fg);">{{ number_format($topup->payable_amount, 2) }} {{ $topup->pay_currency }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Coins to Credit</div>
                    <div class="mono" style="font-size:20px;font-weight:700;color:var(--coin);letter-spacing:-0.5px;">{{ formatCoins($topup->coins_credited ?: $topup->amount) }}</div>
                </div>
                @if($topup->charge > 0)
                <div>
                    <div class="label" style="margin-bottom:4px;">Charge / Fee</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ number_format($topup->charge, 2) }} {{ $topup->pay_currency }}</div>
                </div>
                @endif
                @if($topup->rate)
                <div>
                    <div class="label" style="margin-bottom:4px;">Exchange Rate</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $topup->rate }}</div>
                </div>
                @endif
                @if($topup->package)
                <div>
                    <div class="label" style="margin-bottom:4px;">Package</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $topup->package->name }} ({{ $topup->package->total_coins }} coins)</div>
                </div>
                @endif
                <div>
                    <div class="label" style="margin-bottom:4px;">Submitted</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $topup->created_at->format('M d, Y H:i') }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Via API</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $topup->via_api ? 'Yes' : 'No' }}</div>
                </div>
                @if($topup->transaction_id)
                <div style="grid-column:1/-1;">
                    <div class="label" style="margin-bottom:4px;">Sender Transaction ID</div>
                    <div class="mono" style="font-size:13px;color:var(--fg-2);">{{ $topup->transaction_id }}</div>
                </div>
                @endif
            </div>

            @if($topup->admin_note)
            <div style="padding:0 20px 20px;">
                <div style="padding:12px 14px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);">
                    <div class="label" style="margin-bottom:4px;">Admin Note</div>
                    <div style="font-size:13px;color:var(--fg-2);">{{ $topup->admin_note }}</div>
                </div>
            </div>
            @endif

            @if($topup->proof_image)
            <div style="padding:0 20px 20px;">
                <div class="label" style="margin-bottom:8px;">Payment Proof</div>
                @php $proofExt = strtolower(pathinfo($topup->proof_image, PATHINFO_EXTENSION)); @endphp
                @if(in_array($proofExt, ['jpg','jpeg','png','webp']))
                <a href="{{ route('secure.topupProof', $topup->id) }}" target="_blank"
                   style="display:block; border-radius:10px; overflow:hidden; border:1px solid var(--border); max-width:100%;">
                    <img src="{{ route('secure.topupProof', $topup->id) }}"
                         alt="Payment proof"
                         style="width:100%; max-height:280px; object-fit:contain; display:block; background:var(--surface-2);">
                </a>
                @else
                <a href="{{ route('secure.topupProof', $topup->id) }}" target="_blank"
                   style="display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); color:var(--fg); text-decoration:none; font-size:13px;">
                    <i data-lucide="file-text" style="width:15px; height:15px; color:var(--accent);"></i>
                    View proof document
                </a>
                @endif
            </div>
            @endif

            @if($topup->gateway_response)
            <div style="padding:0 20px 20px;">
                <div class="label" style="margin-bottom:6px;">Gateway Response</div>
                <pre style="font-size:11px;color:var(--fg-3);background:var(--surface-2);border-radius:8px;padding:12px;overflow:auto;max-height:160px;margin:0;font-family:ui-monospace,monospace;">{{ json_encode($topup->gateway_response, JSON_PRETTY_PRINT) }}</pre>
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
                $ini = strtoupper(substr($topup->user?->username ?? '?', 0, 1));
                $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
                $clr = $clrs[ord($ini) % count($clrs)];
            @endphp
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="width:36px;height:36px;border-radius:50%;background:{{ $clr }};display:flex;align-items:center;justify-content:center;color:white;font-size:13px;font-weight:600;flex-shrink:0;">{{ $ini }}</div>
                <div style="flex:1;min-width:0;">
                    <a href="{{ route('admin.users.show', $topup->user_id) }}"
                       style="font-size:13px;font-weight:500;color:var(--fg);text-decoration:none;"
                       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg)'">
                        {{ $topup->user?->fullname ?? 'Unknown' }}
                    </a>
                    <div style="font-size:11px;color:var(--fg-3);">{{ $topup->user?->email }}</div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--border);font-size:12px;color:var(--fg-3);">
                <span>Current Balance</span>
                <span class="coin-badge">{{ formatCoins($topup->user?->coin_balance ?? 0) }}</span>
            </div>
        </div>

        {{-- Review Actions --}}
        @if($topup->status == 2)
        <div class="jobstation-card" style="padding:16px;" x-data="{ rejectOpen: false }">
            <div style="font-size:13px;font-weight:600;margin-bottom:14px;">Review Actions</div>

            <form method="POST" action="{{ route('admin.topups.approve', $topup->id) }}" style="margin-bottom:12px;">
                @csrf
                <div style="margin-bottom:10px;">
                    <div class="label" style="margin-bottom:5px;">Coins to Credit <span style="color:#EF4444;">*</span></div>
                    <input type="number" name="coins_credited"
                           value="{{ old('coins_credited', round($topup->payable_amount * max(1, $topup->rate))) }}"
                           min="0.01" step="0.01" style="width:100%;font-size:13px;box-sizing:border-box;" required>
                </div>
                <div style="margin-bottom:12px;">
                    <div class="label" style="margin-bottom:5px;">Admin Note</div>
                    <input type="text" name="admin_note" placeholder="Optional note…" style="width:100%;font-size:13px;box-sizing:border-box;">
                </div>
                <button type="submit"
                        onclick="return confirm('Approve and credit coins?')"
                        style="width:100%;padding:10px;border-radius:8px;background:#22C55E;border:none;color:white;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <i data-lucide="check" style="width:14px;height:14px;"></i> Approve & Credit
                </button>
            </form>

            <div style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--fg-4);margin-bottom:12px;">
                <div style="flex:1;height:1px;background:var(--border);"></div>or<div style="flex:1;height:1px;background:var(--border);"></div>
            </div>

            <button @click="rejectOpen = !rejectOpen" class="btn btn-danger" style="width:100%;justify-content:center;">
                <i data-lucide="x" style="width:13px;height:13px;"></i> Reject
            </button>
            <div x-show="rejectOpen" x-cloak x-transition style="margin-top:10px;">
                <form method="POST" action="{{ route('admin.topups.reject', $topup->id) }}">
                    @csrf
                    <textarea name="admin_note" rows="2" placeholder="Reason for rejection…"
                              style="width:100%;font-size:12.5px;resize:none;margin-bottom:8px;box-sizing:border-box;" required></textarea>
                    <button type="submit"
                            style="width:100%;padding:9px;border-radius:8px;background:#EF4444;border:none;color:white;font-size:13px;font-weight:500;cursor:pointer;">
                        Confirm Rejection
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>

@endsection
