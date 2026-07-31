@extends('admin.layouts.app')
@section('title', 'Configure ' . $channel->name)
@section('page-title', 'Configure Payment Channel')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.payment-channels.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Payment Channels</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">{{ $channel->name }}</span>
</div>

<div style="max-width:700px;">
<form method="POST" action="{{ route('admin.payment-channels.update', $channel->id) }}">
@csrf @method('PUT')
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- Info Card --}}
    <div class="jobstation-card" style="padding:20px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="padding:10px;border-radius:12px;background:rgba(47,84,235,0.1);">
                <i data-lucide="{{ $channel->is_crypto ? 'bitcoin' : 'credit-card' }}" style="width:22px;height:22px;color:var(--accent);display:block;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:15px;font-weight:600;color:var(--fg);">{{ $channel->name }}</div>
                <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;font-family:ui-monospace,monospace;">
                    Code: {{ $channel->code }} · Driver: {{ $channel->driver }}
                    @if($channel->is_manual) · Manual @endif
                    @if($channel->is_crypto) · Crypto @endif
                </div>
            </div>
            @if($channel->status)
            <span style="display:inline-flex;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:500;background:rgba(34,197,94,0.12);color:#22C55E;">Active</span>
            @else
            <span style="display:inline-flex;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:500;background:var(--surface-2);color:var(--fg-3);">Inactive</span>
            @endif
        </div>
    </div>

    {{-- Credentials --}}
    @if(!$channel->is_manual && $channel->credentials)
    <div class="jobstation-card" style="padding:24px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 16px;">API Credentials</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            @php $creds = $channel->credentials ?? [] @endphp
            @foreach($creds as $key => $value)
            <div style="display:flex;align-items:center;gap:10px;">
                <input type="hidden" name="credential_keys[]" value="{{ $key }}">
                <div style="width:160px;flex-shrink:0;">
                    <div style="padding:8px 12px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;font-size:13px;color:var(--fg-3);">{{ $key }}</div>
                </div>
                <input type="text" name="credential_values[]"
                       value="{{ $value }}"
                       placeholder="Enter value…"
                       style="flex:1;font-family:ui-monospace,monospace;font-size:12.5px;"
                       autocomplete="off">
            </div>
            @endforeach
        </div>
        @php
            // Map the gateway driver to its IPN/webhook slug (see routes/ipn.php).
            $ipnSlugs = [
                'stripe' => 'StripeV3', 'paypalsdk' => 'PaypalSdk', 'paypal' => 'Paypal',
                'razorpay' => 'Razorpay', 'paystack' => 'Paystack', 'flutterwave' => 'Flutterwave',
                'paytm' => 'Paytm', 'coinpayments' => 'Coinpayments', 'coinbasecommerce' => 'CoinbaseCommerce',
                'instamojo' => 'Instamojo', 'perfectmoney' => 'PerfectMoney', 'voguepay' => 'Voguepay', 'payin' => 'PayIn',
            ];
            $drv = strtolower($channel->driver ?? '');
            $ipnSlug = null;
            foreach ($ipnSlugs as $k => $v) { if (str_contains($drv, $k)) { $ipnSlug = $v; break; } }
            $webhookUrl = $ipnSlug ? url('/ipn/' . $ipnSlug) : null;
        @endphp
        @if($webhookUrl)
        <div style="margin-top:16px;padding:14px;border-radius:8px;background:rgba(96,165,250,0.07);border:1px solid rgba(96,165,250,0.2);">
            <div style="font-size:11.5px;color:#60A5FA;font-weight:600;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                <i data-lucide="webhook" style="width:14px;height:14px;"></i> Webhook / IPN URL
            </div>
            <div style="font-size:12px;color:var(--fg-3);margin-bottom:8px;">
                Add this URL as a webhook endpoint in your {{ $channel->name }} dashboard so payments confirm automatically.
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="text" readonly value="{{ $webhookUrl }}" onclick="this.select()"
                       style="flex:1;font-family:ui-monospace,monospace;font-size:12px;background:var(--surface-2);">
                <button type="button" class="btn" style="padding:8px 12px;font-size:12px;white-space:nowrap;"
                        onclick="navigator.clipboard.writeText('{{ $webhookUrl }}');this.innerText='Copied';">Copy</button>
            </div>
            @if($ipnSlug === 'StripeV3')
            <div style="font-size:11.5px;color:var(--fg-4);margin-top:8px;">After adding the webhook in Stripe, paste its <b>Signing secret</b> into the <code>webhook_secret</code> field above.</div>
            @endif
        </div>
        @endif

        @if($channel->webhook_info)
        <div style="margin-top:16px;padding:12px 14px;border-radius:8px;background:rgba(96,165,250,0.07);border:1px solid rgba(96,165,250,0.2);">
            <div style="font-size:11.5px;color:#60A5FA;font-weight:600;margin-bottom:8px;">Webhook Configuration</div>
            @foreach($channel->webhook_info as $label => $val)
            <div style="font-size:12px;color:var(--fg-2);margin-bottom:4px;">
                <span style="color:var(--fg-3);">{{ $label }}:</span>
                <span style="font-family:ui-monospace,monospace;margin-left:6px;">{{ $val }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- Instructions (for manual) --}}
    @if($channel->is_manual)
    <div class="jobstation-card" style="padding:24px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 6px;">Payment Instructions</h3>
        <div style="font-size:12px;color:var(--fg-3);margin-bottom:12px;">Shown to users when they select this payment method. Include account details, reference format, etc.</div>
        <textarea name="instructions" rows="6"
                  placeholder="e.g. Send payment to account number 1234-5678. Use your username as the reference."
                  style="resize:vertical;width:100%;font-size:13px;">{{ old('instructions', $channel->instructions) }}</textarea>
        @error('instructions') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
    </div>
    @endif

    <div style="display:flex;gap:10px;align-items:center;">
        <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save Configuration
        </button>
        <a href="{{ route('admin.payment-channels.index') }}" class="btn" style="padding:9px 18px;">Cancel</a>
        <form method="POST" action="{{ route('admin.payment-channels.toggle', $channel->id) }}" style="margin-left:auto;">
            @csrf
            <button type="submit" class="btn" style="padding:9px 18px;color:{{ $channel->status ? '#EF4444' : '#22C55E' }};">
                <i data-lucide="{{ $channel->status ? 'eye-off' : 'eye' }}" style="width:14px;height:14px;"></i>
                {{ $channel->status ? 'Disable' : 'Enable' }}
            </button>
        </form>
    </div>

</div>
</form>
</div>

@endsection
