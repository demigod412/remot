@extends('web.layouts.app')

@section('title', 'Terms of Service — ' . (gs()->site_name ?? config('app.name')))

@section('content')

<section style="padding:48px 24px 80px;">
    <div class="container" style="max-width:820px;">

        <div style="text-align:center;margin-bottom:40px;">
            <h1 style="font-size:clamp(24px,4vw,40px);font-weight:900;margin-bottom:8px;">{{ __('Terms of Service') }}</h1>
            <p style="color:var(--muted);">{{ __('Last updated:') }} {{ date('F d, Y') }}</p>
        </div>

        <div class="card" style="padding:32px 40px;">

            <p style="font-size:15px;line-height:1.7;color:var(--text);">
                Welcome to {{ gs()->site_name ?? config('app.name') }}. By accessing or using our platform, you agree to be bound by these Terms of Service ("Terms"). Please read them carefully.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">1. Acceptance of Terms</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                By using our website and services, you accept these Terms and our <a href="{{ route('privacy-policy') }}" style="color:var(--accent);">Privacy Policy</a>. If you do not agree, please do not use our platform.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">2. Description of Services</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                {{ gs()->site_name ?? config('app.name') }} provides a marketplace connecting clients and workers for AI training, data annotation, and related tasks. We facilitate job posting, task completion, and payment processing, but we are not a party to any agreement between clients and workers.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">3. User Accounts</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                You must create an account to access certain features. You are responsible for maintaining the confidentiality of your login credentials and for all activities that occur under your account. You agree to provide accurate and complete information and to update it promptly.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">4. User Conduct</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                You agree not to:
            </p>
            <ul style="font-size:14px;line-height:1.7;color:var(--muted);padding-left:20px;">
                <li>Violate any applicable laws or regulations</li>
                <li>Post fraudulent, misleading, or inappropriate content</li>
                <li>Interfere with the operation of our platform or security systems</li>
                <li>Impersonate any person or entity</li>
                <li>Engage in any activity that could harm our reputation or users</li>
            </ul>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">5. Payments and Fees</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                Workers earn coins (JC) for completed tasks. Coins can be converted to real currency subject to our payout policies. Clients pay fees as described on our platform. All fees are non‑refundable except as expressly provided.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">6. Intellectual Property</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                All content on our platform, including text, graphics, logos, and software, is the property of {{ gs()->site_name ?? config('app.name') }} or its licensors and is protected by copyright and other intellectual property laws. You may not reproduce, distribute, or create derivative works without our prior written consent.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">7. Dispute Resolution</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                Any disputes arising from these Terms or your use of our platform shall be governed by the laws of [Your Jurisdiction]. You agree to submit to the exclusive jurisdiction of the courts located in [Your City/Country].
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">8. Limitation of Liability</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                To the fullest extent permitted by law, {{ gs()->site_name ?? config('app.name') }} shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of our platform. Our total liability shall not exceed the amount you paid to us, if any, in the past six months.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">9. Termination</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We may suspend or terminate your account at our sole discretion if we believe you have violated these Terms. Upon termination, your right to use our platform ceases immediately, and any outstanding fees become due.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">10. Changes to Terms</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We may revise these Terms from time to time. Changes are effective upon posting. Continued use of our platform after changes constitutes acceptance of the new Terms.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">11. Contact Information</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                For questions regarding these Terms, please contact us at <a href="mailto:{{ gs()->contact_email ?? 'legal@remotiox.com' }}" style="color:var(--accent);">{{ gs()->contact_email ?? 'legal@remotiox.com' }}</a>.
            </p>

        </div>
    </div>
</section>

@endsection