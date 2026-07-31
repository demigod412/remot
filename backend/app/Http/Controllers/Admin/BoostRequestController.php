<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoostRequest;
use App\Models\UserNotification;
use App\Services\CoinService;
use Illuminate\Http\Request;

class BoostRequestController extends Controller
{
    public function index()
    {
        $pending = BoostRequest::with(['user', 'boostable'])
            ->where('status', 0)
            ->latest()
            ->get();

        $recent = BoostRequest::with(['user', 'boostable'])
            ->where('status', '!=', 0)
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.boost-requests.index', compact('pending', 'recent'));
    }

    public function approve(int $id)
    {
        $req      = BoostRequest::where('status', 0)->findOrFail($id);
        $boostable = $req->boostable;

        if (! $boostable) {
            $req->update(['status' => 2, 'rejection_reason' => 'Associated item no longer exists.']);
            CoinService::credit($req->user, $req->coins_paid, 'boost_refund', 'Boost refund: item deleted');
            return back()->with('error', 'Item not found. Coins refunded.');
        }

        $from  = ($boostable->is_featured && $boostable->featured_until?->isFuture())
            ? $boostable->featured_until
            : now();

        $boostable->update([
            'is_featured'   => true,
            'featured_until' => $from->addDays($req->days),
        ]);

        $req->update(['status' => 1, 'approved_at' => now()]);

        UserNotification::notify(
            $req->user_id,
            'boost_approved',
            'Boost Approved',
            'Your boost request was approved. "' . ($boostable->title ?? 'Item') . '" is now featured for ' . $req->days . ' day(s).',
            null,
            'star'
        );

        return back()->with('success', "Boost approved. Featured for {$req->days} day(s).");
    }

    public function reject(Request $request, int $id)
    {
        $req = BoostRequest::where('status', 0)->findOrFail($id);

        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Refund coins to employer
        CoinService::credit(
            $req->user,
            $req->coins_paid,
            'boost_refund',
            'Boost request rejected — coins refunded'
        );

        $req->update([
            'status'           => 2,
            'rejection_reason' => $request->rejection_reason,
        ]);

        UserNotification::notify(
            $req->user_id,
            'boost_rejected',
            'Boost Request Rejected',
            'Your boost request was rejected and ' . number_format($req->coins_paid, 0) . ' ' . coinSymbol() . ' refunded.',
            null,
            'x-circle'
        );

        return back()->with('success', 'Boost rejected and coins refunded to user.');
    }
}
