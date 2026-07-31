@extends('user.layouts.app')
@section('title', 'Withdrawal Accounts')
@section('page-title', 'Withdrawal Accounts')

@section('content')
<div style="display:grid; grid-template-columns:1fr 360px; gap:20px;" class="pa-grid">

    {{-- LEFT: saved accounts list --}}
    <div>
        <div style="font-size:13px; color:var(--fg-3); margin-bottom:16px;">
            Save your payout account details so you don't have to re-enter them every time you withdraw.
        </div>

        @if($accounts->isEmpty())
        <div class="card" style="padding:50px 24px; text-align:center; margin-bottom:14px;">
            <div style="font-size:28px; margin-bottom:10px;">🏦</div>
            <div style="font-size:14px; font-weight:500; color:var(--fg); margin-bottom:6px;">No saved accounts yet</div>
            <p style="font-size:13px; color:var(--fg-3); margin:0;">Add a withdrawal account using the form on the right.</p>
        </div>
        @else
        <div style="display:flex; flex-direction:column; gap:10px;">
            @foreach($accounts as $account)
            <div class="card" style="padding:16px 18px;">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <div style="width:38px; height:38px; border-radius:10px; background:rgba(47,84,235,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i data-lucide="credit-card" style="width:17px; height:17px; color:var(--accent);"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                            <div style="font-size:13.5px; font-weight:500; color:var(--fg);">
                                {{ $account->label ?: ($account->payoutMethod->name ?? '—') }}
                            </div>
                            @if($account->is_default)
                            <span style="font-size:10px; padding:2px 7px; border-radius:999px; background:rgba(47,84,235,0.12); color:var(--accent); border:1px solid rgba(47,84,235,0.2); font-weight:600;">DEFAULT</span>
                            @endif
                        </div>
                        <div style="font-size:12px; color:var(--fg-3); margin-bottom:8px;">
                            {{ $account->payoutMethod->name ?? '—' }}
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px;">
                            @foreach($account->details as $key => $val)
                            <span class="mono" style="font-size:11.5px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-2);">
                                {{ ucfirst(str_replace('_',' ',$key)) }}: {{ $val }}
                            </span>
                            @endforeach
                        </div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            @if(! $account->is_default)
                            <form method="POST" action="{{ route('user.wallet.payout-accounts.default', $account->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn" style="font-size:11.5px; padding:5px 12px;">Set as default</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('user.wallet.payout-accounts.delete', $account->id) }}" style="display:inline;"
                                  onsubmit="return confirm('Remove this account?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn" style="font-size:11.5px; padding:5px 12px; color:#EF4444; border-color:rgba(239,68,68,0.25);">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- RIGHT: add new account form --}}
    <div x-data="{ selectedMethodId: '{{ old('payout_method_id', '') }}', fields: [] }"
         x-init="selectedMethodId && loadFields(selectedMethodId)">

        @if($methods->isEmpty())
        <div class="card" style="padding:40px 24px; text-align:center; margin-bottom:12px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(47,84,235,0.08); border:1px solid rgba(47,84,235,0.18); display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
                <i data-lucide="banknote" style="width:22px; height:22px; color:var(--accent);"></i>
            </div>
            <div style="font-size:13.5px; font-weight:600; color:var(--fg); margin-bottom:6px;">No withdrawal methods configured</div>
            <p style="font-size:12px; color:var(--fg-3); margin:0; line-height:1.6;">The platform hasn't set up any payout methods yet. Please check back later or contact support.</p>
        </div>
        @else
        <div class="card" style="padding:22px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:16px;">Add new account</div>

            <form method="POST" action="{{ route('user.wallet.payout-accounts.store') }}">
                @csrf
                <div style="display:flex; flex-direction:column; gap:14px;">

                    <div>
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Withdrawal method <span style="color:#EF4444;">*</span></label>
                        <select name="payout_method_id" required
                                @change="selectedMethodId = $event.target.value"
                                style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 10px; color:var(--fg); font-size:13px; outline:none;">
                            <option value="">Select method…</option>
                            @foreach($methods as $m)
                            <option value="{{ $m->id }}" {{ old('payout_method_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                        @error('payout_method_id')
                        <div style="font-size:11.5px; color:#EF4444; margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Label <span style="font-size:10.5px; color:var(--fg-4);">(optional)</span></label>
                        <input type="text" name="label" value="{{ old('label') }}" maxlength="60"
                               style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none;"
                               placeholder="e.g. My M-Pesa, Business Account">
                    </div>

                    {{-- Account number / address --}}
                    <div>
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Account number / address <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="details[account]" value="{{ old('details.account') }}" required
                               style="width:100%; background:var(--surface-2); border:1px solid {{ $errors->has('details.account') ? '#EF4444' : 'var(--border)' }}; border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none;"
                               placeholder="Account number, phone, or wallet address">
                        @error('details.account')
                        <div style="font-size:11.5px; color:#EF4444; margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Account name</label>
                        <input type="text" name="details[name]" value="{{ old('details.name', auth()->user()->fullname) }}"
                               style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none;"
                               placeholder="Name on the account">
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding:10px; justify-content:center; font-size:13px; width:100%; display:flex; align-items:center; gap:7px;">
                        <i data-lucide="plus-circle" style="width:14px; height:14px;"></i> Save account
                    </button>
                </div>
            </form>
        </div>
        @endif

        <div class="card" style="padding:18px; margin-top:12px;">
            <div style="font-size:12.5px; font-weight:600; color:var(--fg); margin-bottom:10px;">How saved accounts work</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach([
                    ['icon'=>'zap','text'=>'Saved accounts appear as quick-select options when you withdraw'],
                    ['icon'=>'shield','text'=>'Your details are stored securely and only used for payouts'],
                    ['icon'=>'star','text'=>'Set one account as default for fastest withdrawals'],
                ] as $tip)
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <i data-lucide="{{ $tip['icon'] }}" style="width:13px; height:13px; color:var(--fg-3); margin-top:1.5px; flex-shrink:0;"></i>
                    <span style="font-size:12px; color:var(--fg-3); line-height:1.55;">{{ $tip['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 860px) { .pa-grid { grid-template-columns: 1fr !important; } }
</style>
@endsection
