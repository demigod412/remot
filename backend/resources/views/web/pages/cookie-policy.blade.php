@extends('web.layouts.app')

@section('title', 'Cookie Policy — ' . (gs()->site_name ?? config('app.name')))

@section('content')

<section style="padding:48px 24px 80px;">
    <div class="container" style="max-width:820px;">

        <div style="text-align:center;margin-bottom:40px;">
            <h1 style="font-size:clamp(24px,4vw,40px);font-weight:900;margin-bottom:8px;">{{ __('Cookie Policy') }}</h1>
            <p style="color:var(--muted);">{{ __('Last updated:') }} {{ date('F d, Y') }}</p>
        </div>

        <div class="card" style="padding:32px 40px;">

            <p style="font-size:15px;line-height:1.7;color:var(--text);">
                This Cookie Policy explains how {{ gs()->site_name ?? config('app.name') }} ("we", "our", "us") uses cookies and similar technologies on our website. By using our site, you consent to the use of cookies as described here.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">What are cookies?</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                Cookies are small text files that are placed on your device when you visit a website. They help us recognise your browser and remember certain information, such as your preferences and login status, to improve your experience.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">How we use cookies</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We use cookies for the following purposes:
            </p>
            <ul style="font-size:14px;line-height:1.7;color:var(--muted);padding-left:20px;">
                <li><strong>Essential cookies:</strong> Necessary for the basic functioning of our site, such as authentication and security.</li>
                <li><strong>Preference cookies:</strong> Remember your choices (e.g., language, currency) to personalise your experience.</li>
                <li><strong>Analytics cookies:</strong> Help us understand how visitors interact with our site, allowing us to improve performance and content.</li>
                <li><strong>Marketing cookies:</strong> Used to deliver relevant advertisements and measure campaign effectiveness (if applicable).</li>
            </ul>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">Third‑party cookies</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We may allow third‑party service providers (e.g., analytics, payment processors) to place cookies on your device. These providers have their own privacy and cookie policies. We recommend that you review them.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">Managing cookies</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                You can control and manage cookies through your browser settings. Most browsers allow you to block or delete cookies, or to alert you when cookies are being sent. However, please note that disabling certain cookies may affect the functionality of our site.
            </p>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                For more information on how to manage cookies, visit your browser's help section or websites like <a href="https://www.aboutcookies.org" target="_blank" style="color:var(--accent);">www.aboutcookies.org</a>.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">Our use of consent</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                When you first visit our site, we display a cookie banner asking for your consent to non‑essential cookies. You can choose to accept all or reject non‑essential cookies. Your preference is stored for future visits.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">Changes to this policy</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We may update this Cookie Policy from time to time. Any changes will be posted on this page with an updated effective date. We encourage you to review this page periodically.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">Contact us</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                If you have questions about our use of cookies, please contact us at <a href="mailto:{{ gs()->contact_email ?? 'support@remotiox.com' }}" style="color:var(--accent);">{{ gs()->contact_email ?? 'support@remotiox.com' }}</a>.
            </p>

        </div>
    </div>
</section>

@endsection