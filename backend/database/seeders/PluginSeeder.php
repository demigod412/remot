<?php

namespace Database\Seeders;

use App\Models\Plugin;
use Illuminate\Database\Seeder;

class PluginSeeder extends Seeder
{
    public function run(): void
    {
        $plugins = [
            [
                'act'         => 'google_recaptcha',
                'name'        => 'Google reCAPTCHA',
                'description' => 'Protect login, registration & contact forms with reCAPTCHA v2.',
                'image'       => null,
                'script'      => null,
                'shortcode'   => json_encode(['site_key' => '', 'secret_key' => '']),
                'support'     => 'https://www.google.com/recaptcha/admin',
                'status'      => 0,
            ],
            [
                'act'         => 'tawk_chat',
                'name'        => 'Tawk.to Live Chat',
                'description' => 'Add a live chat widget to your site via Tawk.to.',
                'image'       => null,
                'script'      => '<script type="text/javascript">var Tawk_API=Tawk_API||{},Tawk_LoadStart=new Date();(function(){var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];s1.async=true;s1.src="https://embed.tawk.to/{{property_id}}/default";s1.charset="UTF-8";s1.setAttribute("crossorigin","*");s0.parentNode.insertBefore(s1,s0)})();</script>',
                'shortcode'   => json_encode(['property_id' => '']),
                'support'     => 'https://dashboard.tawk.to',
                'status'      => 0,
            ],

            /*
             | ── Disabled plugins ──────────────────────────────────────────────
             | Google Analytics and Facebook Pixel are commented out by request.
             | They still work via renderPluginScripts() if you re-enable them —
             | just uncomment the entries below (each ships with a {{shortcode}}
             | the admin fills under Admin → Settings → Plugins).
             |
             | [
             |     'act' => 'google_analytics', 'name' => 'Google Analytics',
             |     'description' => 'Track visitors with Google Analytics (GA4).',
             |     'image' => null,
             |     'script' => '<script async src="https://www.googletagmanager.com/gtag/js?id={{measurement_id}}"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","{{measurement_id}}");</script>',
             |     'shortcode' => json_encode(['measurement_id' => '']),
             |     'support' => 'https://analytics.google.com', 'status' => 0,
             | ],
             | [
             |     'act' => 'facebook_pixel', 'name' => 'Facebook Pixel',
             |     'description' => 'Track conversions and build audiences with Facebook Pixel.',
             |     'image' => null,
             |     'script' => '<script>!function(f,b,e,v,n,t,s){...}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init","{{pixel_id}}");fbq("track","PageView");</script>',
             |     'shortcode' => json_encode(['pixel_id' => '']),
             |     'support' => 'https://business.facebook.com', 'status' => 0,
             | ],
             */
        ];

        foreach ($plugins as $data) {
            Plugin::updateOrCreate(
                ['act' => $data['act']],
                $data
            );
        }
    }
}
