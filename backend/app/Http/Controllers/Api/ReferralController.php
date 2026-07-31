<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralEarning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    /** GET /api/v1/referral */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $gs   = gs();

        $referralLink = url('/register?ref=' . $user->username);

        return response()->json([
            'referral_link'    => $referralLink,
            'ref_code'         => $user->username,
            'total_referred'   => $user->referredUsers()->count(),
            'total_earned'     => (float) $user->referralEarnings()->sum('coins_earned'),
            'commission_rate'  => (float) ($gs->ref_commission ?? 0),
        ]);
    }

    /** GET /api/v1/referral/referred */
    public function referred(Request $request): JsonResponse
    {
        $users = $request->user()
            ->referredUsers()
            ->select('id', 'firstname', 'lastname', 'username', 'created_at')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id'        => $u->id,
                'username'  => $u->username,
                'name'      => $u->firstname . ' ' . $u->lastname,
                'joined_at' => $u->created_at,
            ]),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /** GET /api/v1/referral/earnings */
    public function earnings(Request $request): JsonResponse
    {
        $earnings = ReferralEarning::where('earner_id', $request->user()->id)
            ->with(['referredUser:id,username,firstname,lastname'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $earnings->map(fn ($e) => [
                'id'              => $e->id,
                'coins_earned'    => (float) $e->coins_earned,
                'referred_user'   => $e->referredUser ? [
                    'username' => $e->referredUser->username,
                    'name'     => $e->referredUser->firstname . ' ' . $e->referredUser->lastname,
                ] : null,
                'created_at'      => $e->created_at,
            ]),
            'meta' => [
                'current_page' => $earnings->currentPage(),
                'last_page'    => $earnings->lastPage(),
                'total'        => $earnings->total(),
            ],
        ]);
    }
}
