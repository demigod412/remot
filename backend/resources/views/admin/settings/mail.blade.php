@extends('admin.layouts.app')
@section('title', 'Mail Settings')
@section('page-title', 'Mail Settings')

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;max-width:1100px;align-items:start;">
<div class="jobstation-card" style="padding:10px;">@include('admin.settings._nav', ['active' => 'mail'])</div>
<div>
<form method="POST" action="{{ route('admin.settings.mail.update') }}">
@csrf
<div class="jobstation-card" style="padding:24px;">
    <div style="font-weight:600;font-size:15px;color:var(--fg);margin-bottom:20px;">SMTP / Mail Configuration</div>

    <div style="display:flex;flex-direction:column;gap:16px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">From Email <span style="color:#EF4444;">*</span></label>
                <input type="email" name="email_from" value="{{ old('email_from', $settings->email_from) }}"
                       placeholder="noreply@example.com" required>
            </div>
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">From Name</label>
                <input type="text" name="mail_config[from_name]"
                       value="{{ old('mail_config.from_name', $settings->mail_config['from_name'] ?? $settings->site_name ?? '') }}"
                       placeholder="Job Station">
            </div>
        </div>

        <div>
            <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Mail Driver</label>
            <select name="mail_config[driver]" style="width:100%;max-width:260px;">
                @foreach(['smtp','mailgun','sendgrid','log'] as $driver)
                <option value="{{ $driver }}" @selected(($settings->mail_config['driver'] ?? 'smtp') === $driver)>
                    {{ strtoupper($driver) }}
                </option>
                @endforeach
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">SMTP Host</label>
                <input type="text" name="mail_config[host]" value="{{ old('mail_config.host', $settings->mail_config['host'] ?? '') }}"
                       placeholder="smtp.gmail.com">
            </div>
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Port</label>
                <input type="number" name="mail_config[port]" value="{{ old('mail_config.port', $settings->mail_config['port'] ?? 587) }}"
                       placeholder="587">
            </div>
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Username</label>
                <input type="text" name="mail_config[username]" value="{{ old('mail_config.username', $settings->mail_config['username'] ?? '') }}"
                       autocomplete="off">
            </div>
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Password</label>
                <input type="password" name="mail_config[password]" value="{{ old('mail_config.password', $settings->mail_config['password'] ?? '') }}"
                       autocomplete="new-password">
            </div>
            <div>
                <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Encryption</label>
                <select name="mail_config[encryption]">
                    <option value="tls" @selected(($settings->mail_config['encryption'] ?? 'tls') === 'tls')>TLS</option>
                    <option value="ssl" @selected(($settings->mail_config['encryption'] ?? '') === 'ssl')>SSL</option>
                    <option value=""   @selected(($settings->mail_config['encryption'] ?? '') === '')>None</option>
                </select>
            </div>
        </div>

        <div style="padding-top:4px;">
            <button type="submit" class="btn-primary" style="padding:9px 20px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Save Mail Settings
            </button>
        </div>
    </div>
</div>
</form>

{{-- Separate form, deliberately. Nesting it inside the settings form would submit
     the whole configuration on a test, and a test needs to run against what is
     ALREADY SAVED, not against unsaved fields. Save first, then test. --}}
<div class="jobstation-card" style="padding:20px;margin-top:16px;">
    <div style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:6px;">Send a test email</div>
    <p style="font-size:12.5px;color:var(--fg-3);line-height:1.6;margin:0 0 14px;">
        Uses the settings above as currently saved. Worth doing before approving anyone:
        a member's temporary password is hashed the moment the account is created, so it
        exists in exactly one place &mdash; that email. If it never arrives, the account
        cannot be logged into until you reset the password by hand.
    </p>

    <form method="POST" action="{{ route('admin.settings.mail.test') }}"
          style="display:flex;gap:9px;flex-wrap:wrap;align-items:flex-start;"
          x-data="{ sending: false }" @submit="sending = true">
        @csrf
        <div style="flex:1;min-width:240px;">
            <input type="email" name="test_email" required
                   value="{{ old('test_email', auth('admin')->user()->email ?? '') }}"
                   placeholder="you@example.com"
                   style="width:100%;font-size:13px;">
            @error('test_email')
                <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn" x-bind:disabled="sending"
                x-bind:class="sending ? 'is-busy' : ''"
                style="padding:9px 18px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
            <i data-lucide="send" style="width:14px;height:14px;"></i>
            <span x-show="!sending">Send test</span>
            <span x-show="sending" x-cloak>Sending&hellip;</span>
        </button>
    </form>

    <p style="font-size:11.5px;color:var(--fg-3);margin:12px 0 0;line-height:1.55;">
        Leaving the mail fields blank is fine &mdash; the app then uses MAIL_* from
        <code>.env</code>. Filling only some of them is what breaks it: a blank host
        saved here overrides a working one in .env.
    </p>
</div>
</div>
</div>

@endsection
