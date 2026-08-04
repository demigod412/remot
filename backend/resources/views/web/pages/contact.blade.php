@extends('web.layouts.app')

@section('title', __('Contact Us') . ' — ' . (gs()->site_name ?? config('app.name')))

@section('content')

<section style="padding:48px 24px 80px;">
    <div class="container" style="max-width:640px;">

        <div style="text-align:center;margin-bottom:40px;">
            <h1 style="font-size:clamp(24px,4vw,40px);font-weight:900;margin-bottom:8px;">{{ __('Contact Us') }}</h1>
            <p style="color:var(--muted);">{{ __('Have a question? We\'d love to hear from you.') }}</p>
        </div>

        @if(session('success'))
            <div style="padding:16px 20px;border-radius:var(--radius);background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#4ade80;font-size:14px;margin-bottom:24px;">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <form method="POST" action="{{ route('contact.submit') }}">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div>
                        <label style="font-size:13px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">{{ __('Your Name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="{{ __('John Doe') }}"
                               style="width:100%;background:var(--bg-input);border:1px solid {{ $errors->has('name') ? '#ef4444' : 'var(--border)' }};border-radius:10px;padding:11px 14px;color:var(--text);font-size:14px;outline:none;">
                        @error('name') <span style="font-size:12px;color:#f87171;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">{{ __('Email Address') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="you@example.com"
                               style="width:100%;background:var(--bg-input);border:1px solid {{ $errors->has('email') ? '#ef4444' : 'var(--border)' }};border-radius:10px;padding:11px 14px;color:var(--text);font-size:14px;outline:none;">
                        @error('email') <span style="font-size:12px;color:#f87171;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="font-size:13px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">{{ __('Subject') }}</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required
                           placeholder="{{ __('How can we help?') }}"
                           style="width:100%;background:var(--bg-input);border:1px solid {{ $errors->has('subject') ? '#ef4444' : 'var(--border)' }};border-radius:10px;padding:11px 14px;color:var(--text);font-size:14px;outline:none;">
                    @error('subject') <span style="font-size:12px;color:#f87171;">{{ $message }}</span> @enderror
                </div>

                <div style="margin-bottom:24px;">
                    <label style="font-size:13px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">{{ __('Message') }}</label>
                    <textarea name="message" rows="6" required
                              placeholder="{{ __('Write your message here...') }}"
                              style="width:100%;background:var(--bg-input);border:1px solid {{ $errors->has('message') ? '#ef4444' : 'var(--border)' }};border-radius:10px;padding:11px 14px;color:var(--text);font-size:14px;outline:none;resize:vertical;">{{ old('message') }}</textarea>
                    @error('message') <span style="font-size:12px;color:#f87171;">{{ $message }}</span> @enderror
                </div>

                {!! recaptchaWidget() !!}
                @error('captcha') <div style="font-size:12px;color:#f87171;margin-bottom:10px;">{{ $message }}</div> @enderror

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px;">
                    {{ __('Send Message') }}
                </button>
            </form>
        </div>

        {{-- Info cards --}}
        <div class="grid-2" style="margin-top:32px;">
            <div class="card" style="text-align:center;padding:24px 16px;">
                <div style="font-size:28px;margin-bottom:10px;">📧</div>
                <div style="font-weight:700;color:var(--text);margin-bottom:4px;">{{ __('Email Us') }}</div>
                <div style="font-size:13px;color:var(--muted);">{{ gs()->email_from ?? 'support@remotiox.com' }}</div>
            </div>
            <div class="card" style="text-align:center;padding:24px 16px;">
                <div style="font-size:28px;margin-bottom:10px;">🎫</div>
                <div style="font-weight:700;color:var(--text);margin-bottom:4px;">{{ __('Support Ticket') }}</div>
                @auth('web')
                    <a href="{{ route('user.helpdesk.create') }}" style="font-size:13px;color:var(--purple-light);">{{ __('Open a ticket') }}</a>
                @else
                    <div style="font-size:13px;color:var(--muted);">{{ __('Login to open a ticket') }}</div>
                @endauth
            </div>
        </div>

    </div>
</section>

@endsection
