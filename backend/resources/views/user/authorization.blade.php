@extends('user.layouts.auth')
@section('title', 'Authorization')

@section('content')

@if($step === 'email')
{{-- ===== EMAIL VERIFICATION ===== --}}
<div class="text-center mb-6">
    <div class="w-12 h-12 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="mail" class="w-5 h-5 text-primary"></i>
    </div>
    <h1 class="text-xl font-bold text-white">Verify your email</h1>
    <p class="text-sm text-white/40 mt-1">
        Enter the 6-digit code sent to
        <span class="text-white/60">{{ $user->email }}</span>
    </p>
</div>

<form method="POST" action="{{ route('user.authorization.verify-email') }}" class="space-y-4">
    @csrf
    <div>
        <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
               class="jobstation-input w-full text-center text-2xl tracking-widest font-bold @error('code') border-red-500/50 @enderror"
               placeholder="000000" required autofocus autocomplete="one-time-code">
        @error('code') <div class="text-xs text-red-400 mt-1 text-left">{{ $message }}</div> @enderror
    </div>
    <button type="submit" class="btn-primary w-full py-2.5 text-sm font-semibold">
        Verify Email
    </button>
</form>

<form method="POST" action="{{ route('user.authorization.send-code') }}" class="mt-3">
    @csrf
    <input type="hidden" name="type" value="email">
    <div class="text-center text-sm text-white/40">
        Didn't receive it?
        <button type="submit" class="text-primary hover:underline">Resend code</button>
    </div>
</form>

@elseif($step === 'phone')
{{-- ===== PHONE VERIFICATION ===== --}}
<div class="text-center mb-6">
    <div class="w-12 h-12 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="smartphone" class="w-5 h-5 text-primary"></i>
    </div>
    <h1 class="text-xl font-bold text-white">Verify your phone</h1>
    <p class="text-sm text-white/40 mt-1">
        Enter the 6-digit code sent to your phone
    </p>
</div>

<form method="POST" action="{{ route('user.authorization.verify-phone') }}" class="space-y-4">
    @csrf
    <div>
        <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
               class="jobstation-input w-full text-center text-2xl tracking-widest font-bold @error('code') border-red-500/50 @enderror"
               placeholder="000000" required autofocus autocomplete="one-time-code">
        @error('code') <div class="text-xs text-red-400 mt-1 text-left">{{ $message }}</div> @enderror
    </div>
    <button type="submit" class="btn-primary w-full py-2.5 text-sm font-semibold">
        Verify Phone
    </button>
</form>

<form method="POST" action="{{ route('user.authorization.send-code') }}" class="mt-3">
    @csrf
    <input type="hidden" name="type" value="phone">
    <div class="text-center text-sm text-white/40">
        Didn't receive it?
        <button type="submit" class="text-primary hover:underline">Resend SMS</button>
    </div>
</form>

@elseif($step === '2fa')
{{-- ===== 2FA VERIFICATION ===== --}}
<div class="text-center mb-6">
    <div class="w-12 h-12 rounded-full bg-amber-500/10 border border-amber-500/30 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="shield-check" class="w-5 h-5 text-amber-400"></i>
    </div>
    <h1 class="text-xl font-bold text-white">Two-Factor Authentication</h1>
    <p class="text-sm text-white/40 mt-1">Enter the 6-digit code sent to <span class="text-white/60">{{ $user->email }}</span></p>
</div>

<form method="POST" action="{{ route('user.authorization.verify-2fa') }}" class="space-y-4">
    @csrf
    <div>
        <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
               class="jobstation-input w-full text-center text-2xl tracking-widest font-bold @error('code') border-red-500/50 @enderror"
               placeholder="000000" required autofocus autocomplete="one-time-code">
        @error('code') <div class="text-xs text-red-400 mt-1 text-left">{{ $message }}</div> @enderror
    </div>
    <button type="submit" class="btn-primary w-full py-2.5 text-sm font-semibold">
        Verify
    </button>
</form>

<form method="POST" action="{{ route('user.authorization.send-code') }}" class="mt-3">
    @csrf
    <input type="hidden" name="type" value="email">
    <div class="text-center text-sm text-white/40">
        <button type="submit" class="text-primary hover:underline">Send code via email</button>
    </div>
</form>

@endif

<div class="mt-5 pt-4 border-t border-white/5 text-center">
    <form method="POST" action="{{ route('user.logout') }}">
        @csrf
        <button type="submit" class="text-xs text-white/30 hover:text-red-400 transition-colors">
            Sign out and use a different account
        </button>
    </form>
</div>

@endsection
