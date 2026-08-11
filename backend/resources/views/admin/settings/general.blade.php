@extends('admin.layouts.app')
@section('title', 'General Settings')
@section('page-title', 'Settings')

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:24px;max-width:1100px;align-items:start;">

    {{-- Left Nav --}}
    <div class="jobstation-card" style="padding:10px;">
        @include('admin.settings._nav', ['active' => 'general'])
    </div>

    {{-- Right Content --}}
    <div>
        <form method="POST" action="{{ route('admin.settings.general.update') }}">
        @csrf @method('POST')

        {{-- Platform --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;">Platform</h3>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:22px;">Global controls. Changes apply immediately.</div>

            @foreach([
                ['site_name',           'Platform name',        $settings->site_name,                       'Shown in emails, receipts, and public pages.'],
                ['coin_name',           'Coin name',            $settings->coin_name ?? 'Job Station Coins',      'Internal coin currency name.'],
                ['coin_symbol',         'Coin symbol',          $settings->coin_symbol ?? 'JC',             'Short identifier displayed next to amounts.'],
                ['cur_text',            'Fiat currency',        $settings->cur_text ?? 'US Dollar',         'Display name for fiat currency.'],
                ['cur_sym',             'Currency symbol',      $settings->cur_sym ?? '$',                  'Symbol shown before fiat amounts.'],
                ['ref_commission',      'Referral commission',  $settings->ref_commission ?? '5',           'Percentage paid on referral sign-up.'],
                ['contract_commission', 'Contract commission',  $settings->contract_commission ?? '5',      'Deducted from worker payout on contract completion.'],
            ] as [$name, $label, $value, $hint])
            <div style="display:grid;grid-template-columns:200px 1fr 36px;gap:16px;align-items:center;padding:14px 0;border-top:1px solid var(--border);">
                <div>
                    <div style="font-size:13px;font-weight:500;">{{ $label }}</div>
                    <div style="font-size:11px;color:var(--fg-3);margin-top:2px;">{{ $hint }}</div>
                </div>
                <input type="text" name="{{ $name }}" value="{{ old($name, $value) }}"
                       style="font-size:13px;font-family:ui-monospace,monospace;" required>
                <button type="submit" class="btn btn-sm btn-ghost"
                        style="padding:6px;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="save" style="width:12px;height:12px;"></i>
                </button>
            </div>
            @endforeach
        </div>

        {{-- Cashout --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;">Cashout / Withdrawal</h3>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:22px;">Minimum coins a user must hold before they can request a cashout.</div>
            <div style="display:grid;grid-template-columns:1fr auto;gap:14px;align-items:end;">
                <div>
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Minimum cashout (coins)</label>
                    <input type="number" name="min_cashout" value="{{ old('min_cashout', $settings->min_cashout ?? 50) }}"
                           min="1" step="1" placeholder="e.g. 50"
                           style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
                    {{-- Withdrawals are USD now, not coins. The old label described the
                         coin balance, which is no longer what this gates. --}}
                    <div style="font-size:11px;color:var(--fg-4);margin-top:4px;">Smallest withdrawal a worker may request, in USD. Currently: <strong>{{ formatUsd($settings->min_cashout ?? 50) }}</strong></div>
                </div>

                <div style="grid-column:1/-1;border-top:1px solid var(--border);padding-top:16px;margin-top:6px;">
                    <label style="display:flex;align-items:flex-start;gap:9px;cursor:pointer;margin-bottom:14px;">
                        <input type="checkbox" name="withdrawal_window_enabled" value="1"
                               {{ old('withdrawal_window_enabled', $settings->withdrawal_window_enabled ?? false) ? 'checked' : '' }}
                               style="margin:3px 0 0;flex-shrink:0;">
                        <span>
                            <span style="font-size:12.5px;color:var(--fg);font-weight:500;">Restrict withdrawals to a monthly window</span>
                            <span style="display:block;font-size:11px;color:var(--fg-3);line-height:1.55;margin-top:3px;">
                                Requests can only be created between the two days below. Approving and
                                paying existing requests is unaffected. Off means any day.
                            </span>
                        </span>
                    </label>

                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                        <div>
                            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Window opens (day)</label>
                            <input type="number" name="withdrawal_window_start" min="1" max="31"
                                   value="{{ old('withdrawal_window_start', $settings->withdrawal_window_start ?? 15) }}"
                                   style="width:110px;font-family:ui-monospace,monospace;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Window closes (day)</label>
                            <input type="number" name="withdrawal_window_end" min="1" max="31"
                                   value="{{ old('withdrawal_window_end', $settings->withdrawal_window_end ?? 28) }}"
                                   style="width:110px;font-family:ui-monospace,monospace;">
                            <div style="font-size:11px;color:var(--fg-4);margin-top:4px;">
                                28 rather than 31, so the window exists in February too.
                            </div>
                        </div>
                    </div>

                    {{-- The two task clocks. They shared one column until now, so setting
                         the review window also set how long a worker had to do the task. --}}
                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
                        <div>
                            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Review window (hours)</label>
                            <input type="number" name="default_review_hours" min="1" max="720"
                                   value="{{ old('default_review_hours', $settings->default_review_hours ?? 48) }}"
                                   style="width:130px;font-family:ui-monospace,monospace;">
                            <div style="font-size:11px;color:var(--fg-4);margin-top:4px;line-height:1.5;">
                                After a worker submits, how long you have before it is<br>auto-approved and paid.
                            </div>
                        </div>
                        <div>
                            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Task deadline (hours)</label>
                            <input type="number" name="abandon_after_hours" min="1" max="720"
                                   value="{{ old('abandon_after_hours', $settings->abandon_after_hours ?? 120) }}"
                                   style="width:130px;font-family:ui-monospace,monospace;">
                            <div style="font-size:11px;color:var(--fg-4);margin-top:4px;line-height:1.5;">
                                After you approve an application, how long the worker<br>has before the slot is released. 120 = 5 days.
                            </div>
                        </div>
                    </div>

                    <label style="display:flex;align-items:flex-start;gap:9px;cursor:pointer;">
                        <input type="checkbox" name="one_withdrawal_per_month" value="1"
                               {{ old('one_withdrawal_per_month', $settings->one_withdrawal_per_month ?? false) ? 'checked' : '' }}
                               style="margin:3px 0 0;flex-shrink:0;">
                        <span>
                            <span style="font-size:12.5px;color:var(--fg);font-weight:500;">One approved withdrawal per calendar month</span>
                            <span style="display:block;font-size:11px;color:var(--fg-3);line-height:1.55;margin-top:3px;">
                                Counts approved requests only, so one you have not reviewed yet does not
                                block the worker, and one you rejected does not punish them.
                            </span>
                        </span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">Save</button>
            </div>
        </div>

        {{-- Coin Rate Widget --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;">Coin rate widget</h3>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:22px;">Displays a live rate ticker in the public navbar. e.g. JC1 = TZS 500.</div>

            <div style="display:grid;grid-template-columns:1fr 160px auto;gap:14px;align-items:end;padding-bottom:20px;border-bottom:1px solid var(--border);margin-bottom:20px;">
                <div>
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Exchange rate <span style="color:var(--fg-4);">(1 {{ gs()->coin_symbol ?? 'JC' }} = ?)</span></label>
                    <input type="number" name="coin_rate" value="{{ old('coin_rate', $settings->coin_rate) }}"
                           min="0" step="0.0001" placeholder="e.g. 500"
                           style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
                </div>
                <div>
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Currency label</label>
                    <input type="text" name="coin_rate_currency" value="{{ old('coin_rate_currency', $settings->coin_rate_currency ?? $settings->cur_text) }}"
                           placeholder="e.g. TZS" style="font-size:13px;font-family:ui-monospace,monospace;">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">Save rate</button>
            </div>

            @php $rateOn = old('show_coin_rate', $settings->show_coin_rate ?? false); @endphp
            <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                <div style="position:relative;width:36px;height:20px;flex-shrink:0;">
                    <input type="hidden" name="show_coin_rate" value="0">
                    <input type="checkbox" name="show_coin_rate" value="1" {{ $rateOn ? 'checked' : '' }}
                           onchange="this.closest('form').submit()"
                           style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;z-index:1;margin:0;">
                    <div style="width:36px;height:20px;border-radius:10px;background:{{ $rateOn ? 'var(--accent)' : 'var(--surface-3)' }};transition:.2s;"></div>
                    <div style="position:absolute;top:2px;left:{{ $rateOn ? '18px' : '2px' }};width:16px;height:16px;border-radius:50%;background:white;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></div>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:500;">Show rate in navbar</div>
                    <div style="font-size:11.5px;color:var(--fg-3);">Display the coin rate ticker on public pages</div>
                </div>
            </label>
        </div>

        {{-- Currencies --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:16px;"
             x-data="{
                currencies: {{ json_encode($settings->currencies ?? [['code'=>'USD','name'=>'US Dollar','symbol'=>'$','rate'=>1],['code'=>'TZS','name'=>'Tanzanian Shilling','symbol'=>'TZS','rate'=>2500]]) }},
                add() { this.currencies.push({code:'',name:'',symbol:'',rate:0}) },
                remove(i) { this.currencies.splice(i,1) }
             }">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <h3 style="font-size:15px;font-weight:600;margin:0;">Currencies</h3>
                <button type="button" @click="add()" class="btn btn-sm" style="font-size:12px;">+ Add row</button>
            </div>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:18px;">Define currencies users can select to view their coin balance in fiat. Set the default that appears first.</div>

            {{-- Header --}}
            <div style="display:grid;grid-template-columns:90px 1fr 80px 120px 32px;gap:8px;align-items:center;margin-bottom:8px;font-size:11px;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">
                <span>Code</span><span>Name</span><span>Symbol</span><span>Rate (per coin)</span><span></span>
            </div>

            <template x-for="(c, i) in currencies" :key="i">
                <div style="display:grid;grid-template-columns:90px 1fr 80px 120px 32px;gap:8px;align-items:center;margin-bottom:8px;">
                    <input type="text" name="currency_code[]" :value="c.code" @input="c.code=$event.target.value.toUpperCase()" placeholder="TZS" style="font-family:ui-monospace,monospace;font-size:12.5px;">
                    <input type="text" name="currency_name[]" :value="c.name" @input="c.name=$event.target.value" placeholder="Tanzanian Shilling" style="font-size:12.5px;">
                    <input type="text" name="currency_symbol[]" :value="c.symbol" @input="c.symbol=$event.target.value" placeholder="TZS" style="font-family:ui-monospace,monospace;font-size:12.5px;">
                    <input type="number" name="currency_rate[]" :value="c.rate" @input="c.rate=+$event.target.value" min="0" step="0.0001" placeholder="e.g. 2500" style="font-family:ui-monospace,monospace;font-size:12.5px;">
                    <button type="button" @click="remove(i)" style="width:32px;height:32px;border-radius:6px;background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.2);color:#EF4444;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </template>

            <div style="display:flex;align-items:center;gap:14px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
                <div style="flex:1;">
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Default currency (shown first to users)</label>
                    <input type="text" name="default_currency" value="{{ old('default_currency', $settings->default_currency ?? 'TZS') }}" placeholder="e.g. TZS" style="font-family:ui-monospace,monospace;font-size:13px;width:120px;">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;align-self:flex-end;">Save currencies</button>
            </div>
        </div>

        {{-- Boost Pricing --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;">Boost / Featured pricing</h3>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:22px;">Coins charged per day. Held on request, activated after approval.</div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
                @foreach([
                    ['boost_cost_work', 'Work boost (coins/day)', $settings->boost_cost_work ?? 50],
                    ['boost_days_work', 'Work boost max (days)',  $settings->boost_days_work ?? 30],
                    ['boost_cost_job',  'Job boost (coins/day)',  $settings->boost_cost_job  ?? 100],
                    ['boost_days_job',  'Job boost max (days)',   $settings->boost_days_job  ?? 30],
                ] as [$fname, $flabel, $fval])
                <div>
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">{{ $flabel }}</label>
                    <input type="number" name="{{ $fname }}" value="{{ old($fname, $fval) }}"
                           min="0" step="1" style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
                </div>
                @endforeach
            </div>
        </div>

        {{-- Feature Flags --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 22px;">Feature flags</h3>
            @foreach([
                ['registration',       'User registration',    'Allow new user sign-ups'],
                ['allow_dark_mode',    'Dark mode for users',  'Allow users to switch between light and dark mode'],
                ['email_verification', 'Email verification',   'Require email confirmation on signup'],
                ['phone_verification', 'Phone verification',   'Require OTP on signup'],
                ['kyc_required',       'KYC required',         'Users must verify KYC before cashout'],
                ['secure_password',    'Strong password',      'Enforce password complexity rules'],
                ['agree',              'Terms agreement',      'Show terms checkbox on register'],
                ['force_ssl',          'Force HTTPS',          'Redirect all traffic to HTTPS'],
                ['maintenance_mode',   'Maintenance mode',     'Block public access to the site'],
            ] as [$fname, $flabel, $fhint])
            @php $on = old($fname, $settings->{$fname} ?? false); @endphp
            <div style="display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid var(--border);">
                <div style="flex:1;">
                    <div style="font-size:13px;font-weight:500;">{{ $flabel }}</div>
                    <div style="font-size:11.5px;color:var(--fg-3);">{{ $fhint }}</div>
                </div>
                <label style="position:relative;width:36px;height:20px;flex-shrink:0;cursor:pointer;">
                    <input type="checkbox" name="{{ $fname }}" value="1" {{ $on ? 'checked' : '' }}
                           style="position:absolute;opacity:0;width:0;height:0;"
                           onchange="var t=this.nextElementSibling;t.style.background=this.checked?'var(--accent)':'var(--surface-3)';t.querySelector('div').style.transform=this.checked?'translateX(16px)':'none';">
                    <span style="display:block;width:36px;height:20px;border-radius:10px;background:{{ $on ? 'var(--accent)' : 'var(--surface-3)' }};padding:2px;transition:background .18s;">
                        <div style="width:16px;height:16px;border-radius:8px;background:white;transform:{{ $on ? 'translateX(16px)' : 'none' }};transition:transform .18s;"></div>
                    </span>
                </label>
            </div>
            @endforeach
        </div>

        {{-- Cookie Banner --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;">Cookie Banner</h3>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:22px;">Shown to new visitors at the bottom of every public page. Users can accept or reject.</div>

            @php $cookieOn = old('cookie_enabled', $settings->cookie_enabled ?? true); @endphp
            <div style="display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid var(--border);margin-bottom:16px;">
                <div style="flex:1;">
                    <div style="font-size:13px;font-weight:500;">Enable cookie banner</div>
                    <div style="font-size:11.5px;color:var(--fg-3);">Show the cookie consent notice to first-time visitors</div>
                </div>
                <label style="position:relative;width:36px;height:20px;flex-shrink:0;cursor:pointer;">
                    <input type="checkbox" name="cookie_enabled" value="1" {{ $cookieOn ? 'checked' : '' }}
                           style="position:absolute;opacity:0;width:0;height:0;"
                           onchange="var t=this.nextElementSibling;t.style.background=this.checked?'var(--accent)':'var(--surface-3)';t.querySelector('div').style.transform=this.checked?'translateX(16px)':'none';">
                    <span style="display:block;width:36px;height:20px;border-radius:10px;background:{{ $cookieOn ? 'var(--accent)' : 'var(--surface-3)' }};padding:2px;transition:background .18s;">
                        <div style="width:16px;height:16px;border-radius:8px;background:white;transform:{{ $cookieOn ? 'translateX(16px)' : 'none' }};transition:transform .18s;"></div>
                    </span>
                </label>
            </div>

            <div>
                <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:6px;">Cookie banner message <span style="color:var(--fg-4);">(leave blank for default)</span></label>
                <textarea name="cookie_text" rows="3" placeholder="We use cookies to improve your experience and analyse site usage."
                          style="width:100%;font-size:13px;resize:vertical;padding:10px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--fg);font-family:inherit;line-height:1.6;">{{ old('cookie_text', $settings->cookie_text) }}</textarea>
                <div style="font-size:11px;color:var(--fg-4);margin-top:4px;">This text appears next to the Accept / Reject buttons.</div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save settings
        </button>

        </form>
    </div>

</div>

@endsection
