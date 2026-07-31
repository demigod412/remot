<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('user.auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'exists:users,email']]);

        if (! verifyRecaptcha($request->input('g-recaptcha-response'))) {
            return back()->withInput()
                ->withErrors(['captcha' => 'Please complete the reCAPTCHA verification and try again.']);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Rate limit: 2 minutes between sends
        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInSeconds(now()) < 120) {
            return back()->with('error', 'Please wait before requesting a new code.');
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'verify_code'         => $code,
            'verify_code_sent_at' => now(),
        ]);

        NotifyService::send($user, 'PASSWORD_RESET', ['code' => $code]);

        $request->session()->put('reset_email', $user->email);

        return redirect()->route('user.verify-code')
            ->with('success', 'A 6-digit verification code has been sent to your email.');
    }

    public function showVerifyForm()
    {
        if (! session('reset_email')) {
            return redirect()->route('user.forgot-password');
        }
        return view('user.auth.verify-code');
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $email = session('reset_email');
        if (! $email) {
            return redirect()->route('user.forgot-password')->with('error', 'Session expired. Please start again.');
        }

        $user = User::where('email', $email)->first();

        if (! $user || $user->verify_code !== $request->code) {
            return back()->with('error', 'Invalid verification code.');
        }

        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInMinutes(now()) > 30) {
            return back()->with('error', 'Code has expired. Please request a new one.');
        }

        // Mark code as used
        $user->update(['verify_code' => null]);
        $request->session()->put('reset_verified', $email);

        return redirect()->route('user.reset-password');
    }
}
