@extends('admin.layouts.app')
@section('title', 'Notification Settings')
@section('page-title', 'Notification Settings')

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;max-width:1100px;align-items:start;">
<div class="jobstation-card" style="padding:10px;">@include('admin.settings._nav', ['active' => 'notification'])</div>
<div>
<form method="POST" action="{{ route('admin.settings.notification.update') }}">
@csrf
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- Channels --}}
    <div class="jobstation-card" style="padding:24px;">
        <div style="font-weight:600;font-size:15px;color:var(--fg);margin-bottom:16px;">Notification Channels</div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:9px;cursor:pointer;transition:background .14s;"
                   onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="email_notify" value="1"
                       {{ old('email_notify', $settings->email_notify) ? 'checked' : '' }}>
                <div>
                    <div style="font-size:13.5px;color:var(--fg);font-weight:500;">Email Notifications</div>
                    <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">Send transactional emails to users</div>
                </div>
            </label>
            <label style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:9px;cursor:pointer;transition:background .14s;"
                   onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="sms_notify" value="1"
                       {{ old('sms_notify', $settings->sms_notify) ? 'checked' : '' }}>
                <div>
                    <div style="font-size:13.5px;color:var(--fg);font-weight:500;">SMS Notifications</div>
                    <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">Send SMS messages via configured provider</div>
                </div>
            </label>
        </div>
    </div>

    {{-- SMS Configuration --}}
    <div class="jobstation-card" style="padding:24px;">
        <div style="font-weight:600;font-size:15px;color:var(--fg);margin-bottom:20px;">SMS Configuration</div>

        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Driver select --}}
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">SMS Provider</label>
                <select name="sms_config[driver]" id="sms_driver" style="width:100%;max-width:260px;" onchange="toggleSmsFields(this.value)">
                    <option value="nextsms" @selected(($settings->sms_config['driver'] ?? 'nextsms') === 'nextsms')>NextSMS</option>
                    <option value="twilio"  @selected(($settings->sms_config['driver'] ?? '') === 'twilio')>Twilio</option>
                    <option value="nexmo"   @selected(($settings->sms_config['driver'] ?? '') === 'nexmo')>Nexmo / Vonage</option>
                    <option value="log"     @selected(($settings->sms_config['driver'] ?? '') === 'log')>Log (testing)</option>
                </select>
            </div>

            {{-- NextSMS fields --}}
            <div id="fields_nextsms" class="sms-fields" style="display:none;">
                <div style="background:var(--surface-2);border-radius:10px;padding:16px 20px;margin-bottom:16px;">
                    <div style="font-size:12px;color:var(--fg-3);line-height:1.6;">
                        <strong style="color:var(--fg-2);">NextSMS (Send via Link)</strong> — Uses HTTP GET to
                        <code style="font-size:11px;color:var(--accent);">messaging-service.co.tz</code>.
                        Get your token from the NextSMS portal. Sender ID must be pre-approved.
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">API Token <span style="color:#EF4444;">*</span></label>
                        <input type="password" name="sms_config[token]"
                               value="{{ old('sms_config.token', $settings->sms_config['token'] ?? '') }}"
                               autocomplete="new-password" placeholder="d983d9d1d54176047e68547aba079ba4">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Sender ID</label>
                        <input type="text" name="sms_config[from]"
                               value="{{ old('sms_config.from', $settings->sms_config['from'] ?? '') }}"
                               placeholder="JOBSTATION" maxlength="20">
                        <div style="font-size:11px;color:var(--fg-3);margin-top:4px;">Max 11 chars alphanumeric. Must be registered with NextSMS.</div>
                    </div>
                </div>
            </div>

            {{-- Twilio fields --}}
            <div id="fields_twilio" class="sms-fields" style="display:none;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Account SID <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="sms_config[account_sid]"
                               value="{{ old('sms_config.account_sid', $settings->sms_config['account_sid'] ?? '') }}"
                               autocomplete="off">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Auth Token <span style="color:#EF4444;">*</span></label>
                        <input type="password" name="sms_config[auth_token]"
                               value="{{ old('sms_config.auth_token', $settings->sms_config['auth_token'] ?? '') }}"
                               autocomplete="new-password">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">From Number <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="sms_config[from]"
                               value="{{ old('sms_config.from', $settings->sms_config['from'] ?? '') }}"
                               placeholder="+15551234567">
                    </div>
                </div>
            </div>

            {{-- Nexmo fields --}}
            <div id="fields_nexmo" class="sms-fields" style="display:none;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">API Key <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="sms_config[api_key]"
                               value="{{ old('sms_config.api_key', $settings->sms_config['api_key'] ?? '') }}"
                               autocomplete="off">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">API Secret <span style="color:#EF4444;">*</span></label>
                        <input type="password" name="sms_config[api_secret]"
                               value="{{ old('sms_config.api_secret', $settings->sms_config['api_secret'] ?? '') }}"
                               autocomplete="new-password">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">From / Brand Name</label>
                        <input type="text" name="sms_config[from]"
                               value="{{ old('sms_config.from', $settings->sms_config['from'] ?? '') }}"
                               placeholder="Job Station">
                    </div>
                </div>
            </div>

            {{-- Log / off --}}
            <div id="fields_log" class="sms-fields" style="display:none;">
                <div style="background:var(--surface-2);border-radius:10px;padding:14px 18px;font-size:13px;color:var(--fg-3);">
                    SMS will be written to <code>storage/logs/laravel.log</code> instead of being sent. Use this for local testing.
                </div>
            </div>

        </div>
    </div>

    <div>
        <button type="submit" class="btn-primary" style="padding:9px 20px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save Notification Settings
        </button>
    </div>

</div>
</form>
</div>
</div>

<script>
function toggleSmsFields(driver) {
    document.querySelectorAll('.sms-fields').forEach(el => el.style.display = 'none');
    var el = document.getElementById('fields_' + driver);
    if (el) el.style.display = '';
}
// Init on load
toggleSmsFields(document.getElementById('sms_driver').value);
</script>

@endsection
