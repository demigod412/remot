<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('user.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! verifyRecaptcha($request->input('g-recaptcha-response'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['captcha' => 'Please complete the reCAPTCHA verification and try again.']);
        }

        $remember = $request->boolean('remember');

        if (! Auth::guard('web')->attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Invalid email or password.');
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();

        // Banned
        if ($user->status == 0) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('user.login')
                ->with('error', 'Your account has been suspended. ' . ($user->ban_reason ? 'Reason: ' . $user->ban_reason : 'Please contact support.'));
        }

        // Determine required auth steps
        $steps = [];
        $gs = gs();

        if ($gs->email_verification && ! $user->email_verified) {
            $steps[] = 'email';
        }
        if ($gs->phone_verification && ! $user->phone_verified) {
            $steps[] = 'phone';
        }
        if ($user->two_fa_enabled) {
            $steps[] = '2fa';
            // Auto-send the code so the user doesn't have to click "send"
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update(['verify_code' => $code, 'verify_code_sent_at' => now()]);
            \App\Services\NotifyService::send($user, 'EMAIL_VERIFICATION', ['code' => $code]);
        }

        if (count($steps)) {
            $request->session()->put('auth_steps', $steps);
            return redirect()->route('user.authorization');
        }

        // Record session
        $this->recordSession($request, $user->id);

        return redirect()->intended(route('user.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('user.login')->with('success', 'Logged out successfully.');
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
            'user_id'  => $userId,
            'user_ip'  => $request->ip(),
            'browser'  => $browser,
            'os'       => $os,
            'city'     => '',
            'country'  => '',
            'country_code' => '',
        ]);
    }
}
