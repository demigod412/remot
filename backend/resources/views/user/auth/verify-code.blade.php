@extends('user.layouts.auth')
@section('title', 'Verify Code')

@section('content')
<div class="text-center mb-6">
    <div class="w-12 h-12 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="mail-check" class="w-5 h-5 text-primary"></i>
    </div>
    <h1 style="font-size:20px;font-weight:700;color:var(--fg);">Check your email</h1>
    <p style="font-size:14px;color:var(--fg-3);margin-top:4px;">
        We sent a 6-digit code to
        <span style="color:var(--fg-2);">{{ session('reset_email') }}</span>
    </p>
</div>

<form method="POST" action="{{ route('user.verify-code.submit') }}" class="space-y-4">
    @csrf
    <div>
        <label style="display:block;font-size:14px;color:var(--fg-2);margin-bottom:6px;">Verification Code</label>
        <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
               class="jobstation-input w-full text-center text-2xl tracking-widest font-bold @error('code') border-red-500/50 @enderror"
               placeholder="000000" required autofocus autocomplete="one-time-code">
        @error('code') <div class="text-xs text-red-400 mt-1 text-left">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="btn-primary w-full py-2.5 text-sm font-semibold">
        Verify Code
    </button>
</form>

<div style="margin-top:16px;text-align:center;font-size:14px;color:var(--fg-3);">
    Didn't receive it?
    <a href="{{ route('user.forgot-password') }}" style="color:#2f54eb;text-decoration:none;">Resend code</a>
</div>
@endsection

@section('footer-links')
<p style="text-align:center;font-size:14px;color:var(--fg-3);margin-top:20px;">
    <a href="{{ route('user.login') }}" style="color:#2f54eb;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
        <i data-lucide="arrow-left" style="width:12px;height:12px;"></i> Back to Sign In
    </a>
</p>
@endsection
