@extends('admin.layouts.app')
@section('title', 'Links & Social')
@section('page-title', 'Settings')

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:24px;max-width:1100px;align-items:start;">

    {{-- Left Nav --}}
    <div class="jobstation-card" style="padding:10px;">
        @include('admin.settings._nav', ['active' => 'links'])
    </div>

    {{-- Right Content --}}
    <div>
        <form method="POST" action="{{ route('admin.settings.links.update') }}">
        @csrf @method('POST')

        @php
            $field = function ($name, $label, $value, $hint, $ph = '') {
                echo '<div style="margin-bottom:16px;">';
                echo '<label style="display:block;font-size:12.5px;font-weight:500;color:var(--fg-2);margin-bottom:5px;">'.e($label).'</label>';
                echo '<input type="text" name="'.$name.'" value="'.e(old($name, $value)).'" placeholder="'.e($ph).'" style="width:100%;font-size:13px;" autocomplete="off">';
                if ($hint) echo '<div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">'.e($hint).'</div>';
                echo '</div>';
            };
        @endphp

        {{-- Mobile app badges --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;">Mobile App Links</h3>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:22px;">Powers the App Store / Google Play badges in the footer. Leave blank to hide a badge.</div>
            {!! $field('app_store_url', 'App Store URL', $settings->app_store_url, 'Your iOS app listing, e.g. https://apps.apple.com/app/id…', 'https://apps.apple.com/app/idXXXXXXXX') !!}
            {!! $field('play_store_url', 'Google Play URL', $settings->play_store_url, 'Your Android app listing, e.g. https://play.google.com/store/apps/details?id=…', 'https://play.google.com/store/apps/details?id=com.example') !!}
        </div>

        {{-- Social media --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;">Social Media</h3>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:22px;">Footer social icons. Only links you fill in are shown.</div>
            {!! $field('facebook',  'Facebook',  $settings->facebook,  '', 'https://facebook.com/yourpage') !!}
            {!! $field('twitter',   'X / Twitter', $settings->twitter, '', 'https://x.com/yourhandle') !!}
            {!! $field('linkedin',  'LinkedIn',  $settings->linkedin,  '', 'https://linkedin.com/company/yourcompany') !!}
            {!! $field('instagram', 'Instagram', $settings->instagram, '', 'https://instagram.com/yourhandle') !!}
        </div>

        {{-- Contact --}}
        <div class="jobstation-card" style="padding:24px;margin-bottom:16px;">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;">Contact</h3>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:22px;">Shown in the footer "Feel Free To Ask" block.</div>
            {!! $field('contact_email', 'Public email', $settings->contact_email, 'Clickable mailto link.', 'support@yourdomain.com') !!}
            {!! $field('contact_phone', 'Public phone', $settings->contact_phone, 'Clickable tel link.', '+1 555 123 4567') !!}
            {!! $field('support_hours', 'Support hours', $settings->support_hours, 'Defaults to “Mon – Fri, 9am – 6pm” if left blank.', 'Mon – Fri, 9am – 6pm') !!}
        </div>

        <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save Changes
        </button>
        </form>
    </div>
</div>

@endsection
