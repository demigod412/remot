<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    private array $allowedProviders = ['google', 'facebook'];

    private function configureProvider(string $provider): bool
    {
        $creds = gs()->socialite_credentials[$provider] ?? null;

        if (! $creds || empty($creds['client_id']) || empty($creds['client_secret'])) {
            return false;
        }

        config(["services.{$provider}" => [
            'client_id'     => $creds['client_id'],
            'client_secret' => $creds['client_secret'],
            'redirect'      => route('user.social.callback', $provider),
        ]]);

        return true;
    }

    public function redirect(Request $request, string $provider)
    {
        if (! in_array($provider, $this->allowedProviders)) {
            return redirect()->route('user.login')->with('error', 'Unsupported social provider.');
        }

        if (! $this->configureProvider($provider)) {
            return redirect()->route('user.login')->with('error', 'Social login is not configured.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        if (! in_array($provider, $this->allowedProviders)) {
            return redirect()->route('user.login')->with('error', 'Unsupported social provider.');
        }

        if (! $this->configureProvider($provider)) {
            return redirect()->route('user.login')->with('error', 'Social login is not configured.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('user.login')->with('error', 'Social login failed. Please try again.');
        }

        $email = $socialUser->getEmail();
        if (! $email) {
            return redirect()->route('user.login')->with('error', 'Could not retrieve email from social provider.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $nameParts = explode(' ', trim($socialUser->getName() ?? $email), 2);
            $firstname = $nameParts[0];
            $lastname  = $nameParts[1] ?? '';

            $base     = Str::slug($firstname . ($lastname ? $lastname[0] : ''));
            $username = $base;
            $i = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $i++;
            }

            $user = User::create([
                'firstname'      => $firstname,
                'lastname'       => $lastname,
                'email'          => $email,
                'username'       => $username,
                'password'       => Str::random(32),
                'coin_balance'   => 0,
                'status'         => 1,
                'email_verified' => 1,
                'phone_verified' => 0,
                'kyc_status'     => 0,
            ]);
        }

        if ($user->status == 0) {
            return redirect()->route('user.login')
                ->with('error', 'Your account has been suspended.');
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        $this->recordSession($request, $user->id);

        return redirect()->intended(route('user.dashboard'));
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
