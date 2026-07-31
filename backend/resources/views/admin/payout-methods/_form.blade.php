{{-- Shared form partial for create/edit payout method --}}
<div style="display:flex;flex-direction:column;gap:16px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="payout-form-2col">
        <div>
            <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Method Name <span style="color:#EF4444;">*</span></label>
            <input type="text" name="name" value="{{ old('name', $method->name ?? '') }}"
                   placeholder="e.g. M-Pesa, Bank Transfer"
                   @error('name') style="border-color:#EF4444;" @enderror required>
            @error('name') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
        </div>
        <div>
            <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Payout Currency <span style="color:#EF4444;">*</span></label>
            <input type="text" name="currency" value="{{ old('currency', $method->currency ?? 'USD') }}"
                   placeholder="USD" style="font-family:ui-monospace,monospace;"
                   @error('currency') style="border-color:#EF4444;" @enderror required>
            @error('currency') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="payout-form-2col">
        <div>
            <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Min Coins <span style="color:#EF4444;">*</span></label>
            <input type="number" name="min_coins" value="{{ old('min_coins', $method->min_coins ?? '') }}"
                   min="0" step="1" placeholder="100"
                   @error('min_coins') style="border-color:#EF4444;" @enderror required>
            @error('min_coins') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
        </div>
        <div>
            <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Max Coins <span style="color:#EF4444;">*</span></label>
            <input type="number" name="max_coins" value="{{ old('max_coins', $method->max_coins ?? '') }}"
                   min="0" step="1" placeholder="10000"
                   @error('max_coins') style="border-color:#EF4444;" @enderror required>
            @error('max_coins') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
        </div>
    </div>

    <div>
        <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">
            Coin → Currency Rate <span style="color:#EF4444;">*</span>
            <span style="color:var(--fg-3);font-size:11px;font-weight:400;margin-left:4px;">How much 1 {{ coinSymbol() }} is worth in the payout currency</span>
        </label>
        <input type="number" name="coin_to_currency_rate"
               value="{{ old('coin_to_currency_rate', $method->coin_to_currency_rate ?? '') }}"
               min="0" step="0.000001" placeholder="0.01"
               style="font-family:ui-monospace,monospace;"
               @error('coin_to_currency_rate') style="border-color:#EF4444;" @enderror required>
        @error('coin_to_currency_rate') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="payout-form-2col">
        <div>
            <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Percent Fee (%)</label>
            <input type="number" name="percent_fee" value="{{ old('percent_fee', $method->percent_fee ?? 0) }}"
                   min="0" max="100" step="0.01" placeholder="0.00">
            <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Applied on coin amount before payout</div>
        </div>
        <div>
            <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Fixed Fee ({{ coinSymbol() }})</label>
            <input type="number" name="fixed_fee" value="{{ old('fixed_fee', $method->fixed_fee ?? 0) }}"
                   min="0" step="1" placeholder="0">
            <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Flat coin fee deducted per cashout</div>
        </div>
    </div>

    <div>
        <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Description / Instructions</label>
        <textarea name="description" rows="3" placeholder="Instructions shown to users when selecting this method…"
                  style="resize:vertical;width:100%;font-size:13px;">{{ old('description', $method->description ?? '') }}</textarea>
    </div>
</div>

<style>
@media (max-width: 600px) {
    .payout-form-2col { grid-template-columns: 1fr !important; }
}
</style>
