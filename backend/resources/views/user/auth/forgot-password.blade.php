@extends('user.layouts.auth')
@section('title', 'Forgot Password')
@section('heading', 'Forgot your password?')
@section('subheading', 'Enter your email and we\'ll send you a 6-digit code to reset it.')

@section('top-right')
<div style="font-size:13px;color:var(--fg-3);">
    <a href="{{ route('user.login') }}" style="color:#2f54eb;font-weight:500;text-decoration:none;">← Back to sign in</a>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('user.forgot-password.send') }}">
    @csrf

    @if($errors->any())
    <div class="auth-flash-error" style="margin-bottom:18px;">{{ $errors->first() }}</div>
    @endif

    <div class="auth-field">
        <label class="auth-label">Email address</label>
        <input type="email" name="email" value="{{ old('email') }}"
               class="auth-input {{ $errors->has('email') ? 'error' : '' }}"
               placeholder="you@company.com" required autofocus autocomplete="email">
        @error('email')<div class="auth-error">{{ $message }}</div>@enderror
    </div>

    {!! recaptchaWidget() !!}
    @error('captcha')<div class="auth-error" style="margin-bottom:12px;">{{ $message }}</div>@enderror

    <button type="submit" class="auth-btn-primary" style="margin-bottom:24px;">
        Send reset link
        <i data-lucide="send" style="width:14px;height:14px;"></i>
    </button>
</form>

@if(session('success'))
<div style="padding:16px;border:1px solid var(--border);border-radius:12px;background:var(--surface-2);">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <i data-lucide="check-circle" style="width:15px;height:15px;color:#22C55E;flex-shrink:0;"></i>
        <span style="font-size:13px;font-weight:500;color:var(--fg-2);">Reset link sent!</span>
    </div>
    <div style="font-size:12px;color:var(--fg-3);line-height:1.5;margin-bottom:10px;">
        Check your inbox at <span style="color:var(--fg-2);">{{ old('email') }}</span>. The email arrives within 30 seconds.
    </div>
    <div style="display:flex;gap:8px;font-size:12px;">
        <a href="{{ route('user.forgot-password') }}" style="color:#2f54eb;text-decoration:none;">Resend</a>
        <span style="color:var(--fg-4);">·</span>
        <a href="{{ route('user.login') }}" style="color:var(--fg-3);text-decoration:none;">Back to sign in</a>
    </div>
</div>
@else
<div style="font-size:11.5px;color:var(--fg-4);line-height:1.55;">
    If you signed in with Google or Facebook, use that option on the sign-in page instead of resetting.
</div>
@endif
@endsection
