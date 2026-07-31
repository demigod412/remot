@extends('user.layouts.app')
@section('title', 'Two-Factor Authentication')
@section('page-title', 'Two-Factor Auth')

@section('content')
<div class="max-w-lg space-y-4">

    {{-- Status banner --}}
    <div class="rounded-xl p-4 flex items-center gap-3
                {{ $user->two_fa_enabled
                    ? 'bg-green-500/8 border border-green-500/20'
                    : 'bg-white/3 border border-white/8' }}">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                    {{ $user->two_fa_enabled ? 'bg-green-500/15' : 'bg-white/5' }}">
            <i data-lucide="{{ $user->two_fa_enabled ? 'shield-check' : 'shield-off' }}"
               class="w-5 h-5 {{ $user->two_fa_enabled ? 'text-green-400' : 'text-white/30' }}"></i>
        </div>
        <div>
            <div class="font-semibold text-white text-sm">Two-Factor Authentication</div>
            <div class="text-xs mt-0.5 {{ $user->two_fa_enabled ? 'text-green-400' : 'text-white/40' }}">
                {{ $user->two_fa_enabled ? 'Active — your account has extra protection' : 'Not enabled — your account is less secure' }}
            </div>
        </div>
        {{-- Toggle pill indicator --}}
        <div class="ml-auto shrink-0">
            <div class="w-10 h-6 rounded-full relative {{ $user->two_fa_enabled ? 'bg-green-500' : 'bg-white/10' }}">
                <div class="absolute top-1 w-4 h-4 rounded-full bg-white shadow transition-all
                            {{ $user->two_fa_enabled ? 'right-1' : 'left-1' }}"></div>
            </div>
        </div>
    </div>

    {{-- Main card --}}
    <div class="jobstation-card p-6">
        <p class="text-sm text-white/50 leading-relaxed mb-6">
            When enabled, you'll be asked to enter a verification code sent to
            <strong class="text-white/70">{{ $user->email }}</strong>
            each time you sign in from a new device.
        </p>

        @if($user->two_fa_enabled)
        {{-- Disable form --}}
        <form method="POST" action="{{ route('user.profile.2fa.disable') }}" class="space-y-4">
            @csrf
            <div class="p-3 rounded-lg bg-red-500/5 border border-red-500/15 text-xs text-red-400/80 flex gap-2 mb-4">
                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>
                Disabling 2FA will make your account more vulnerable. Confirm with your password to proceed.
            </div>
            <div>
                <label class="block text-sm text-white/60 mb-1.5">Confirm with Password</label>
                <input type="password" name="password"
                       class="jobstation-input w-full @error('password') border-red-500/50 @enderror"
                       placeholder="Enter your current password" required>
                @error('password') <div class="text-xs text-red-400 mt-1">{{ $message }}</div> @enderror
            </div>
            <button type="submit"
                    class="py-2.5 px-6 text-sm rounded-lg border border-red-500/30 text-red-400 hover:bg-red-500/10 transition-colors font-medium">
                <i data-lucide="shield-off" class="w-4 h-4 inline mr-1.5"></i> Disable 2FA
            </button>
        </form>

        @elseif(session('2fa_pending'))
        {{-- Confirm activation with emailed code --}}
        <p class="text-sm text-white/50 leading-relaxed mb-5">
            Enter the 6-digit code sent to <strong class="text-white/70">{{ $user->email }}</strong> to activate 2FA.
        </p>
        <form method="POST" action="{{ route('user.profile.2fa.confirm') }}" class="space-y-4">
            @csrf
            <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
                   class="jobstation-input w-full text-center text-2xl tracking-widest font-bold @error('code') border-red-500/50 @enderror"
                   placeholder="000000" required autofocus autocomplete="one-time-code">
            @error('code') <div class="text-xs text-red-400 mt-1">{{ $message }}</div> @enderror
            <button type="submit" class="btn-primary w-full py-2.5 text-sm font-semibold">
                <i data-lucide="shield-check" class="w-4 h-4 inline mr-1.5"></i> Confirm & Activate 2FA
            </button>
        </form>
        <form method="POST" action="{{ route('user.profile.2fa.enable') }}" class="mt-3 text-center">
            @csrf
            <button type="submit" class="text-sm text-white/30 hover:text-primary transition-colors">Resend code</button>
        </form>

        @else
        {{-- Enable form --}}
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-3">
                <div class="flex items-start gap-3 p-3 rounded-lg bg-white/3">
                    <div class="w-6 h-6 rounded-full bg-primary/20 text-primary text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">1</div>
                    <div class="text-sm text-white/60">Click <strong class="text-white/80">Enable 2FA</strong> below.</div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg bg-white/3">
                    <div class="w-6 h-6 rounded-full bg-primary/20 text-primary text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">2</div>
                    <div class="text-sm text-white/60">A verification code is sent to <strong class="text-white/80">{{ $user->email }}</strong>.</div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg bg-white/3">
                    <div class="w-6 h-6 rounded-full bg-primary/20 text-primary text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">3</div>
                    <div class="text-sm text-white/60">Enter the code to confirm activation.</div>
                </div>
            </div>

            <form method="POST" action="{{ route('user.profile.2fa.enable') }}">
                @csrf
                <button type="submit" class="btn-primary px-6 py-2.5 text-sm">
                    <i data-lucide="shield-check" class="w-4 h-4 inline mr-1.5"></i> Enable 2FA
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Security tips --}}
    <div class="jobstation-card p-5">
        <div class="text-xs font-semibold text-white/30 uppercase tracking-widest mb-3">Security Tips</div>
        <ul class="space-y-2.5">
            @foreach([
                ['lock', 'Use a strong, unique password for this account.'],
                ['mail', 'Keep your email address up to date so you can receive codes.'],
                ['eye-off', 'Never share your verification codes with anyone.'],
                ['log-out', 'Sign out of shared or public computers after use.'],
            ] as [$icon, $tip])
            <li class="flex items-start gap-2.5 text-sm text-white/40">
                <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5 shrink-0 mt-0.5 text-white/20"></i>
                {{ $tip }}
            </li>
            @endforeach
        </ul>
    </div>

</div>
@endsection
