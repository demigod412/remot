<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Cashout;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotifyService;
use App\Services\Payout\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashoutController extends Controller
{
    public function index(Request $request)
    {
        $query = Cashout::with(['user', 'payoutMethod']);

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

        $cashouts = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();

        $stats = [
            'total'    => Cashout::count(),
            'pending'  => Cashout::where('status', 0)->count(),
            'approved' => Cashout::where('status', 1)->count(),
            'rejected' => Cashout::where('status', 2)->count(),
        ];

        return view('admin.cashouts.index', compact('cashouts', 'stats'));
    }

    public function show(int $id)
    {
        $cashout = Cashout::with(['user', 'payoutMethod'])->findOrFail($id);
        return view('admin.cashouts.show', compact('cashout'));
    }

    public function approve(Request $request, int $id)
    {
        $cashout = Cashout::with(['user', 'payoutMethod'])->findOrFail($id);

        if ($cashout->status !== 0) {
            return back()->with('error', 'This cashout has already been processed.');
        }

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:255'],
        ]);

        $method = $cashout->payoutMethod;

        // Auto-disburse if the payout method has a gateway driver configured
        if ($method?->driver) {
            try {
                $gatewayRef = PayoutService::disburse($cashout);

                // Mark as disbursing (status 3) — webhook will set to 1 when confirmed
                $cashout->update([
                    'status'            => 3,
                    'gateway_reference' => $gatewayRef,
                    'admin_note'        => $data['admin_note'] ?? null,
                ]);

                AdminNotification::notify(
                    Auth::guard('admin')->id(),
                    'Cashout Disbursing',
                    "Cashout #{$cashout->reference} sent to PayIn for {$cashout->user?->username}. Ref: {$gatewayRef}",
                    'info',
                    route('admin.cashouts.show', $cashout->id)
                );

                return back()->with('success', 'Disbursement initiated via PayIn. Status will update automatically when confirmed.');
            } catch (\Throwable $e) {
                Log::error('Auto-disbursement failed', ['cashout' => $id, 'error' => $e->getMessage()]);
                return back()->with('error', 'Disbursement failed: ' . $e->getMessage());
            }
        }

        // Manual approval — no gateway driver, admin will send money themselves
        DB::transaction(function () use ($cashout, $data) {
            $cashout->update([
                'status'     => 1,
                'admin_note' => $data['admin_note'],
            ]);

            // NO ledger row here, deliberately.
            //
            // The money already left when the worker submitted the request:
            // EarningsService::withdraw() locked the user row, decremented usd_balance
            // and wrote the one '-' row for this reference. Approval is a status change,
            // not a second movement.
            //
            // This used to write another '-' row for the same amount and reference, so
            // every withdrawal appeared twice in the ledger and any sum over cashout
            // rows came out at double the real figure. The balance itself was only ever
            // debited once, so no money was lost — but the history lied about it.
            //
            // The approval itself is recorded on the cashout record (status, admin_note)
            // and in the admin activity log, which is where an audit trail belongs.
        });

        AdminNotification::notify(
            Auth::guard('admin')->id(),
            'Cashout Approved',
            "Cashout #{$cashout->reference} approved for {$cashout->user?->username}.",
            'success',
            route('admin.cashouts.show', $cashout->id)
        );

        if ($cashout->user) {
            NotifyService::send($cashout->user, 'CASHOUT_APPROVED', [
                'coins'     => number_format($cashout->net_coins_deducted, 0),
                'reference' => $cashout->reference,
            ]);
        }

        ActivityLogger::logMoney('cashout.approve', $cashout, (float) $cashout->net_coins_deducted, $cashout->user_id, [
            'reference' => $cashout->reference,
        ]);

        return back()->with('success', 'Cashout approved.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Cashout::with(['user', 'payoutMethod']);

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

        $cashouts = $query->latest()->get();

        $statusMap = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected', 3 => 'Processing'];

        $filename = 'cashouts_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($cashouts, $statusMap) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'User', 'Email', 'Method', 'Coins', 'Fee', 'Payout Amount', 'Currency', 'Status', 'Date']);

            foreach ($cashouts as $c) {
                fputcsv($handle, [
                    $c->reference,
                    $c->user?->fullname,
                    $c->user?->email,
                    $c->payoutMethod?->name,
                    $c->coin_amount,
                    $c->fee,
                    $c->payout_amount,
                    $c->payout_currency,
                    $statusMap[$c->status] ?? $c->status,
                    $c->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function reject(Request $request, int $id)
    {
        $cashout = Cashout::with('user')->findOrFail($id);

        if ($cashout->status !== 0) {
            return back()->with('error', 'This cashout has already been processed.');
        }

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:255'],
        ]);

        try {
        DB::transaction(function () use ($cashout, $data) {
            // Return the USD to the worker's earnings balance, not to coins.
            //
            // reverseWithdrawal() reads the amount from the original debit row and
            // refuses to run at all if there is no matching USD withdrawal for this
            // reference. That turns a silent over-refund into a visible error, which is
            // the right trade for money leaving the platform.
            $user = $cashout->user;
            if ($user) {
                \App\Services\EarningsService::reverseWithdrawal(
                    $user,
                    (float) $cashout->net_coins_deducted,
                    $cashout->reference,
                    'Cashout refunded (rejected)'
                );
            }

            $cashout->update([
                'status'     => 2,
                'admin_note' => $data['admin_note'],
            ]);

            ActivityLogger::logMoney('cashout.reject', $cashout, (float) $cashout->net_coins_deducted, $cashout->user_id, [
                'reference'  => $cashout->reference,
                'admin_note' => $data['admin_note'],
                'refunded'   => true,
            ]);
        });
        } catch (\RuntimeException $e) {
            // Nothing was committed: the whole transaction rolled back, so the cashout
            // is still pending and no money moved. Surface the reason instead of a 500.
            Log::warning('Cashout rejection refused', [
                'cashout' => $cashout->id,
                'reason'  => $e->getMessage(),
            ]);

            return back()->with('error', 'Could not refund this cashout: ' . $e->getMessage());
        }

        if ($cashout->user) {
            NotifyService::send($cashout->user, 'CASHOUT_REJECTED', [
                'coins'     => number_format($cashout->net_coins_deducted, 0),
                'reference' => $cashout->reference,
                'reason'    => $data['admin_note'],
            ]);
        }

        return back()->with('success', 'Cashout rejected and coins refunded.');
    }
}
