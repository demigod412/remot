@extends('user.layouts.auth')
@section('title', 'Set New Password')
@section('heading', 'Set a new password.')
@section('subheading', 'Choose a strong password for your account. You\'ll be signed in automatically.')

@section('top-right')
<div style="font-size:13px;color:var(--fg-3);">
    <a href="{{ route('user.login') }}" style="color:#2f54eb;font-weight:500;text-decoration:none;">← Back to sign in</a>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('user.reset-password.submit') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

    @if($errors->any())
    <div class="auth-flash-error" style="margin-bottom:18px;">{{ $errors->first() }}</div>
    @endif

    <div class="auth-field" x-data="{ show: false, strength: 0 }">
        <label class="auth-label">New password</label>
        <div style="position:relative;">
            <input :type="show ? 'text' : 'password'" name="password"
                   @input="strength = $el.value.length > 12 ? 4 : $el.value.length > 8 ? 3 : $el.value.length > 5 ? 2 : $el.value.length > 0 ? 1 : 0"
                   class="auth-input {{ $errors->has('password') ? 'error' : '' }}"
                   placeholder="Create a strong password" required autocomplete="new-password"
                   style="padding-right:44px;">
            <button type="button" @click="show = !show"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;padding:0;">
                <template x-if="!show"><i data-lucide="eye" style="width:15px;height:15px;"></i></template>
                <template x-if="show"><i data-lucide="eye-off" style="width:15px;height:15px;"></i></template>
            </button>
        </div>
        <div style="display:flex;gap:4px;margin-top:8px;">
            <template x-for="i in 4">
                <div :style="`flex:1;height:3px;border-radius:3px;background:${i <= strength ? (strength <= 2 ? '#F59E0B' : '#2f54eb') : 'var(--border)'}`"></div>
            </template>
        </div>
        @error('password')<div class="auth-error">{{ $message }}</div>@enderror
    </div>

    <div class="auth-field" x-data="{ show: false }">
        <label class="auth-label">Confirm new password</label>
        <div style="position:relative;">
            <input :type="show ? 'text' : 'password'" name="password_confirmation"
                   class="auth-input"
                   placeholder="Repeat your password" required autocomplete="new-password"
                   style="padding-right:44px;">
            <button type="button" @click="show = !show"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;padding:0;">
                <template x-if="!show"><i data-lucide="eye" style="width:15px;height:15px;"></i></template>
                <template x-if="show"><i data-lucide="eye-off" style="width:15px;height:15px;"></i></template>
            </button>
        </div>
    </div>

    <button type="submit" class="auth-btn-primary" style="margin-top:8px;">
        Set new password
        <i data-lucide="check" style="width:14px;height:14px;"></i>
    </button>
</form>
@endsection
