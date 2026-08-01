@extends('web.layouts.app')

@section('title', __('Application status') . ' — ' . (gs()->site_name ?? config('app.name')))

@section('content')
<div style="max-width:600px; margin:0 auto; padding:48px 24px;">

    <h1 style="font-size:30px; font-weight:600; margin:0 0 8px; color:var(--text);">{{ __('Application status') }}</h1>
    <p style="font-size:15px; color:var(--muted); margin:0 0 28px;">
        {{ __('Enter the reference code from your confirmation along with the email you applied with.') }}
    </p>

    @if (session('success'))
        <div style="padding:14px 16px; border-radius:8px; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; margin-bottom:24px;">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('membership.status') }}" style="margin-bottom:28px;">
        @csrf
        <div style="margin-bottom:14px;">
            <label for="reference_code" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Reference code') }}</label>
            <input id="reference_code" type="text" name="reference_code" value="{{ old('reference_code') }}" required
                   placeholder="MA-202607-XXXXXX"
                   style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
        </div>
        <div style="margin-bottom:18px;">
            <label for="email" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Email address') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
        </div>
        <button type="submit"
                style="width:100%; padding:12px; border:0; border-radius:8px; background:var(--accent); color:#fff; font-weight:600; cursor:pointer;">
            {{ __('Check status') }}
        </button>
    </form>

    @if ($searched)
        @if ($application)
            <div style="border:1px solid var(--border); border-radius:10px; padding:20px;">
                <div style="font-size:13px; color:var(--muted); margin-bottom:4px;">{{ $application->reference_code }}</div>
                <div style="font-size:20px; font-weight:600; margin-bottom:14px;">{{ $application->full_name }}</div>

                <div style="display:inline-block; padding:5px 12px; border-radius:99px; font-size:13px; font-weight:600;
                            background:{{ $application->status === 1 ? '#ecfdf5' : ($application->status === 2 ? '#fef2f2' : '#fffbeb') }};
                            color:{{ $application->status === 1 ? '#065f46' : ($application->status === 2 ? '#991b1b' : '#92400e') }};">
                    {{ $application->status_label }}
                </div>

                @if ($application->status === 0)
                    <p style="font-size:14px; color:var(--muted); margin:16px 0 0; line-height:1.6;">
                        {{ __('Your application is in the review queue. We will email you once a decision is made.') }}
                    </p>
                @elseif ($application->status === 1)
                    <p style="font-size:14px; color:var(--muted); margin:16px 0 0; line-height:1.6;">
                        {{ __('Approved. Check your email for your login details.') }}
                        <a href="{{ route('user.login') }}" style="color:var(--accent);">{{ __('Log in') }}</a>
                    </p>
                @else
                    <p style="font-size:14px; color:var(--muted); margin:16px 0 0; line-height:1.6;">
                        <strong>{{ __('Reason:') }}</strong> {{ $application->rejection_reason }}
                    </p>
                @endif
            </div>
        @else
            <div style="padding:14px 16px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b;">
                {{ __('No application found with that reference code and email address.') }}
            </div>
        @endif
    @endif
</div>
@endsection
