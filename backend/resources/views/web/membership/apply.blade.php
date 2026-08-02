@extends('web.layouts.app')

@section('title', __('Apply for membership') . ' — ' . (gs()->site_name ?? config('app.name')))

@section('content')
{{--
    Invite-only intake form. Two applicant types share one submission: the
    business block is additive, not a replacement, because a business applicant
    still has a named human contact behind it and MembershipApplicationRequest
    requires the individual fields either way.

    Deliberately NOT wrapped in a gs()->registration check. That flag is the one
    that gets switched off to close self-registration, and gating this form behind
    it is what previously hid the apply button on an invite-only site.
--}}
@php
    $docTypes = strtoupper(implode(', ', config('jobstation.membership.allowed_doc_types', ['pdf', 'doc', 'docx'])));
@endphp

<div style="max-width:720px; margin:0 auto; padding:48px 24px;"
     x-data="{
        type: '{{ old('applicant_type', 1) }}',
        submitting: false,
        get isBusiness() { return this.type === '2'; }
     }">

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

    @if (session('error'))
        <div style="padding:14px 16px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:24px;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="padding:14px 16px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:24px;">
            <strong>{{ __('Please fix the following:') }}</strong>
            <ul style="margin:8px 0 0; padding-left:20px; line-height:1.6;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('membership.apply.submit') }}" enctype="multipart/form-data"
          @submit="submitting = true">
        @csrf

        {{-- ===== Step 1: applicant type ===== --}}
        <section style="margin-bottom:26px;">
            @include('web.membership.partials.step-heading', [
                'number' => 1,
                'title'  => __('What kind of account do you need?'),
                'blurb'  => __('This decides which documents we ask for.'),
            ])

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;" class="apply-type-grid">
                @foreach ([
                    ['value' => '1', 'title' => __('An individual'), 'blurb' => __('You will do the tasks yourself.')],
                    ['value' => '2', 'title' => __('A business'),    'blurb' => __('You are applying on behalf of a registered company.')],
                ] as $option)
                    <label x-bind:style="type === '{{ $option['value'] }}'
                                ? 'border-color:var(--accent); background:rgba(99,102,241,0.06);'
                                : ''"
                           style="cursor:pointer; display:block; border:1.5px solid var(--border); border-radius:10px; padding:14px 16px; transition:border-color .15s, background .15s;">
                        <span style="display:flex; align-items:center; gap:9px;">
                            <input type="radio" name="applicant_type" value="{{ $option['value'] }}" x-model="type"
                                   style="margin:0; flex-shrink:0;">
                            <span style="font-size:14px; font-weight:600; color:var(--text);">{{ $option['title'] }}</span>
                        </span>
                        <span style="display:block; font-size:12.5px; color:var(--muted); margin-top:6px; line-height:1.5;">
                            {{ $option['blurb'] }}
                        </span>
                    </label>
                @endforeach
            </div>

            @error('applicant_type')
                <p style="font-size:12.5px; color:#dc2626; margin:8px 0 0;">{{ $message }}</p>
            @enderror
        </section>

        {{-- ===== Step 2: the person applying ===== --}}
        <section style="margin-bottom:26px;">
            @include('web.membership.partials.step-heading', [
                'number' => 2,
                'title'  => __('About you'),
                'blurb'  => __('We need a real person as the contact, even for a business account.'),
            ])

            <div style="border:1px solid var(--border); border-radius:12px; padding:20px;">
                <div style="margin-bottom:18px;">
                    <label for="full_name" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
                        {{ __('Full name') }} <span style="color:#dc2626;">*</span>
                    </label>
                    <input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" required
                           autocomplete="name"
                           style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('full_name') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                    @error('full_name')
                        <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:18px;">
                    <label for="email" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
                        {{ __('Email address') }} <span style="color:#dc2626;">*</span>
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           autocomplete="email"
                           style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('email') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                    <p style="font-size:12px; color:var(--muted); margin:6px 0 0;">
                        {{ __('If you are approved, your login details go to this address.') }}
                    </p>
                    @error('email')
                        <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;" class="apply-pair">
                    <div>
                        <label for="phone" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
                            {{ __('Phone') }}
                        </label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                               style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('phone') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                        @error('phone')
                            <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="country" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
                            {{ __('Country') }} <span style="color:#dc2626;">*</span>
                        </label>
                        <input id="country" type="text" name="country" value="{{ old('country') }}" required
                               autocomplete="country-name"
                               style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('country') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                        @error('country')
                            <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== Step 3: personal documents ===== --}}
        <section style="margin-bottom:26px;">
            @include('web.membership.partials.step-heading', [
                'number' => 3,
                'title'  => __('Your documents'),
                'blurb'  => __('Accepted formats:') . ' ' . $docTypes . '.',
            ])

            <div style="border:1px solid var(--border); border-radius:12px; padding:20px;">
                @include('web.membership.partials.file-drop', [
                    'name'     => 'resume',
                    'label'    => __('CV / Resume'),
                    'required' => true,
                    'hint'     => __('Your work history and relevant skills.'),
                ])

                @include('web.membership.partials.file-drop', [
                    'name'     => 'cover_letter',
                    'label'    => __('Cover letter'),
                    'required' => false,
                    'hint'     => __('Optional, but it helps your application.'),
                ])
            </div>
        </section>

        {{-- ===== Step 4: business block, only for business applicants ===== --}}
        <section x-show="isBusiness" x-cloak style="margin-bottom:26px;">
            @include('web.membership.partials.step-heading', [
                'number' => 4,
                'title'  => __('About the business'),
                'blurb'  => __('Required because you selected a business account.'),
            ])

            <div style="border:1px solid var(--border); border-radius:12px; padding:20px;">
                <div style="margin-bottom:18px;">
                    <label for="business_name" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
                        {{ __('Business name') }} <span style="color:#dc2626;">*</span>
                    </label>
                    <input id="business_name" type="text" name="business_name" value="{{ old('business_name') }}"
                           style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('business_name') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                    @error('business_name')
                        <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;" class="apply-pair">
                    <div>
                        <label for="business_email" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
                            {{ __('Business email') }} <span style="color:#dc2626;">*</span>
                        </label>
                        <input id="business_email" type="email" name="business_email" value="{{ old('business_email') }}"
                               style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('business_email') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                        @error('business_email')
                            <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="business_country" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
                            {{ __('Business country') }} <span style="color:#dc2626;">*</span>
                        </label>
                        <input id="business_country" type="text" name="business_country" value="{{ old('business_country') }}"
                               style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('business_country') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                        @error('business_country')
                            <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @include('web.membership.partials.file-drop', [
                    'name'     => 'business_registration_doc',
                    'label'    => __('Registration document'),
                    'required' => true,
                    'hint'     => __('Certificate of incorporation or equivalent.'),
                ])
            </div>
        </section>

        @if (recaptchaEnabled())
            <div style="margin-bottom:20px;">
                <div class="g-recaptcha" data-sitekey="{{ recaptchaPlugin()->shortcode['site_key'] ?? '' }}"></div>
                @error('captcha')
                    <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Submit, with visible progress. A double submission on a slow connection
             would trip the unique-email rule and show a confusing error, so the
             button disables itself for the duration of the upload. --}}
        <button type="submit" x-bind:disabled="submitting"
                x-bind:style="submitting ? 'opacity:.65; cursor:progress;' : ''"
                style="width:100%; padding:13px; border:0; border-radius:8px; background:var(--accent); color:#fff; font-size:15px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:9px;">
            <span x-show="submitting" x-cloak class="apply-spinner" aria-hidden="true"></span>
            <span x-show="!submitting">{{ __('Submit application') }}</span>
            <span x-show="submitting" x-cloak>{{ __('Uploading your application') }}</span>
        </button>

        <p x-show="submitting" x-cloak style="text-align:center; font-size:12.5px; color:var(--muted); margin:12px 0 0;">
            {{ __('Your documents are uploading. Please do not close this page.') }}
        </p>

        <p style="text-align:center; font-size:13px; color:var(--muted); margin:18px 0 0;">
            {{ __('Already applied?') }}
            <a href="{{ route('membership.status') }}" style="color:var(--accent);">{{ __('Check your status') }}</a>
        </p>
    </form>
</div>

<style>
.apply-spinner {
    width: 15px; height: 15px; flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: apply-spin .6s linear infinite;
}
@keyframes apply-spin { to { transform: rotate(360deg); } }
@media (max-width: 620px) {
    .apply-type-grid { grid-template-columns: 1fr !important; }
    .apply-pair { grid-template-columns: 1fr !important; }
}
</style>
@endsection

@push('scripts')
    @if (recaptchaEnabled())
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
@endpush
