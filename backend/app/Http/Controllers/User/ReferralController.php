<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ReferralEarning;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();

        $stats = [
            'total_referred' => $user->referredUsers()->count(),
            'total_earned'   => $user->referralEarnings()->sum('coins_earned'),
            'pending'        => 0,
        ];

        $referralLink = url('/dashboard/register?ref=' . $user->username);

        return view('user.referral.index', compact('user', 'stats', 'referralLink'));
    }

    public function referred()
    {
        $user = Auth::guard('web')->user();
        $referred = $user->referredUsers()->latest()->paginate(config('jobstation.per_page'));
        return view('user.referral.referred', compact('referred'));
    }

    public function earnings()
    {
        $user = Auth::guard('web')->user();
        $earnings = $user->referralEarnings()
            ->with('referredUser')
            ->latest()
            ->paginate(config('jobstation.per_page'));

        return view('user.referral.earnings', compact('earnings'));
    }
}
