<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\WorkCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeneralController extends Controller
{
    public function settings(Request $request): JsonResponse
    {
        $gs = gs();

        return response()->json([
            'site_name'          => $gs->site_name,
            'coin_name'          => $gs->coin_name,
            'coin_symbol'        => $gs->coin_symbol,
            'registration'       => (bool) $gs->registration,
            'email_verification' => (bool) $gs->email_verification,
            'phone_verification' => (bool) $gs->phone_verification,
            'kyc_required'       => (bool) ($gs->kyc_required ?? false),
            'secure_password'    => (bool) $gs->secure_password,
            'min_cashout'        => (float) ($gs->min_cashout ?? 0),
            'cashout_charge'     => (float) ($gs->cashout_charge ?? 0),
            'cashout_charge_type'=> $gs->cashout_charge_type ?? 'percent',
            'ref_commission'     => (float) ($gs->ref_commission ?? 0),
        ]);
    }
}
