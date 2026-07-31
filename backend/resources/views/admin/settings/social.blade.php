@extends('admin.layouts.app')
@section('title', 'Social Login Settings')
@section('page-title', 'Social Login Settings')

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;max-width:1100px;align-items:start;">
<div class="jobstation-card" style="padding:10px;">@include('admin.settings._nav', ['active' => 'social'])</div>
<div>
<form method="POST" action="{{ route('admin.settings.social.update') }}">
@csrf
<div class="jobstation-card" style="padding:24px;">
    <div style="font-weight:600;font-size:15px;color:var(--fg);margin-bottom:6px;">Google OAuth</div>
    <p style="font-size:12.5px;color:var(--fg-3);margin-bottom:20px;line-height:1.6;">
        Create credentials at <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color:var(--accent);">console.cloud.google.com</a>.
        Set the Authorized redirect URI to: <code style="background:var(--surface-2);padding:2px 6px;border-radius:4px;font-size:11.5px;">{{ route('user.social.callback', 'google') }}</code>
    </p>

    <div style="display:flex;flex-direction:column;gap:16px;">
        <div>
            <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Client ID</label>
            <input type="text" name="google_client_id"
                   value="{{ old('google_client_id', $settings->socialite_credentials['google']['client_id'] ?? '') }}"
                   placeholder="xxxx.apps.googleusercontent.com"
                   style="width:100%;font-size:13px;">
            @error('google_client_id') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
        </div>
        <div>
            <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Client Secret</label>
            <input type="password" name="google_client_secret"
                   value="{{ old('google_client_secret', $settings->socialite_credentials['google']['client_secret'] ?? '') }}"
                   placeholder="GOCSPX-…"
                   style="width:100%;font-size:13px;"
                   autocomplete="new-password">
            @error('google_client_secret') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        <div style="padding:12px 14px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);font-size:12.5px;color:var(--fg-3);line-height:1.6;">
            <strong style="color:var(--fg-2);">After saving:</strong> The "Continue with Google" button will appear on the login and register pages automatically.
            Leave both fields empty to disable Google login.
        </div>
    </div>

    <div style="margin-top:22px;padding-top:20px;border-top:1px solid var(--border);">
        <button type="submit" class="btn btn-primary" style="padding:9px 24px;">Save Google Settings</button>
    </div>
</div>
</form>
</div>
</div>

@endsection
