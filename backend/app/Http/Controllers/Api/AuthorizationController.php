<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorizationController extends Controller
{
    /** GET /api/v1/authorization — return which steps are still pending */
    public function status(Request $request): JsonResponse
    {
        $user  = $request->user();
        $gs    = gs();
        $steps = [];

        if ($gs->email_verification && ! $user->email_verified) $steps[] = 'email';
        if ($gs->phone_verification && ! $user->phone_verified)  $steps[] = 'phone';
        if ($user->two_fa_enabled && ! $user->two_fa_verified)   $steps[] = '2fa';

        return response()->json([
            'pending_steps' => $steps,
            'complete'      => empty($steps),
        ]);
    }

    /** POST /api/v1/authorization/send-code */
    public function sendVerifyCode(Request $request): JsonResponse
    {
        $request->validate(['type' => ['required', 'in:email,phone,2fa']]);

        $user = $request->user();

        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInSeconds(now()) < 60) {
            return response()->json(['message' => 'Please wait before requesting a new code.'], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'verify_code'         => $code,
            'verify_code_sent_at' => now(),
        ]);

        $act = match ($request->type) {
            'email' => 'EMAIL_VERIFICATION',
            'phone' => 'PHONE_VERIFICATION',
            '2fa'   => 'TWO_FA_CODE',
            default => 'EMAIL_VERIFICATION',
        };

        NotifyService::send($user, $act, ['code' => $code]);

        return response()->json(['message' => 'Verification code sent.']);
    }

    /** POST /api/v1/authorization/verify-email */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();

        if (! $this->codeValid($user, $request->code, 30)) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $user->update(['email_verified' => 1, 'verify_code' => null]);

        return response()->json(['message' => 'Email verified successfully.']);
    }

    /** POST /api/v1/authorization/verify-phone */
    public function verifyPhone(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();

        if (! $this->codeValid($user, $request->code, 30)) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $user->update(['phone_verified' => 1, 'verify_code' => null]);

        return response()->json(['message' => 'Phone verified successfully.']);
    }

    /** POST /api/v1/authorization/verify-2fa */
    public function verifyTwoFa(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();

        if (! $this->codeValid($user, $request->code, 10)) {
            return response()->json(['message' => 'Invalid or expired 2FA code.'], 422);
        }

        $user->update(['two_fa_verified' => 1, 'verify_code' => null]);

        return response()->json(['message' => '2FA verified successfully.']);
    }

    private function codeValid($user, string $code, int $ttlMinutes): bool
    {
        if ($user->verify_code !== $code) return false;
        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInMinutes(now()) > $ttlMinutes) {
            return false;
        }
        return true;
    }
}
