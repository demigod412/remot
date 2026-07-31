<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    public function sendCode(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email', 'exists:users,email']]);

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInSeconds(now()) < 120) {
            return response()->json(['message' => 'Please wait 2 minutes before requesting a new code.'], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'verify_code'         => $code,
            'verify_code_sent_at' => now(),
        ]);

        NotifyService::send($user, 'PASSWORD_RESET', ['code' => $code]);

        return response()->json(['message' => 'A 6-digit code has been sent to your email.']);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code'  => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->verify_code !== $request->code) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInMinutes(now()) > 30) {
            return response()->json(['message' => 'Code has expired. Please request a new one.'], 422);
        }

        // Issue a short-lived reset token stored in the user row
        $resetToken = bin2hex(random_bytes(20));
        $user->update([
            'verify_code'         => null,
            'password_reset_token' => hash('sha256', $resetToken),
        ]);

        return response()->json([
            'message'      => 'Code verified.',
            'reset_token'  => $resetToken,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => ['required', 'email', 'exists:users,email'],
            'reset_token'           => ['required', 'string'],
            'password'              => ['required', 'confirmed', 'min:6'],
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (! $user->password_reset_token
            || ! hash_equals($user->password_reset_token, hash('sha256', $request->reset_token))) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        $user->update([
            'password'             => Hash::make($request->password),
            'password_reset_token' => null,
        ]);

        return response()->json(['message' => 'Password reset successfully. You can now log in.']);
    }
}
