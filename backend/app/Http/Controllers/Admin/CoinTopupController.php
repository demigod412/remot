<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\CoinTopup;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoinTopupController extends Controller
{
    public function index(Request $request)
    {
        $query = CoinTopup::with(['user', 'channel', 'package']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) =>
                      $u->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                  );
            });
        }

        $topups = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();

        $stats = [
            'total'      => CoinTopup::count(),
            'pending'    => CoinTopup::where('status', 2)->count(),
            'successful' => CoinTopup::where('status', 1)->count(),
            'initiated'  => CoinTopup::where('status', 0)->count(),
        ];

        return view('admin.topups.index', compact('topups', 'stats'));
    }

    public function show(int $id)
    {
        $topup = CoinTopup::with(['user', 'channel', 'package'])->findOrFail($id);
        return view('admin.topups.show', compact('topup'));
    }

    public function approve(Request $request, int $id)
    {
        $topup = CoinTopup::with('user')->findOrFail($id);

        if ($topup->status === 1) {
            return back()->with('error', 'This top-up is already approved.');
        }

        $data = $request->validate([
            'admin_note'     => ['nullable', 'string', 'max:255'],
            'coins_credited' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($topup, $data) {
            $coins = (float) $data['coins_credited'];
            $user  = $topup->user;

            $user->increment('coin_balance', $coins);
            $user->refresh();

            LedgerEntry::create([
                'user_id'       => $user->id,
                'coins'         => $coins,
                'fee'           => 0,
                'balance_after' => $user->coin_balance,
                'entry_type'    => '+',
                'reference'     => $topup->reference,
                'description'   => 'Coin top-up approved (ref: ' . $topup->reference . ')',
                'category'      => 'topup',
            ]);

            $topup->update([
                'status'         => 1,
                'coins_credited' => $coins,
                'admin_note'     => $data['admin_note'],
            ]);
        });

        AdminNotification::notify(
            Auth::guard('admin')->id(),
            'Top-up Approved',
            "Top-up #{$topup->reference} approved for {$topup->user?->username}.",
            'success',
            route('admin.topups.show', $topup->id)
        );

        if ($topup->user) {
            NotifyService::send($topup->user, 'TOPUP_APPROVED', [
                'coins'     => number_format($data['coins_credited'], 0),
                'reference' => $topup->reference,
            ]);
        }

        return back()->with('success', 'Top-up approved and coins credited.');
    }

    public function reject(Request $request, int $id)
    {
        $topup = CoinTopup::findOrFail($id);

        if ($topup->status === 1) {
            return back()->with('error', 'Cannot reject an already approved top-up.');
        }

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:255'],
        ]);

        $topup->update([
            'status'     => 3,
            'admin_note' => $data['admin_note'],
        ]);

        $topup->load('user');
        if ($topup->user) {
            NotifyService::send($topup->user, 'TOPUP_REJECTED', [
                'reference' => $topup->reference,
                'reason'    => $data['admin_note'],
            ]);
        }

        return back()->with('success', 'Top-up rejected.');
    }
}
