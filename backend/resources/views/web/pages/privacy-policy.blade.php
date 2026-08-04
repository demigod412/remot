@extends('web.layouts.app')

@section('title', 'Privacy Policy — ' . (gs()->site_name ?? config('app.name')))

@section('content')

<section style="padding:48px 24px 80px;">
    <div class="container" style="max-width:820px;">

        <div style="text-align:center;margin-bottom:40px;">
            <h1 style="font-size:clamp(24px,4vw,40px);font-weight:900;margin-bottom:8px;">{{ __('Privacy Policy') }}</h1>
            <p style="color:var(--muted);">{{ __('Last updated:') }} {{ date('F d, Y') }}</p>
        </div>

        <div class="card" style="padding:32px 40px;">

            <p style="font-size:15px;line-height:1.7;color:var(--text);">
                {{ gs()->site_name ?? config('app.name') }} ("we", "our", "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or use our services.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">1. Information We Collect</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We may collect personal information such as your name, email address, phone number, payment information, and any other details you provide when you register, apply for membership, or contact us. We also collect usage data (e.g., IP address, browser type, pages visited) through cookies and similar technologies.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">2. How We Use Your Information</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We use your information to:
            </p>
            <ul style="font-size:14px;line-height:1.7;color:var(--muted);padding-left:20px;">
                <li>Provide, operate, and maintain our services</li>
                <li>Process your transactions and send you related notifications</li>
                <li>Communicate with you, including responding to your inquiries</li>
                <li>Improve our platform and develop new features</li>
                <li>Comply with legal obligations</li>
            </ul>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">3. Sharing Your Information</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We do not sell or rent your personal information. We may share your data with trusted third‑party service providers who assist us in operating our platform (e.g., payment processors, hosting providers), subject to strict confidentiality agreements. We may also disclose information if required by law or to protect our rights.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">4. Data Security</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We implement appropriate technical and organisational measures to protect your personal information against unauthorised access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">5. Your Rights</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                Depending on your location, you may have the right to access, correct, delete, or restrict the processing of your personal data. You may also withdraw consent at any time. To exercise these rights, please contact us at <a href="mailto:{{ gs()->contact_email ?? 'privacy@remotiox.com' }}" style="color:var(--accent);">{{ gs()->contact_email ?? 'privacy@remotiox.com' }}</a>.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">6. Cookies</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We use cookies and similar tracking technologies to enhance your experience, analyse usage, and personalise content. You can manage your cookie preferences through your browser settings. For more details, see our <a href="{{ route('cookie-policy') }}" style="color:var(--accent);">Cookie Policy</a>.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">7. Children’s Privacy</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                Our services are not directed to individuals under the age of 16. We do not knowingly collect personal information from children. If you believe we have inadvertently collected such data, please contact us so we can delete it.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">8. Changes to This Policy</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date. We encourage you to review this page periodically.
            </p>

            <h2 style="font-size:20px;font-weight:700;margin:28px 0 12px;color:var(--text);">9. Contact Us</h2>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                If you have any questions or concerns about this Privacy Policy, please contact us at:
            </p>
            <p style="font-size:14px;line-height:1.7;color:var(--muted);">
                <strong>{{ gs()->site_name ?? config('app.name') }}</strong><br>
                Email: <a href="mailto:{{ gs()->contact_email ?? 'privacy@remotiox.com' }}" style="color:var(--accent);">{{ gs()->contact_email ?? 'privacy@remotiox.com' }}</a>
            </p>

        </div>
    </div>
</section>

@endsection