{{--
    Numbered section heading for the membership application form.

    Usage:
        @include('web.membership.partials.step-heading', [
            'number' => 2,
            'title'  => __('About you'),
            'blurb'  => __('Optional supporting line.'),
        ])
--}}
@php
    $blurb = $blurb ?? null;
@endphp

<div style="display:flex; align-items:flex-start; gap:11px; margin-bottom:13px;">
    <span aria-hidden="true"
          style="flex-shrink:0; width:24px; height:24px; border-radius:50%; background:var(--accent); color:#fff; font-size:12.5px; font-weight:600; display:flex; align-items:center; justify-content:center; margin-top:1px;">
        {{ $number }}
    </span>
    <div>
        <h2 style="font-size:16px; font-weight:600; margin:0; color:var(--text); letter-spacing:-0.2px;">
            {{ $title }}
        </h2>
        @if($blurb)
            <p style="font-size:13px; color:var(--muted); margin:4px 0 0; line-height:1.5;">{{ $blurb }}</p>
        @endif
    </div>
</div>
