<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        if ($user->status == 0) {
            $reason = $user->ban_reason ? ' Reason: ' . $user->ban_reason : '';
            return response()->json(['message' => 'Your account has been suspended.' . $reason], 403);
        }

        $gs    = gs();
        $steps = [];
        if ($gs->email_verification && ! $user->email_verified) $steps[] = 'email';
        if ($gs->phone_verification && ! $user->phone_verified)  $steps[] = 'phone';
        if ($user->two_fa_enabled)                               $steps[] = '2fa';

        $token = $user->createToken('mobile')->plainTextToken;

        $ua = $request->userAgent() ?? '';
        UserSession::create([
            'user_id'      => $user->id,
            'user_ip'      => $request->ip(),
            'browser'      => $this->detect($ua, ['Chrome','Firefox','Safari','Edge','Opera']),
            'os'           => $this->detect($ua, ['Windows','Mac','Linux','Android','iOS','iPhone','iPad']),
            'city'         => '',
            'country'      => '',
            'country_code' => '',
        ]);

        return response()->json([
            'message'       => 'Login successful.',
            'token'         => $token,
            'token_type'    => 'Bearer',
            'user'          => $this->userResource($user),
            'pending_steps' => $steps,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Remove the specific device token so the device stops receiving push notifications
        if ($deviceToken = $request->input('device_token')) {
            $request->user()->deviceTokens()->where('token', $deviceToken)->delete();
        }

        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
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
