<?php

namespace Database\Seeders;

use App\Models\SitePage;
use Illuminate\Database\Seeder;

class PolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        $site = config('app.name', 'Job Station');

        $pages = [
            [
                'name'       => 'Terms & Conditions',
                'slug'       => 'terms',
                'is_default' => true,
                'secs'       => [[
                    'heading' => 'Terms & Conditions',
                    'content' => '<p>Last updated: ' . date('F j, Y') . '</p>
<p>Please read these Terms and Conditions carefully before using ' . $site . '. By accessing or using the platform you agree to be bound by these terms.</p>

<h2>1. Acceptance of Terms</h2>
<p>By creating an account or using any part of the platform, you confirm that you are at least 18 years old (or the age of majority in your jurisdiction) and agree to these Terms and all applicable laws and regulations.</p>

<h2>2. User Accounts</h2>
<p>You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account. Notify us immediately of any unauthorised use. We reserve the right to suspend or terminate accounts that violate these Terms.</p>

<h2>3. Platform Services</h2>
<p>' . $site . ' is a marketplace connecting workers and employers. We facilitate task posting, job listings, and contracts. We are not a party to any agreement between users and are not responsible for the quality, safety, legality, or outcome of any transaction.</p>

<h2>4. Payments & Coins</h2>
<p>All on-platform transactions use ' . $site . ' Coins. Coin values are determined by the current exchange rate shown on the platform. We do not guarantee the value or availability of any specific exchange rate. Cashout requests are subject to minimum balance requirements and verification checks.</p>

<h2>5. Prohibited Conduct</h2>
<ul>
  <li>Submitting false, misleading, or fraudulent work</li>
  <li>Circumventing the platform to pay or receive payment off-platform</li>
  <li>Harassing, threatening, or abusing other users</li>
  <li>Attempting to reverse-engineer, copy, or exploit the platform</li>
  <li>Creating multiple accounts to gain unfair advantages</li>
</ul>

<h2>6. Intellectual Property</h2>
<p>All content, trademarks, and code on ' . $site . ' are owned by or licensed to us. You may not reproduce or distribute any content without prior written permission.</p>

<h2>7. Limitation of Liability</h2>
<p>To the maximum extent permitted by law, ' . $site . ' is not liable for any indirect, incidental, special, or consequential damages arising from your use of the platform. Our total liability to you shall not exceed the amount you paid us in the 12 months preceding the claim.</p>

<h2>8. Termination</h2>
<p>We may suspend or terminate your access at any time for violation of these Terms or for any other reason at our sole discretion. Upon termination, your right to use the platform ceases immediately.</p>

<h2>9. Governing Law</h2>
<p>These Terms are governed by the laws of the jurisdiction in which ' . $site . ' is incorporated. Any disputes shall be resolved exclusively in the courts of that jurisdiction.</p>

<h2>10. Changes to Terms</h2>
<p>We may update these Terms at any time. We will notify registered users of material changes. Continued use of the platform after changes constitutes acceptance of the revised Terms.</p>

<h2>11. Contact</h2>
<p>For questions about these Terms, please contact us through the Contact page on the platform.</p>',
                ]],
            ],
            [
                'name'       => 'Privacy Policy',
                'slug'       => 'privacy-policy',
                'is_default' => true,
                'secs'       => [[
                    'heading' => 'Privacy Policy',
                    'content' => '<p>Last updated: ' . date('F j, Y') . '</p>
<p>This Privacy Policy explains how ' . $site . ' collects, uses, and protects your personal information when you use our platform.</p>

<h2>1. Information We Collect</h2>
<p>We collect information you provide directly, including:</p>
<ul>
  <li><strong>Account data:</strong> name, email address, phone number, profile photo</li>
  <li><strong>Identity data:</strong> government-issued ID documents for KYC verification</li>
  <li><strong>Financial data:</strong> payment method details, cashout account information</li>
  <li><strong>Activity data:</strong> work submissions, job applications, messages, ratings</li>
</ul>
<p>We also collect data automatically, such as IP address, device type, browser, and pages visited.</p>

<h2>2. How We Use Your Information</h2>
<ul>
  <li>To create and manage your account</li>
  <li>To process payments and cashout requests</li>
  <li>To verify your identity (KYC)</li>
  <li>To provide customer support</li>
  <li>To send important account and service notifications</li>
  <li>To improve and personalise the platform experience</li>
  <li>To detect and prevent fraud and abuse</li>
</ul>

<h2>3. Sharing of Information</h2>
<p>We do not sell your personal data. We may share information with:</p>
<ul>
  <li><strong>Service providers:</strong> payment processors, email/SMS providers, cloud hosts — under strict data processing agreements</li>
  <li><strong>Other users:</strong> your public profile (username, bio, rating) is visible to other users</li>
  <li><strong>Legal authorities:</strong> when required by law or to protect our rights</li>
</ul>

<h2>4. Data Retention</h2>
<p>We retain your data for as long as your account is active, plus a period required for legal and financial compliance. You may request deletion of your account at any time, subject to any outstanding obligations.</p>

<h2>5. Cookies</h2>
<p>We use cookies and similar technologies. See our <a href="/cookie-policy">Cookie Policy</a> for details. You can control cookies through your browser settings.</p>

<h2>6. Security</h2>
<p>We implement industry-standard security measures including TLS encryption, access controls, and regular security reviews. No system is 100% secure; we encourage you to use a strong, unique password.</p>

<h2>7. Your Rights</h2>
<p>Depending on your location, you may have the right to access, correct, port, or delete your personal data. Contact us to exercise these rights. We will respond within 30 days.</p>

<h2>8. Children\'s Privacy</h2>
<p>The platform is not directed at anyone under 18. We do not knowingly collect data from minors. If you believe a minor has provided us with data, contact us immediately.</p>

<h2>9. Changes to This Policy</h2>
<p>We may update this Policy. We will notify you of material changes. Continued use after notice constitutes acceptance.</p>

<h2>10. Contact</h2>
<p>For privacy enquiries, use the Contact page on the platform.</p>',
                ]],
            ],
            [
                'name'       => 'Cookie Policy',
                'slug'       => 'cookie-policy',
                'is_default' => true,
                'secs'       => [[
                    'heading' => 'Cookie Policy',
                    'content' => '<p>Last updated: ' . date('F j, Y') . '</p>
<p>This Cookie Policy explains how ' . $site . ' uses cookies and similar technologies when you visit our platform.</p>

<h2>What Are Cookies?</h2>
<p>Cookies are small text files placed on your device by a website. They are widely used to make websites work, improve user experience, and provide reporting information to site owners.</p>

<h2>Types of Cookies We Use</h2>

<h3>Strictly Necessary</h3>
<p>These cookies are essential for the platform to function and cannot be switched off. They are set in response to your actions — such as logging in, setting your language preference, or completing a form.</p>

<h3>Functional</h3>
<p>These cookies enable enhanced functionality and personalisation, such as remembering your preferences, currency selection, and theme settings. Disabling them may affect some features.</p>

<h3>Analytics</h3>
<p>We use analytics cookies to understand how visitors interact with the platform — which pages are visited most, how long users stay, and where they come from. This data is anonymised and used solely to improve the service.</p>

<h3>Session Cookies</h3>
<p>Session cookies are temporary and expire when you close your browser. We use them to maintain your logged-in state during a browsing session.</p>

<h2>Cookie Consent</h2>
<p>When you first visit ' . $site . ', a cookie banner gives you the choice to accept or reject non-essential cookies. Strictly necessary cookies are always active. You can change your preferences at any time by clearing cookies in your browser settings, which will cause the consent banner to reappear on your next visit.</p>

<h2>Third-Party Cookies</h2>
<p>Some pages may include content from third parties (e.g. embedded media or payment widgets) that set their own cookies. We have no control over these cookies. Check the relevant third party\'s cookie policy for details.</p>

<h2>Managing Cookies</h2>
<p>You can control and delete cookies through your browser settings:</p>
<ul>
  <li><strong>Chrome:</strong> Settings → Privacy and Security → Cookies and other site data</li>
  <li><strong>Firefox:</strong> Options → Privacy & Security → Cookies and Site Data</li>
  <li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data</li>
  <li><strong>Edge:</strong> Settings → Privacy, search, and services → Cookies</li>
</ul>
<p>Note: Disabling all cookies may impair the functionality of this and other websites.</p>

<h2>Changes to This Policy</h2>
<p>We may update this Cookie Policy. Any changes will be posted on this page with an updated date.</p>

<h2>Contact</h2>
<p>Questions about this Cookie Policy? Use the Contact page on the platform.</p>',
                ]],
            ],
            [
                'name'       => 'About Us',
                'slug'       => 'about',
                'is_default' => true,
                'secs'       => [[
                    'heading' => 'About Us',
                    'content' => '<p>' . $site . ' is a micro-work marketplace designed to connect skilled workers with employers who need tasks completed quickly and reliably. From social-media engagement tasks to full-time hiring and private contracts — everything happens in one unified platform.</p>

<h2>Our Mission</h2>
<p>We believe earning should be accessible. Whether you are a student looking to make extra income, a freelancer seeking reliable clients, or a business that needs work done fast — ' . $site . ' is built for you.</p>

<h2>What We Offer</h2>
<ul>
  <li><strong>Instant Jobs:</strong> Complete quick tasks — social follows, reviews, surveys — and earn coins that convert to real money</li>
  <li><strong>Hiring Jobs:</strong> Browse full job listings from verified employers — remote, part-time, and on-site roles</li>
  <li><strong>Contracts:</strong> Private, escrow-backed agreements between two verified users for longer engagements</li>
</ul>

<h2>How It Works</h2>
<ol>
  <li>Create a free account and complete your profile</li>
  <li>Browse available work or post your own tasks</li>
  <li>Submit your work with the required proof</li>
  <li>Get paid in ' . $site . ' Coins and cash out to your preferred payment method</li>
</ol>

<h2>Our Values</h2>
<ul>
  <li><strong>Trust:</strong> KYC verification and escrow protect both sides of every transaction</li>
  <li><strong>Transparency:</strong> Rates, commissions, and review criteria are always visible</li>
  <li><strong>Fairness:</strong> Workers are reviewed on merit; employers are held to posted terms</li>
</ul>

<h2>Get in Touch</h2>
<p>We love hearing from our community. If you have a question, idea, or issue — visit our <a href="/contact">Contact</a> page and we will get back to you as soon as possible.</p>',
                ]],
            ],
        ];

        foreach ($pages as $page) {
            SitePage::updateOrCreate(
                ['slug' => $page['slug']],
                array_merge($page, ['tempname' => 'page'])
            );
        }
    }
}
