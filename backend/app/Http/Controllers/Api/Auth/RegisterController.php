<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\ReferralEarning;
use App\Models\User;
use App\Models\UserSession;
use App\Services\NotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $gs = gs();

        if (! $gs->registration) {
            return response()->json(['message' => 'Registration is currently disabled.'], 403);
        }

        $rules = [
            'firstname' => ['required', 'string', 'max:60'],
            'lastname'  => ['required', 'string', 'max:60'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'username'  => ['required', 'alpha_num', 'min:4', 'max:30', 'unique:users,username'],
            'password'  => ['required', 'confirmed', 'min:6'],
        ];

        if ($gs->secure_password) {
            $rules['password'][] = Password::min(8)->mixedCase()->numbers()->symbols();
        }

        $data = $request->validate($rules);

        // Referral
        $referredBy = null;
        if ($ref = $request->input('ref')) {
            $referrer = User::where('username', $ref)->first();
            if ($referrer) {
                $referredBy = $referrer->id;
            }
        }

        $user = null;

        DB::transaction(function () use ($data, $request, $referredBy, $gs, &$user) {
            $user = User::create([
                'firstname'      => $data['firstname'],
                'lastname'       => $data['lastname'],
                'email'          => $data['email'],
                'username'       => $data['username'],
                'password'       => $data['password'],
                'referred_by'    => $referredBy ?? 0,
                'coin_balance'   => 0,
                'status'         => 1,
                'email_verified' => 0,
                'phone_verified' => 0,
                'kyc_status'     => 0,
            ]);

            if ($referredBy && $gs->ref_commission > 0) {
                ReferralEarning::create([
                    'earner_id'        => $referredBy,
                    'referred_user_id' => $user->id,
                    'coins_earned'     => $gs->ref_commission,
                    'original_amount'  => $gs->ref_commission,
                ]);
                $referrerUser = User::find($referredBy);
                if ($referrerUser) {
                    $referrerUser->increment('coin_balance', $gs->ref_commission);
                    $referrerUser->refresh();
                    LedgerEntry::create([
                        'user_id'       => $referredBy,
                        'coins'         => $gs->ref_commission,
                        'fee'           => 0,
                        'balance_after' => $referrerUser->coin_balance,
                        'entry_type'    => '+',
                        'category'      => 'referral',
                        'reference'     => $user->username,
                        'description'   => 'Referral bonus: ' . $user->firstname . ' joined',
                    ]);
                    NotifyService::send($referrerUser, 'REFERRAL_BONUS', [
                        'referred_username' => $user->username,
                        'coins'             => number_format($gs->ref_commission, 0),
                    ]);
                }
            }
        });

        NotifyService::send($user, 'WELCOME');

        $ua = $request->userAgent() ?? '';
        UserSession::create([
            'user_id'      => $user->id,
            'user_ip'      => $request->ip(),
            'browser'      => $this->detectBrowser($ua),
            'os'           => $this->detectOs($ua),
            'city'         => '',
            'country'      => '',
            'country_code' => '',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        $steps = [];
        if ($gs->email_verification) $steps[] = 'email';
        if ($gs->phone_verification) $steps[] = 'phone';

        return response()->json([
            'message'         => 'Registration successful.',
            'token'           => $token,
            'token_type'      => 'Bearer',
            'user'            => $this->userResource($user),
            'pending_steps'   => $steps,
        ], 201);
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
            'coin_balance'   => $user->coin_balance,
            'avatar'         => $user->image
                ? fileUrl(config('jobstation.upload_paths.user_avatar'), $user->image)
                : null,
        ];
    }

    private function detectBrowser(string $ua): string
    {
        foreach (['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'] as $b) {
            if (stripos($ua, $b) !== false) return $b;
        }
        return 'Unknown';
    }

    private function detectOs(string $ua): string
    {
        foreach (['Windows', 'Mac', 'Linux', 'Android', 'iOS', 'iPhone', 'iPad'] as $o) {
            if (stripos($ua, $o) !== false) return $o;
        }
        return 'Unknown';
    }
}
