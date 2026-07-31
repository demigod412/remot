@extends('user.layouts.auth')
@section('title', 'Create Account')
@section('heading', 'Create your account.')
@section('subheading', 'Join thousands earning with instant tasks, or post jobs and find talent fast.')

@section('top-right')
<div style="font-size:13px;color:var(--fg-3);">
    Already have one?
    <a href="{{ route('user.login') }}" style="color:#2f54eb;font-weight:500;text-decoration:none;">Sign in →</a>
</div>
@endsection

@section('content')
@php $gs = gs(); @endphp

{{-- Social login --}}
@if(($gs->socialite_credentials['google']['client_id'] ?? null) || ($gs->socialite_credentials['facebook']['client_id'] ?? null))
<div style="display:grid;grid-template-columns:repeat({{ ($gs->socialite_credentials['google']['client_id'] ?? null) && ($gs->socialite_credentials['facebook']['client_id'] ?? null) ? 2 : 1 }},1fr);gap:8px;margin-bottom:4px;">
    @if($gs->socialite_credentials['google']['client_id'] ?? null)
    <a href="{{ route('user.social.redirect', 'google') }}" class="auth-social-btn">
        <svg width="15" height="15" viewBox="0 0 24 24"><path fill="#EA4335" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Google
    </a>
    @endif
    @if($gs->socialite_credentials['facebook']['client_id'] ?? null)
    <a href="{{ route('user.social.redirect', 'facebook') }}" class="auth-social-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        Facebook
    </a>
    @endif
</div>
<div class="auth-divider">or with email</div>
@endif

<form method="POST" action="{{ route('user.register.submit') }}">
    @csrf
    <input type="hidden" name="account_type" value="{{ old('account_type', '1') }}">
    @if(request('ref'))
    <input type="hidden" name="ref" value="{{ request('ref') }}">
    @endif

    @if($errors->any())
    <div class="auth-flash-error" style="margin-bottom:18px;">{{ $errors->first() }}</div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="auth-field">
            <label class="auth-label">First name</label>
            <input type="text" name="firstname" value="{{ old('firstname') }}"
                   class="auth-input {{ $errors->has('firstname') ? 'error' : '' }}"
                   placeholder="Priya" required autocomplete="given-name">
            @error('firstname')<div class="auth-error">{{ $message }}</div>@enderror
        </div>
        <div class="auth-field">
            <label class="auth-label">Last name</label>
            <input type="text" name="lastname" value="{{ old('lastname') }}"
                   class="auth-input {{ $errors->has('lastname') ? 'error' : '' }}"
                   placeholder="Nair" required autocomplete="family-name">
            @error('lastname')<div class="auth-error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="auth-field">
        <label class="auth-label">Username</label>
        <input type="text" name="username" value="{{ old('username') }}"
               class="auth-input {{ $errors->has('username') ? 'error' : '' }}"
               placeholder="@handle" required autocomplete="username">
        @error('username')<div class="auth-error">{{ $message }}</div>@enderror
    </div>

    <div class="auth-field">
        <label class="auth-label">Email address</label>
        <input type="email" name="email" value="{{ old('email') }}"
               class="auth-input {{ $errors->has('email') ? 'error' : '' }}"
               placeholder="you@example.com" required autocomplete="email">
        @error('email')<div class="auth-error">{{ $message }}</div>@enderror
    </div>

    <div class="auth-field" x-data="{ show: false, strength: 0 }">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <label class="auth-label" style="margin:0;">Password</label>
            <span style="font-size:11.5px;color:var(--fg-4);">min 6 chars</span>
        </div>
        <div style="position:relative;">
            <input :type="show ? 'text' : 'password'" name="password"
                   @input="strength = $el.value.length > 12 ? 4 : $el.value.length > 8 ? 3 : $el.value.length > 5 ? 2 : $el.value.length > 0 ? 1 : 0"
                   class="auth-input {{ $errors->has('password') ? 'error' : '' }}"
                   placeholder="Create a strong password" required autocomplete="new-password"
                   style="padding-right:44px;">
            <button type="button" @click="show = !show"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;padding:4px;">
                <svg x-show="!show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg x-show="show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
        <div style="display:flex;gap:4px;margin-top:8px;">
            <template x-for="i in 4">
                <div :style="`flex:1;height:3px;border-radius:3px;background:${i <= strength ? (strength <= 2 ? '#F59E0B' : '#2f54eb') : 'var(--border)'}`"></div>
            </template>
        </div>
        @error('password')<div class="auth-error">{{ $message }}</div>@enderror
    </div>

    @if($gs->agree ?? false)
    <label style="display:flex;align-items:flex-start;gap:10px;font-size:12.5px;color:var(--fg-3);margin-bottom:22px;cursor:pointer;line-height:1.5;">
        <input type="checkbox" name="agree" style="width:15px;height:15px;accent-color:#2f54eb;cursor:pointer;margin-top:2px;flex-shrink:0;" required>
        <span>I agree to {{ gs()->site_name ?? 'Job Station' }}'s
            <a href="{{ url('/terms') }}" style="color:#2f54eb;text-decoration:none;">Terms of Service</a>
            and <a href="{{ url('/privacy') }}" style="color:#2f54eb;text-decoration:none;">Privacy Policy</a>. I am at least 18.
        </span>
    </label>
    @endif

    {!! recaptchaWidget() !!}
    @error('captcha')<div class="auth-error" style="margin-bottom:12px;">{{ $message }}</div>@enderror

    <button type="submit" class="auth-btn-primary">
        Create account
        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
    </button>
</form>
@endsection

@section('footer-links')
<div style="text-align:center;margin-top:24px;font-size:13px;color:var(--fg-3);">
    Already have an account?
    <a href="{{ route('user.login') }}" style="color:#2f54eb;font-weight:500;text-decoration:none;">Sign in</a>
</div>
@endsection
