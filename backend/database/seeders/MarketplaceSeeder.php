<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use App\Models\PaymentChannel;
use Illuminate\Database\Seeder;

/**
 * Adds only what the admin-curated marketplace needs on top of the existing
 * seeders. Safe to re-run: everything is an updateOrCreate keyed on the natural
 * unique column, so it will not duplicate rows.
 *
 * Run with:  php artisan db:seed --class=MarketplaceSeeder
 */
class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $this->notificationTemplates();
        $this->nowPaymentsChannel();
    }

    // -------------------------------------------------------------------------
    // Notification templates for the new lifecycle emails
    // -------------------------------------------------------------------------

    protected function notificationTemplates(): void
    {
        $templates = [
            [
                'act'          => 'MEMBERSHIP_APPROVED',
                'name'         => 'Membership Approved',
                'subj'         => 'Your {{site_name}} application has been approved',
                'email_body'   => '<p>Hi {{full_name}},</p>'
                                . '<p>Good news, your membership application has been approved.</p>'
                                . '<p><strong>Your login details</strong><br>'
                                . 'Username: {{username}}<br>'
                                . 'Email: {{email}}<br>'
                                . 'Temporary password: <code>{{temp_password}}</code></p>'
                                . '<p><a href="{{login_url}}">Log in here</a></p>'
                                . '<p>Two things to do straight away:</p>'
                                . '<ol><li>Change this temporary password. You will be prompted on first login.</li>'
                                . '<li>Complete identity verification (KYC) so you can apply to tasks that require it.</li></ol>'
                                . '<p>Application reference: {{reference_code}}</p>',
                'sms_body'     => 'Your {{site_name}} application is approved. Log in at {{login_url}} with username {{username}}.',
                'shortcodes'   => '[[full_name]], [[username]], [[email]], [[temp_password]], [[login_url]], [[reference_code]], [[site_name]]',
                'email_status' => 1,
                'sms_status'   => 0,
            ],
            [
                'act'          => 'TASK_ASSIGNED',
                'name'         => 'Task Assigned To Worker',
                'subj'         => 'You have been approved for: {{work_title}}',
                'email_body'   => '<p>Hi {{name}},</p>'
                                . '<p>Your application for <strong>{{work_title}}</strong> has been approved.</p>'
                                . '<p>The task files and instructions are waiting in your dashboard under My Tasks.</p>'
                                . '<p><strong>Deadline: {{deadline}}</strong></p>'
                                . '<p>If you do not submit before the deadline the slot is released to another worker '
                                . 'and the application fee is not refunded, so please start soon.</p>',
                'sms_body'     => 'Approved for {{work_title}}. Deadline {{deadline}}. Files are in your dashboard.',
                'shortcodes'   => '[[name]], [[work_title]], [[deadline]], [[site_name]]',
                'email_status' => 1,
                'sms_status'   => 0,
            ],
            [
                'act'          => 'SUBMISSION_REVISION',
                'name'         => 'Revision Requested',
                'subj'         => 'Changes requested on: {{work_title}}',
                'email_body'   => '<p>Hi {{name}},</p>'
                                . '<p>Your submission for <strong>{{work_title}}</strong> needs some changes before it can be approved.</p>'
                                . '<p><strong>What needs fixing:</strong><br>{{reason}}</p>'
                                . '<p>New deadline: {{deadline}}</p>',
                'sms_body'     => 'Changes requested on {{work_title}}. New deadline {{deadline}}.',
                'shortcodes'   => '[[name]], [[work_title]], [[reason]], [[deadline]], [[site_name]]',
                'email_status' => 1,
                'sms_status'   => 0,
            ],
        ];

        foreach ($templates as $data) {
            NotificationTemplate::updateOrCreate(['act' => $data['act']], $data);
        }
    }

    // -------------------------------------------------------------------------
    // NOWPayments gateway channel
    // -------------------------------------------------------------------------

    protected function nowPaymentsChannel(): void
    {
        // Seeded disabled with blank credentials. Admin fills in the API key and
        // IPN secret in the panel, then enables it. Never commit real keys here.
        PaymentChannel::updateOrCreate(
            ['code' => 1006],
            [
                'code'         => 1006,
                'name'         => 'NOWPayments',
                'driver'       => 'nowpayments',
                'status'       => 0,
                'is_crypto'    => 1,
                'credentials'  => [
                    'api_key'    => '',
                    'ipn_secret' => '',
                ],
                'currencies'   => [
                    'USD' => ['rate' => 1, 'min' => 5, 'max' => 100000, 'fixed_charge' => 0, 'percent_charge' => 0.5],
                ],
                'instructions' => 'Pay with crypto. Coins are credited once the network confirms the payment.',
                // MUST be an array: PaymentChannel casts webhook_info to 'array', and
                // the edit screen iterates it as label => value. A plain string here
                // round-trips through json_encode/json_decode as a string and makes
                // admin/payment-channels/{id}/edit fatal with "foreach() argument must
                // be of type array|object, string given".
                'webhook_info' => [
                    'IPN callback URL' => url('/ipn/NowPayments'),
                ],
                'form_id'      => 0,
            ]
        );
    }
}
