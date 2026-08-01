@extends('web.layouts.app')

@section('title', __('Apply for membership') . ' — ' . (gs()->site_name ?? config('app.name')))

@section('content')
<div style="max-width:760px; margin:0 auto; padding:48px 24px;">

    <h1 style="font-size:34px; font-weight:600; letter-spacing:-0.6px; margin:0 0 8px; color:var(--text);">
        {{ __('Apply for membership') }}
    </h1>
    <p style="font-size:15px; color:var(--muted); margin:0 0 32px; line-height:1.6;">
        {{ __('This is an invite-only marketplace. Submit an application and our team will review it. If approved, we will email your login details.') }}
    </p>

    @if (session('success'))
        <div style="padding:14px 16px; border-radius:8px; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; margin-bottom:24px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="padding:14px 16px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:24px;">
            <strong>{{ __('Please fix the following:') }}</strong>
            <ul style="margin:8px 0 0; padding-left:20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('membership.apply.submit') }}" enctype="multipart/form-data"
          x-data="{ type: '{{ old('applicant_type', 1) }}' }">
        @csrf

        {{-- Applicant type --}}
        <div style="margin-bottom:28px;">
            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:10px; color:var(--text);">
                {{ __('I am applying as') }}
            </label>
            <div style="display:flex; gap:12px;">
                <label style="flex:1; cursor:pointer;">
                    <input type="radio" name="applicant_type" value="1" x-model="type" style="margin-right:8px;">
                    {{ __('An individual') }}
                </label>
                <label style="flex:1; cursor:pointer;">
                    <input type="radio" name="applicant_type" value="2" x-model="type" style="margin-right:8px;">
                    {{ __('A business') }}
                </label>
            </div>
        </div>

        {{-- Individual details --}}
        <fieldset style="border:1px solid var(--border); border-radius:10px; padding:20px; margin-bottom:20px;">
            <legend style="font-size:13px; font-weight:600; padding:0 8px; color:var(--muted);">
                {{ __('Your details') }}
            </legend>

            <div style="margin-bottom:16px;">
                <label for="full_name" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Full name') }} *</label>
                <input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" required
                       style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
            </div>

            <div style="margin-bottom:16px;">
                <label for="email" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Email address') }} *</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
            </div>

            <div style="display:flex; gap:16px; margin-bottom:16px;">
                <div style="flex:1;">
                    <label for="phone" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Phone') }}</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                           style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
                <div style="flex:1;">
                    <label for="country" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Country') }} *</label>
                    <input id="country" type="text" name="country" value="{{ old('country') }}" required
                           style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label for="resume" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('CV / Resume') }} *</label>
                <input id="resume" type="file" name="resume" required accept=".pdf,.doc,.docx">
                <small style="display:block; color:var(--muted); margin-top:4px;">{{ __('PDF or Word, max 5 MB.') }}</small>
            </div>

            <div>
                <label for="cover_letter" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Cover letter') }}</label>
                <input id="cover_letter" type="file" name="cover_letter" accept=".pdf,.doc,.docx">
            </div>
        </fieldset>

        {{-- Business details, only when applying as a business --}}
        <fieldset x-show="type === '2'" x-cloak
                  style="border:1px solid var(--border); border-radius:10px; padding:20px; margin-bottom:20px;">
            <legend style="font-size:13px; font-weight:600; padding:0 8px; color:var(--muted);">
                {{ __('Business details') }}
            </legend>

            <div style="margin-bottom:16px;">
                <label for="business_name" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Business name') }} *</label>
                <input id="business_name" type="text" name="business_name" value="{{ old('business_name') }}"
                       style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
            </div>

            <div style="display:flex; gap:16px; margin-bottom:16px;">
                <div style="flex:1;">
                    <label for="business_email" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Business email') }} *</label>
                    <input id="business_email" type="email" name="business_email" value="{{ old('business_email') }}"
                           style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
                <div style="flex:1;">
                    <label for="business_country" style="display:block; font-size:13px; margin-bottom:6px;">{{ __('Business country') }} *</label>
                    <input id="business_country" type="text" name="business_country" value="{{ old('business_country') }}"
                           style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
            </div>

            <div>
                <label for="business_registration_doc" style="display:block; font-size:13px; margin-bottom:6px;">
                    {{ __('Registration document') }} *
                </label>
                <input id="business_registration_doc" type="file" name="business_registration_doc" accept=".pdf,.doc,.docx">
            </div>
        </fieldset>

        @if (recaptchaEnabled())
            <div style="margin-bottom:20px;">
                <div class="g-recaptcha" data-sitekey="{{ recaptchaPlugin()->shortcode['site_key'] ?? '' }}"></div>
            </div>
        @endif

        <button type="submit"
                style="width:100%; padding:13px; border:0; border-radius:8px; background:var(--accent); color:#fff; font-size:15px; font-weight:600; cursor:pointer;">
            {{ __('Submit application') }}
        </button>

        <p style="text-align:center; font-size:13px; color:var(--muted); margin:18px 0 0;">
            {{ __('Already applied?') }}
            <a href="{{ route('membership.status') }}" style="color:var(--accent);">{{ __('Check your status') }}</a>
        </p>
    </form>
</div>
@endsection

@push('scripts')
    @if (recaptchaEnabled())
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
@endpush
