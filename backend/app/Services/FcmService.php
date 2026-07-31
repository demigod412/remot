<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send a push notification to a single FCM device token.
     */
    public static function send(string $fcmToken, string $title, string $body, array $data = []): void
    {
        $projectId = gs()->firebase_config['project_id'] ?? null;
        if (! $projectId) return;

        try {
            $accessToken = static::accessToken();

            (new Client(['timeout' => 10]))->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'message' => [
                            'token'        => $fcmToken,
                            'notification' => ['title' => $title, 'body' => $body],
                            'data'         => array_map('strval', $data),
                            'android'      => ['priority' => 'high'],
                            'apns'         => ['headers' => ['apns-priority' => '10']],
                        ],
                    ],
                ]
            );
        } catch (\Throwable $e) {
            // Stale token — remove it so we don't keep sending to dead tokens
            if (str_contains($e->getMessage(), 'UNREGISTERED') || str_contains($e->getMessage(), 'NOT_FOUND')) {
                \App\Models\UserDeviceToken::where('token', $fcmToken)->delete();
            }
            Log::warning("FcmService: send failed [{$fcmToken}]: " . $e->getMessage());
        }
    }

    /**
     * Send a push notification to all registered devices for a user.
     */
    public static function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->deviceTokens()->pluck('token');
        foreach ($tokens as $token) {
            static::send($token, $title, $body, $data);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OAuth2 token via service account JWT (FCM v1 requires this)
    // ─────────────────────────────────────────────────────────────────────────

    private static function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3500, function () {
            $creds = static::loadCredentials();

            $header  = static::b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = static::b64url(json_encode([
                'iss'   => $creds['client_email'],
                'sub'   => $creds['client_email'],
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => time(),
                'exp'   => time() + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ]));

            $input      = $header . '.' . $payload;
            $privateKey = openssl_pkey_get_private($creds['private_key']);
            openssl_sign($input, $sig, $privateKey, OPENSSL_ALGO_SHA256);

            $jwt = $input . '.' . static::b64url($sig);

            $res = (new Client())->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ],
            ]);

            return json_decode($res->getBody(), true)['access_token'];
        });
    }

    private static function loadCredentials(): array
    {
        $fbConfig = gs()->firebase_config ?? [];

        $path = $fbConfig['credentials_path'] ?? null;
        if ($path && file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        throw new \RuntimeException('Firebase service account file not found. Upload it in Admin → Settings → Firebase.');
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
