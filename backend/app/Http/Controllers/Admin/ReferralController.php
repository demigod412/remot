<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralEarning;
use App\Models\User;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $query = ReferralEarning::with(['earner', 'referredUser']);

        if ($search = $request->input('search')) {
            $query->whereHas('earner', fn ($q) =>
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            )->orWhereHas('referredUser', fn ($q) =>
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $earnings = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();

        $stats = [
            'total_earnings' => ReferralEarning::sum('coins_earned'),
            'total_referrals' => ReferralEarning::count(),
            'top_referrers'  => User::withCount('referredUsers')
                ->orderByDesc('referred_users_count')
                ->limit(5)
                ->get(),
        ];

        return view('admin.referrals.index', compact('earnings', 'stats'));
    }
}
