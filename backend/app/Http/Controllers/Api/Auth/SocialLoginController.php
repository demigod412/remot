<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Services\NotifyService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SocialLoginController extends Controller
{
    /**
     * POST /api/v1/auth/social
     *
     * Flutter sends a Firebase ID token obtained after Google / Apple sign-in.
     * We verify it with Firebase REST API, then find-or-create the user.
     *
     * Body: { firebase_token: string, provider: "google"|"apple" }
     */
    public function handle(Request $request): JsonResponse
    {
        $request->validate([
            'firebase_token' => ['required', 'string'],
            'provider'       => ['required', 'in:google,apple'],
        ]);

        $gs = gs();

        $firebaseUser = $this->verifyFirebaseToken($request->firebase_token);

        if (! $firebaseUser) {
            return response()->json(['message' => 'Invalid or expired Firebase token.'], 401);
        }

        $email      = $firebaseUser['email'] ?? null;
        $firebaseUid = $firebaseUser['localId'];
        $displayName = $firebaseUser['displayName'] ?? '';
        $photoUrl    = $firebaseUser['photoUrl'] ?? null;

        if (! $email) {
            return response()->json(['message' => 'No email address associated with this account.'], 422);
        }

        // Find existing user by firebase_uid or email
        $user = User::where('firebase_uid', $firebaseUid)
            ->orWhere('email', $email)
            ->first();

        $isNew = false;

        if (! $user) {
            if (! $gs->registration) {
                return response()->json(['message' => 'Registration is currently disabled.'], 403);
            }

            $nameParts = explode(' ', trim($displayName), 2);
            $firstname = $nameParts[0] ?: Str::before($email, '@');
            $lastname  = $nameParts[1] ?? '';

            $username = $this->generateUsername($firstname, $lastname);

            $user = DB::transaction(function () use ($email, $firstname, $lastname, $username, $firebaseUid, $photoUrl, $request) {
                return User::create([
                    'firstname'      => $firstname,
                    'lastname'       => $lastname,
                    'email'          => $email,
                    'username'       => $username,
                    'password'       => bcrypt(Str::random(32)),
                    'firebase_uid'   => $firebaseUid,
                    'email_verified' => 1, // Firebase already verified the email
                    'coin_balance'   => 0,
                    'status'         => 1,
                ]);
            });

            NotifyService::send($user, 'WELCOME');
            $isNew = true;
        } else {
            // Stamp firebase_uid if missing (first time social login on existing account)
            if (! $user->firebase_uid) {
                $user->update(['firebase_uid' => $firebaseUid, 'email_verified' => 1]);
            }

            if ($user->status == 0) {
                $reason = $user->ban_reason ? ' Reason: ' . $user->ban_reason : '';
                return response()->json(['message' => 'Your account has been suspended.' . $reason], 403);
            }
        }

        $ua = $request->userAgent() ?? '';
        UserSession::create([
            'user_id'      => $user->id,
            'user_ip'      => $request->ip(),
            'browser'      => $this->detect($ua, ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera']),
            'os'           => $this->detect($ua, ['Windows', 'Mac', 'Linux', 'Android', 'iOS', 'iPhone', 'iPad']),
            'city'         => '',
            'country'      => '',
            'country_code' => '',
        ]);

        $token  = $user->createToken('mobile')->plainTextToken;
        $steps  = [];
        if ($gs->phone_verification && ! $user->phone_verified) $steps[] = 'phone';
        if ($user->two_fa_enabled)                              $steps[] = '2fa';

        return response()->json([
            'message'       => $isNew ? 'Account created successfully.' : 'Login successful.',
            'token'         => $token,
            'token_type'    => 'Bearer',
            'user'          => $this->userResource($user),
            'pending_steps' => $steps,
            'is_new_user'   => $isNew,
        ], $isNew ? 201 : 200);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function verifyFirebaseToken(string $idToken): ?array
    {
        $apiKey = gs()->firebase_config['web_api_key'] ?? null;

        if (! $apiKey) {
            throw new \RuntimeException('Firebase Web API Key is not configured. Set it in Admin → Settings → Firebase.');
        }

        try {
            $response = (new Client(['timeout' => 10]))->post(
                "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}",
                ['json' => ['idToken' => $idToken]]
            );

            $body  = json_decode($response->getBody(), true);
            $users = $body['users'] ?? [];

            return $users[0] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generateUsername(string $firstname, string $lastname): string
    {
        $base = Str::slug(strtolower($firstname . ($lastname ? '_' . $lastname : '')), '_');
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'user';
        $base = substr($base, 0, 20);

        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        return $username;
    }

    private function userResource(User $user): array
    {
        return [
            'id'             => $user->id,
            'firstname'      => $user->firstname,
            'lastname'       => $user->lastname,
            'username'       => $user->username,
            'email'          => $user->email,
            'email_verified' => (bool) $user->email_verified,
            'phone_verified' => (bool) $user->phone_verified,
            'two_fa_enabled' => (bool) $user->two_fa_enabled,
            'kyc_status'     => $user->kyc_status,
            'coin_balance'   => (float) $user->coin_balance,
            'avatar'         => $user->image
                ? fileUrl(config('jobstation.upload_paths.user_avatar'), $user->image)
                : null,
        ];
    }

    private function detect(string $ua, array $list): string
    {
        foreach ($list as $item) {
            if (stripos($ua, $item) !== false) return $item;
        }
        return 'Unknown';
    }
}
