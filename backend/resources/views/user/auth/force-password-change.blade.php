@extends('user.layouts.app')

@section('title', __('Change your password'))

@section('content')
<div style="max-width:460px; margin:60px auto; padding:0 20px;">
    <h1 style="font-size:26px; font-weight:600; margin:0 0 8px;">{{ __('Set a new password') }}</h1>
    <p style="font-size:14px; color:var(--muted); margin:0 0 26px; line-height:1.6;">
        {{ __('Your account was created with a temporary password. Choose your own before continuing.') }}
    </p>

    @if ($errors->any())
        <div style="padding:12px 14px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:20px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('user.password.change.submit') }}">
        @csrf
        <div style="margin-bottom:14px;">
            <label for="current_password" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Temporary password') }}</label>
            <input id="current_password" type="password" name="current_password" required
                   style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
        </div>
        <div style="margin-bottom:14px;">
            <label for="password" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('New password') }}</label>
            <input id="password" type="password" name="password" required
                   style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
            <small style="color:var(--muted);">{{ __('At least 8 characters, with letters and numbers.') }}</small>
        </div>
        <div style="margin-bottom:20px;">
            <label for="password_confirmation" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Confirm new password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
        </div>
        <button type="submit"
                style="width:100%; padding:12px; border:0; border-radius:8px; background:var(--accent); color:#fff; font-weight:600; cursor:pointer;">
            {{ __('Save password') }}
        </button>
    </form>
</div>
@endsection
