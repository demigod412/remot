<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\ReferralEarning;
use App\Models\User;
use App\Models\UserSession;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('user.auth.register');
    }

    public function register(Request $request)
    {
        $gs = gs();

        $rules = [
            'firstname'    => ['required', 'string', 'max:60'],
            'lastname'     => ['required', 'string', 'max:60'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'username'     => ['required', 'alpha_num', 'min:4', 'max:30', 'unique:users,username'],
            'password'     => ['required', 'min:6'],
            'account_type' => ['sometimes', 'in:1,2'],
        ];

        if ($gs->secure_password) {
            $rules['password'][] = Password::min(8)->mixedCase()->numbers()->symbols();
        }

        if ($gs->agree) {
            $rules['agree'] = ['accepted'];
        }

        $data = $request->validate($rules);

        if (! verifyRecaptcha($request->input('g-recaptcha-response'))) {
            return back()->withInput()
                ->withErrors(['captcha' => 'Please complete the reCAPTCHA verification and try again.']);
        }

        // Referral
        $referredBy = null;
        $refCode = $request->input('ref') ?? $request->session()->get('ref_code');
        if ($refCode) {
            $referrer = User::where('username', $refCode)->first();
            if ($referrer) {
                $referredBy = $referrer->id;
            }
        }

        DB::transaction(function () use ($data, $request, $referredBy, $gs, &$user) {
            $user = User::create([
                'firstname'      => $data['firstname'],
                'lastname'       => $data['lastname'],
                'email'          => $data['email'],
                'username'       => $data['username'],
                'password'       => $data['password'],
                'referred_by'    => $referredBy ?? 0, // 0 = no referrer (column is NOT NULL, default 0)
                'account_type'   => (int) ($request->input('account_type', 1)),
                'coin_balance'   => 0,
                'status'         => 1,
                'email_verified' => 0,
                'phone_verified' => 0,
                'kyc_status'     => 0,
            ]);

            // Referral commission
            if ($referredBy && $gs->ref_commission > 0) {
                ReferralEarning::create([
                    'earner_id'        => $referredBy,
                    'referred_user_id' => $user->id,
                    'coins_earned'     => $gs->ref_commission,
                    'original_amount'  => $gs->ref_commission,
                ]);
                // Credit referrer immediately on new user sign up
                $referrerUser = \App\Models\User::find($referredBy);
                if ($referrerUser) {
                    $referrerUser->increment('coin_balance', $gs->ref_commission);
                    $referrerUser->refresh();
                    \App\Models\LedgerEntry::create([
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

        $request->session()->forget('ref_code');

        NotifyService::send($user, 'WELCOME');

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $steps = [];
        if ($gs->email_verification) {
            $steps[] = 'email';
        }
        if ($gs->phone_verification) {
            $steps[] = 'phone';
        }

        if (count($steps)) {
            $request->session()->put('auth_steps', $steps);
            return redirect()->route('user.authorization');
        }

        // Record session
        $this->recordSession($request, $user->id);

        return redirect()->route('user.dashboard')->with('success', 'Welcome to ' . ($gs->site_name ?? 'Job Station') . '!');
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
