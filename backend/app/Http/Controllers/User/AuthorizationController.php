<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthorizationController extends Controller
{
    /**
     * Show the authorization step form.
     * Determines which step is next from session.
     */
    public function showForm(Request $request)
    {
        $user  = Auth::guard('web')->user();
        $steps = $request->session()->get('auth_steps', []);

        if (empty($steps)) {
            return redirect()->route('user.dashboard');
        }

        $currentStep = $steps[0];

        return view('user.authorization', [
            'step' => $currentStep,
            'user' => $user,
        ]);
    }

    /**
     * Resend verification code (email or phone).
     */
    public function sendVerifyCode(Request $request)
    {
        $request->validate(['type' => ['required', 'in:email,phone']]);

        $user = Auth::guard('web')->user();

        // Rate limit
        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInSeconds(now()) < 60) {
            return back()->with('error', 'Please wait before requesting a new code.');
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'verify_code'         => $code,
            'verify_code_sent_at' => now(),
        ]);

        $act = $request->type === 'email' ? 'EMAIL_VERIFICATION' : 'PHONE_VERIFICATION';
        NotifyService::send($user, $act, ['code' => $code]);

        $via = $request->type === 'email' ? 'email' : 'phone';
        return back()->with('success', "A verification code has been sent to your {$via}.");
    }

    /**
     * Verify email code.
     */
    public function verifyEmail(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = Auth::guard('web')->user();

        if ($user->verify_code !== $request->code) {
            return back()->with('error', 'Invalid verification code.');
        }

        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInMinutes(now()) > 30) {
            return back()->with('error', 'Code has expired. Please request a new one.');
        }

        $user->update([
            'email_verified' => 1,
            'verify_code'    => null,
        ]);

        return $this->advanceStep($request, 'email');
    }

    /**
     * Verify phone code.
     */
    public function verifyPhone(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = Auth::guard('web')->user();

        if ($user->verify_code !== $request->code) {
            return back()->with('error', 'Invalid verification code.');
        }

        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInMinutes(now()) > 30) {
            return back()->with('error', 'Code has expired. Please request a new one.');
        }

        $user->update([
            'phone_verified' => 1,
            'verify_code'    => null,
        ]);

        return $this->advanceStep($request, 'phone');
    }

    /**
     * Verify 2FA code (email-based for Phase 1).
     */
    public function verifyTwoFa(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = Auth::guard('web')->user();

        if ($user->verify_code !== $request->code) {
            return back()->with('error', 'Invalid 2FA code.');
        }

        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInMinutes(now()) > 10) {
            return back()->with('error', '2FA code has expired. Please request a new one.');
        }

        $user->update([
            'two_fa_verified' => 1,
            'verify_code'     => null,
        ]);

        return $this->advanceStep($request, '2fa');
    }

    /**
     * Remove the completed step and redirect to next or dashboard.
     */
    private function advanceStep(Request $request, string $completed)
    {
        $steps = $request->session()->get('auth_steps', []);
        $steps = array_values(array_filter($steps, fn($s) => $s !== $completed));

        if (empty($steps)) {
            $request->session()->forget('auth_steps');
            $this->recordSession($request, Auth::guard('web')->id());
            return redirect()->route('user.dashboard');
        }

        $request->session()->put('auth_steps', $steps);
        return redirect()->route('user.authorization');
    }

    private function recordSession(Request $request, int $userId): void
    {
        $ua = $request->userAgent() ?? '';
        $browser = 'Unknown';
        foreach (['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'] as $b) {
            if (stripos($ua, $b) !== false) { $browser = $b; break; }
        }
        $os = 'Unknown';
        foreach (['Windows', 'Mac', 'Linux', 'Android', 'iOS', 'iPhone', 'iPad'] as $o) {
            if (stripos($ua, $o) !== false) { $os = $o; break; }
        }
        UserSession::create([
            'user_id' => $userId, 'user_ip' => $request->ip(),
            'browser' => $browser, 'os' => $os,
            'city' => '', 'country' => '', 'country_code' => '',
        ]);
    }
}
