@extends('user.layouts.app')
@section('title', 'Confirm Payment')
@section('page-title', 'Confirm Manual Payment')

@section('content')
<div style="display:grid; grid-template-columns:1fr 320px; gap:20px;" class="tc-grid">

    {{-- LEFT: form --}}
    <div>
        <div style="margin-bottom:16px;">
            <a href="{{ route('user.wallet.topup') }}"
               style="font-size:12.5px; color:var(--accent); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-left" style="width:13px; height:13px;"></i> Back to top-up
            </a>
        </div>

        {{-- Instructions --}}
        <div class="card" style="padding:22px; margin-bottom:14px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:14px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="file-text" style="width:15px; height:15px; color:var(--accent);"></i>
                Payment instructions
            </div>
            <div style="font-size:13px; color:var(--fg-2); line-height:1.7; margin-bottom:18px;">
                {!! richBody($channel->instructions ?? 'Please send the exact amount shown and submit your transaction reference below.') !!}
            </div>

            {{-- Amount box --}}
            <div style="padding:16px; border-radius:10px; background:rgba(245,213,71,0.08); border:1px solid rgba(245,213,71,0.2); margin-bottom:12px;">
                <div style="font-size:11px; color:var(--fg-3); text-transform:uppercase; letter-spacing:.07em; margin-bottom:6px;">Amount to send</div>
                <div class="mono" style="font-size:28px; font-weight:700; color:var(--coin); letter-spacing:-1px; line-height:1;">
                    {{ $topup->pay_currency }} {{ number_format($topup->payable_amount, 2) }}
                </div>
                <div class="mono" style="font-size:11px; color:var(--fg-4); margin-top:6px;">Ref: {{ $topup->reference }}</div>
            </div>

            <div style="display:flex; gap:8px; align-items:center; font-size:12px; color:#F59E0B;">
                <i data-lucide="clock" style="width:13px; height:13px; flex-shrink:0;"></i>
                Coins are credited after admin verifies your payment — usually within 24 hours.
            </div>
        </div>

        {{-- Confirmation form --}}
        <div class="card" style="padding:22px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:16px;">Submit payment proof</div>

            <form method="POST" action="{{ route('user.wallet.topup.update', $topup->id) }}"
                  enctype="multipart/form-data">
                @csrf
                <div style="display:flex; flex-direction:column; gap:14px;">

                    <div>
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Transaction ID / Reference</label>
                        <input type="text" name="transaction_id"
                               style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 12px; color:var(--fg); font-size:13.5px; font-family:ui-monospace,monospace; outline:none;"
                               placeholder="Your bank or payment reference number">
                    </div>

                    {{-- Proof upload --}}
                    <div x-data="{ preview: null, isPdf: false, fileName: '' }">
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">
                            Payment proof <span style="font-size:10.5px; color:var(--fg-4);">(screenshot / receipt — JPG, PNG, PDF · max 4 MB)</span>
                        </label>

                        <label for="proof_upload"
                               :style="preview ? 'border-color:var(--accent);' : ''"
                               style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:22px; border:1.5px dashed var(--border); border-radius:10px; cursor:pointer; transition:border-color .15s; background:var(--surface-2); overflow:hidden; text-align:center;">
                            <template x-if="preview && !isPdf">
                                <img :src="preview" style="max-width:100%; max-height:200px; object-fit:contain; border-radius:6px; display:block;">
                            </template>
                            <template x-if="isPdf">
                                <div>
                                    <i data-lucide="file-text" style="width:28px; height:28px; color:var(--accent); display:block; margin:0 auto 6px;"></i>
                                    <div style="font-size:13px; color:var(--fg);" x-text="fileName"></div>
                                </div>
                            </template>
                            <template x-if="!preview && !isPdf">
                                <div>
                                    <i data-lucide="upload-cloud" style="width:28px; height:28px; color:var(--fg-3); display:block; margin:0 auto 6px;"></i>
                                    <div style="font-size:13px; color:var(--fg-2);">Click to attach proof of payment</div>
                                    <div style="font-size:11.5px; color:var(--fg-4); margin-top:3px;">JPG, PNG or PDF up to 4 MB</div>
                                </div>
                            </template>
                            <input type="file" id="proof_upload" name="proof_image"
                                   accept="image/jpeg,image/png,image/webp,application/pdf"
                                   style="display:none;"
                                   @change="
                                       const f = $event.target.files[0];
                                       if (!f) { preview = null; isPdf = false; fileName = ''; return; }
                                       fileName = f.name;
                                       if (f.type === 'application/pdf') { isPdf = true; preview = null; }
                                       else { isPdf = false; const r = new FileReader(); r.onload = e => preview = e.target.result; r.readAsDataURL(f); }
                                   ">
                        </label>

                        <template x-if="preview || isPdf">
                            <button type="button"
                                    @click="preview = null; isPdf = false; fileName = ''; document.getElementById('proof_upload').value = ''"
                                    style="font-size:11.5px; color:var(--fg-4); background:none; border:none; cursor:pointer; margin-top:6px; padding:0; display:inline-flex; align-items:center; gap:4px;">
                                <i data-lucide="x" style="width:12px; height:12px;"></i> Remove
                            </button>
                        </template>

                        @error('proof_image')
                        <div style="font-size:11.5px; color:#EF4444; margin-top:5px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Additional note <span style="color:var(--fg-4);">(optional)</span></label>
                        <textarea name="note" rows="3"
                                  style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 12px; color:var(--fg); font-size:13px; outline:none; resize:vertical;"
                                  placeholder="Any extra information for the reviewer…"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-size:13px; display:inline-flex; align-items:center; gap:7px; width:fit-content;">
                        <i data-lucide="send" style="width:14px; height:14px;"></i> Submit confirmation
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- RIGHT: order summary --}}
    <div>
        <div class="card" style="padding:22px; margin-bottom:14px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:16px;">Order summary</div>

            <div style="display:flex; flex-direction:column; gap:0;">
                @php
                    $expectedCoins = round($topup->payable_amount * max(1, (float) $topup->rate));
                @endphp
                @foreach([
                    ['label'=>'Channel',  'value'=> $channel->name ?? '—'],
                    ['label'=>'Amount',   'value'=> $topup->pay_currency . ' ' . number_format($topup->payable_amount, 2), 'mono'=>true],
                    ['label'=>'Reference','value'=> $topup->reference, 'mono'=>true, 'small'=>true],
                ] as $row)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; color:var(--fg-3);">{{ $row['label'] }}</span>
                    <span class="{{ ($row['mono'] ?? false) ? 'mono' : '' }}"
                          style="font-size:{{ ($row['small'] ?? false) ? '11' : '13' }}px; font-weight:500; color:var(--fg);">{{ $row['value'] }}</span>
                </div>
                @endforeach

                @if($topup->package)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:12.5px; color:var(--fg-3);">Package</span>
                    <span style="font-size:12.5px; color:var(--fg);">{{ number_format($topup->package->coins) }}{{ coinSymbol() }}
                        @if($topup->package->bonus_coins)
                        <span style="color:#22C55E;">+{{ number_format($topup->package->bonus_coins) }}{{ coinSymbol() }} bonus</span>
                        @endif
                    </span>
                </div>
                @endif
            </div>

            {{-- You'll receive --}}
            <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 0 0;">
                <span style="font-size:13px; font-weight:600; color:var(--fg);">You'll receive</span>
                <div style="display:flex; align-items:baseline; gap:3px;">
                    <span class="mono" style="font-size:24px; font-weight:700; color:var(--accent); letter-spacing:-1px; line-height:1;">{{ formatCoins($expectedCoins) }}</span>
                </div>
            </div>
        </div>

        <div class="card" style="padding:16px; background:rgba(47,84,235,0.04); border-color:rgba(47,84,235,0.2);">
            <div style="font-size:12px; font-weight:600; color:var(--fg); margin-bottom:10px;">What happens next?</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach([
                    ['step'=>'1','text'=>'Submit your transaction reference below'],
                    ['step'=>'2','text'=>'Our team verifies your payment (within 24h)'],
                    ['step'=>'3','text'=>'Coins are credited to your balance automatically'],
                ] as $s)
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <div style="width:20px; height:20px; border-radius:50%; background:rgba(47,84,235,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <span style="font-size:10px; font-weight:700; color:var(--accent);">{{ $s['step'] }}</span>
                    </div>
                    <span style="font-size:12px; color:var(--fg-3); line-height:1.5;">{{ $s['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 860px) { .tc-grid { grid-template-columns: 1fr !important; } }
</style>
@endsection
