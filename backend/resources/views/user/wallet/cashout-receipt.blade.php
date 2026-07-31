<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt · {{ $cashout->reference }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --bg: #09090B; --surface: #111113; --surface-2: #1A1A1E; --border: rgba(255,255,255,0.08);
        --fg: #FAFAFA; --fg-2: #A1A1AA; --fg-3: #71717A; --accent: #2f54eb; --coin: #F5D547;
    }
    body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--fg); min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 32px 16px; }
    .mono { font-family: ui-monospace, monospace; }
    .receipt { width: 100%; max-width: 460px; }
    .actions { display: flex; gap: 10px; justify-content: center; margin-bottom: 24px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid var(--border); background: var(--surface-2); color: var(--fg-2); text-decoration: none; transition: .15s; font-family: 'Poppins', sans-serif; }
    .btn:hover { border-color: var(--accent); color: var(--fg); }
    .btn-primary { background: var(--accent); color: #000; border-color: var(--accent); }
    .btn-primary:hover { background: #4da85a; border-color: #4da85a; color: #000; }
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
    .header { padding: 28px 28px 20px; text-align: center; border-bottom: 1px solid var(--border); }
    .logo { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 20px; }
    .logo-icon { width: 32px; height: 32px; }
    .logo-text { font-size: 18px; font-weight: 700; letter-spacing: -0.5px; }
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 600; margin-bottom: 12px; }
    .status-0 { background: rgba(245,158,11,0.12); color: #F59E0B; border: 1px solid rgba(245,158,11,0.25); }
    .status-1 { background: rgba(34,197,94,0.1); color: #22C55E; border: 1px solid rgba(34,197,94,0.2); }
    .status-2 { background: rgba(239,68,68,0.1); color: #EF4444; border: 1px solid rgba(239,68,68,0.2); }
    .status-3 { background: rgba(96,165,250,0.1); color: #60A5FA; border: 1px solid rgba(96,165,250,0.2); }
    .amount-block { padding: 20px 28px; text-align: center; border-bottom: 1px dashed var(--border); background: linear-gradient(180deg, rgba(47,84,235,0.04) 0%, transparent 100%); }
    .rows { padding: 20px 28px; display: flex; flex-direction: column; gap: 0; }
    .row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
    .row:last-child { border-bottom: none; }
    .row-label { color: var(--fg-3); }
    .row-value { font-weight: 500; color: var(--fg); text-align: right; }
    .footer { padding: 18px 28px; border-top: 1px solid var(--border); text-align: center; font-size: 11.5px; color: var(--fg-3); line-height: 1.6; }
    .perforated { height: 0; border-top: 2px dashed var(--border); margin: 0 28px; }

    @media print {
        body { background: #fff; color: #000; padding: 0; }
        :root { --bg: #fff; --surface: #fff; --surface-2: #f5f5f5; --border: #e5e5e5; --fg: #111; --fg-2: #555; --fg-3: #888; --accent: #2f54eb; --coin: #d4af00; }
        .actions { display: none !important; }
        .card { border: 1px solid #e5e5e5; box-shadow: none; }
        .receipt { max-width: 100%; }
    }
</style>
</head>
<body>

<div class="receipt">
    {{-- Actions --}}
    <div class="actions no-print">
        <a href="{{ route('user.wallet.cashout.history') }}" class="btn">← Back</a>
        <button onclick="window.print()" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print / Save PDF
        </button>
    </div>

    <div class="card">
        {{-- Header --}}
        <div class="header">
            <div class="logo">
                <svg class="logo-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="8" fill="#2f54eb" fill-opacity="0.12"/>
                    <path d="M8 10h16M16 10v12" stroke="#2f54eb" stroke-width="2.2" stroke-linecap="round"/>
                    <circle cx="16" cy="22" r="2.2" fill="#2f54eb"/>
                </svg>
                <span class="logo-text">{{ gs()->site_name ?? 'Job Station' }}</span>
            </div>
            <div style="font-size:13px; color:var(--fg-3); margin-bottom:14px;">Withdrawal Receipt</div>
            @php
                $statusLabels = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected', 3 => 'Processing'];
                $icons = [0 => '⏳', 1 => '✅', 2 => '❌', 3 => '⚙️'];
            @endphp
            <div class="status-badge status-{{ $cashout->status }}">
                {{ $icons[$cashout->status] ?? '?' }} {{ $statusLabels[$cashout->status] ?? 'Unknown' }}
            </div>
        </div>

        {{-- Amount block --}}
        <div class="amount-block">
            <div style="font-size:11px; color:var(--fg-3); margin-bottom:6px; text-transform:uppercase; letter-spacing:.08em;">Coin Amount</div>
            <div style="display:flex; align-items:baseline; gap:4px; justify-content:center; margin-bottom:4px;">
                <span class="mono" style="font-size:13px; color:var(--coin);">{{ coinSymbol() }}</span>
                <span class="mono" style="font-size:38px; font-weight:700; letter-spacing:-2px; color:var(--coin);">{{ number_format($cashout->coin_amount) }}</span>
            </div>
            <div style="font-size:12.5px; color:var(--fg-3);">
                → <span class="mono" style="color:var(--fg); font-weight:500;">{{ $cashout->payout_currency }} {{ number_format($cashout->payout_amount, 2) }}</span>
            </div>
        </div>

        <div class="perforated"></div>

        {{-- Detail rows --}}
        <div class="rows">
            <div class="row">
                <span class="row-label">Reference</span>
                <span class="row-value mono" style="font-size:12px; color:var(--fg-2);">{{ $cashout->reference }}</span>
            </div>
            <div class="row">
                <span class="row-label">Date</span>
                <span class="row-value">{{ $cashout->created_at->format('M d, Y · H:i') }}</span>
            </div>
            <div class="row">
                <span class="row-label">Method</span>
                <span class="row-value">{{ $cashout->payoutMethod?->name ?? '—' }}</span>
            </div>
            <div class="row">
                <span class="row-label">Account</span>
                <span class="row-value mono" style="font-size:12px;">{{ $cashout->payout_details['account'] ?? '—' }}</span>
            </div>
            <div class="row">
                <span class="row-label">Coin rate</span>
                <span class="row-value mono" style="font-size:12px;">{{ coinSymbol() }}1 = {{ $cashout->payout_currency }} {{ $cashout->coin_to_currency_rate }}</span>
            </div>
            <div class="row">
                <span class="row-label">Platform fee</span>
                <span class="row-value mono" style="color:var(--fg-3);">{{ coinSymbol() }}{{ number_format($cashout->fee, 2) }}</span>
            </div>
            <div class="row" style="padding-top:14px; margin-top:4px;">
                <span class="row-label" style="font-weight:600; color:var(--fg);">You receive</span>
                <span class="row-value mono" style="font-size:15px; font-weight:700; color:var(--accent);">
                    {{ $cashout->payout_currency }} {{ number_format($cashout->payout_amount, 2) }}
                </span>
            </div>
            @if($cashout->admin_note)
            <div class="row">
                <span class="row-label">Note</span>
                <span class="row-value" style="color:var(--fg-3); font-size:12px; max-width:220px; text-align:right;">{{ $cashout->admin_note }}</span>
            </div>
            @endif
        </div>

        <div class="footer">
            Requested by <strong>{{ $cashout->user?->fullname }}</strong> ({{ '@' . $cashout->user?->username }})<br>
            Keep this receipt for your records. Processing takes 1–3 business days.
        </div>
    </div>
</div>

</body>
</html>
