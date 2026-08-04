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

<div style="max-width:720px; margin:0 auto; padding:48px 24px 96px;"
     x-data="{
        type: '{{ old('applicant_type', 1) }}',
        submitting: false,

        /* Mirrors of the required text fields, so the progress bar can reflect real
           completion. Seeded from old() so a failed submit does not reset it. */
        f: {
            full_name:        '{{ addslashes(old('full_name', '')) }}',
            email:            '{{ addslashes(old('email', '')) }}',
            country:          '{{ addslashes(old('country', '')) }}',
            business_name:    '{{ addslashes(old('business_name', '')) }}',
            business_email:   '{{ addslashes(old('business_email', '')) }}',
            business_country: '{{ addslashes(old('business_country', '')) }}'
        },

        /* Filled by the membership-file event each drop zone dispatches, so file state
           lives in one place instead of being invisible to the parent. */
        attached: {},

        get isBusiness() { return this.type === '2'; },

        onFile(e) { this.attached[e.detail.field] = e.detail.attached; },

        /* Required items for the chosen applicant type. */
        get needed() {
            const base = ['full_name', 'email', 'country', 'resume'];
            return this.isBusiness
                ? base.concat(['business_name', 'business_email', 'business_country', 'business_registration_doc'])
                : base;
        },

        get doneCount() {
            return this.needed.filter(k =>
                k === 'resume' || k === 'business_registration_doc'
                    ? !! this.attached[k]
                    : (this.f[k] || '').trim() !== ''
            ).length;
        },

        get percent() { return Math.round((this.doneCount / this.needed.length) * 100); },
        get ready()   { return this.doneCount === this.needed.length; }
     }"
     @membership-file="onFile($event)">

    <h1 style="font-size:34px; font-weight:600; letter-spacing:-0.6px; margin:0 0 8px; color:var(--text);">
        {{ __('Apply for membership') }}
    </h1>
    <p style="font-size:15px; color:var(--muted); margin:0 0 32px; line-height:1.6;">
        {{ __('This is an invite-only marketplace. Submit an application and our team will review it. If approved, we will email your login details.') }}
    </p>

    {{-- Live completion, so the length of the form is visible up front rather than
         being discovered by scrolling. Counts the required items for the chosen
         applicant type, which is why picking "a business" makes it drop. --}}
    <div style="margin-bottom:28px;">
        <div style="display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin-bottom:7px;">
            <span style="font-size:12px; color:var(--muted);">
                <span x-text="doneCount"></span> {{ __('of') }} <span x-text="needed.length"></span> {{ __('required items complete') }}
            </span>
            <span x-show="ready" x-cloak style="font-size:12px; color:#16a34a; font-weight:500;">
                {{ __('Ready to submit') }}
            </span>
        </div>
        <div style="height:5px; border-radius:99px; background:var(--border); overflow:hidden;">
            <div x-bind:style="'width:' + percent + '%'"
                 x-bind:class="ready ? 'apply-bar apply-bar--done' : 'apply-bar'"></div>
        </div>
    </div>

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
                    <label class="apply-card" x-bind:class="type === '{{ $option['value'] }}' ? 'apply-card--on' : ''">
                        {{-- The native radio is visually hidden but still focusable and
                             still what the form posts, so keyboard and screen-reader
                             users get the real control rather than a div pretending. --}}
                        <input type="radio" name="applicant_type" value="{{ $option['value'] }}" x-model="type"
                               class="apply-radio">
                        <span style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <span style="font-size:14.5px; font-weight:600; color:var(--text);">{{ $option['title'] }}</span>
                            <span class="apply-tick" x-show="type === '{{ $option['value'] }}'" x-cloak>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                                     stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;" aria-hidden="true">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </span>
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
                    <input id="full_name" type="text" name="full_name" x-model="f.full_name" required
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
                    <input id="email" type="email" name="email" x-model="f.email" required
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
                        <input id="country" type="text" name="country" x-model="f.country" required
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
        {{-- x-show, not x-if: switching type back and forth keeps whatever was already
             typed. Safe here because every input inside binds its required attribute to
             the same condition as the visibility. --}}
        <section x-show="isBusiness" x-cloak
                 x-transition:enter="apply-reveal"
                 style="margin-bottom:26px;">
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
                    <input id="business_name" type="text" name="business_name" x-model="f.business_name" x-bind:required="isBusiness"
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
                        <input id="business_email" type="email" name="business_email" x-model="f.business_email" x-bind:required="isBusiness"
                               style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('business_email') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                        @error('business_email')
                            <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="business_country" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
                            {{ __('Business country') }} <span style="color:#dc2626;">*</span>
                        </label>
                        <input id="business_country" type="text" name="business_country" x-model="f.business_country" x-bind:required="isBusiness"
                               style="width:100%; padding:10px 12px; border:1px solid {{ $errors->has('business_country') ? '#fca5a5' : 'var(--border)' }}; border-radius:8px; font-size:14px;">
                        @error('business_country')
                            <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- requiredWhen, not required: this field sits inside a section that is
                     hidden for individual applicants, and a hidden input carrying a plain
                     required attribute cannot be focused to report its error, so the
                     browser refuses to submit and says nothing at all. --}}
                @include('web.membership.partials.file-drop', [
                    'name'         => 'business_registration_doc',
                    'label'        => __('Registration document'),
                    'required'     => true,
                    'requiredWhen' => 'isBusiness',
                    'hint'         => __('Certificate of incorporation or equivalent.'),
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

        {{-- Sticks to the bottom on a phone, where this form is long enough that the
             button would otherwise be several scrolls away from the last field.

             The disabled state tracks `submitting` only, never `ready`. Greying out the
             button while fields are incomplete hides WHICH field is missing; letting the
             submit through means the browser points at it. --}}
        <div class="apply-submit">


              <button type="submit" x-bind:disabled="submitting"
                x-bind:style="submitting ? 'opacity:.65; cursor:progress;' : ''"
                style="width:100%; padding:13px; border:0; border-radius:8px; background:var(--accent); color:#fff; font-size:15px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:9px;">
            <span x-show="submitting" x-cloak class="apply-spinner" aria-hidden="true"></span>
            <span x-show="!submitting">{{ __('Submit application') }}</span>
            <span x-show="submitting" x-cloak>{{ __('Uploading your application') }}</span>
        </button>

            
            <button type="submit" x-bind:disabled="submitting"
                    x-bind:style="submitting ? 'opacity:.65; cursor:progress;' : ''"
                    style="width:100%; padding:14px; border:0; border-radius:9px; background:var(--accent); color:#fff; font-size:15px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:9px;">
                <span x-show="submitting" x-cloak class="apply-spinner" aria-hidden="true"></span>
                <span x-show="!submitting">{{ __('Submit application') }}</span>
                <span x-show="submitting" x-cloak>{{ __('Uploading your application') }}</span>
            </button>

            <p x-show="submitting" x-cloak aria-live="polite"
               style="text-align:center; font-size:12.5px; color:var(--muted); margin:11px 0 0;">
                {{ __('Your documents are uploading. Please do not close this page.') }}
            </p>

            <p x-show="!submitting" style="text-align:center; font-size:12px; color:var(--muted); margin:11px 0 0; line-height:1.55;">
                {{ __('We review applications by hand. If approved, your login details are emailed to you.') }}
            </p>
        </div>

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

.apply-bar {
    height: 100%;
    background: var(--accent);
    border-radius: 99px;
    transition: width .28s ease, background .28s ease;
}
.apply-bar--done { background: #16a34a; }

/* Applicant type cards. The radio stays in the DOM and focusable; only its default
   appearance is removed, so the card can show focus for keyboard users. */
.apply-card {
    position: relative;
    display: block;
    cursor: pointer;
    border: 1.5px solid var(--border);
    border-radius: 11px;
    padding: 15px 16px;
    transition: border-color .15s, background .15s, box-shadow .15s;
}
.apply-card:hover { border-color: var(--accent); }
.apply-card--on {
    border-color: var(--accent);
    background: rgba(99,102,241,0.06);
}
.apply-radio {
    position: absolute;
    opacity: 0;
    width: 1px; height: 1px;
    margin: 0;
}
.apply-card:has(.apply-radio:focus-visible) {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.18);
}
.apply-tick {
    flex-shrink: 0;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: var(--accent);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.apply-reveal { animation: apply-fade .22s ease; }
@keyframes apply-fade {
    from { opacity: 0; transform: translateY(-5px); }
    to   { opacity: 1; transform: none; }
}

@media (max-width: 620px) {
    .apply-type-grid { grid-template-columns: 1fr !important; }
    .apply-pair { grid-template-columns: 1fr !important; }

    /* On a phone the form runs long enough that the button would sit several
       scrolls below the last field. */
    .apply-submit {
        position: sticky;
        bottom: 0;
        padding: 14px 0 16px;
        margin: 0 -24px;
        padding-left: 24px;
        padding-right: 24px;
        background: linear-gradient(to top, var(--bg, #fff) 72%, transparent);
    }
}
</style>
@endsection

@push('scripts')
    @if (recaptchaEnabled())
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
@endpush
