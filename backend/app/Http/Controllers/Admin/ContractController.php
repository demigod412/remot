<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\CoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with(['employer', 'worker']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $contracts = $query->latest()->paginate(config('jobstation.per_page'))->withQueryString();

        $stats = [
            'total'      => Contract::count(),
            'active'     => Contract::whereIn('status', [1, 2])->count(),
            'completed'  => Contract::where('status', 3)->count(),
            'disputed'   => Contract::where('status', 6)->count(),
            'commission' => Contract::where('status', 3)->sum('commission_amount'),
        ];

        return view('admin.contracts.index', compact('contracts', 'stats'));
    }

    public function show(int $id)
    {
        $contract = Contract::with(['employer', 'worker'])->findOrFail($id);
        return view('admin.contracts.show', compact('contract'));
    }

    public function resolve(Request $request, int $id)
    {
        $contract = Contract::where('id', $id)->where('status', 6)->firstOrFail();

        $data = $request->validate([
            'resolution' => ['required', 'in:pay_worker,refund_employer'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($contract, $data) {
            if ($data['resolution'] === 'pay_worker') {
                $rate       = (float) gs()->contract_commission;
                $commission = round((float) $contract->amount * $rate / 100, 8);
                $payout     = round((float) $contract->amount - $commission, 8);

                CoinService::credit(
                    $contract->worker,
                    $payout,
                    $contract->reference,
                    'work_earn',
                    'Contract dispute resolved – paid to worker: ' . $contract->title
                );
                $contract->update([
                    'status'            => 3,
                    'completed_at'      => now(),
                    'commission_amount' => $commission,
                    'worker_payout'     => $payout,
                    'employer_note'     => ($data['admin_note'] ?? '') . ' [Admin resolved: paid worker]',
                ]);
            } else {
                $paidOut = (float) $contract->milestones()->where('status', 2)->sum('amount');
                CoinService::credit(
                    $contract->employer,
                    max(0, (float) $contract->amount - $paidOut),
                    $contract->reference,
                    'work_refund',
                    'Contract dispute resolved – refunded to employer: ' . $contract->title
                );
                $contract->update([
                    'status'        => 5,
                    'employer_note' => ($data['admin_note'] ?? '') . ' [Admin resolved: refunded employer]',
                ]);
            }
        });

        return back()->with('success', 'Dispute resolved and coins transferred.');
    }

    public function forceCancel(Request $request, int $id)
    {
        $contract = Contract::whereIn('status', [0, 1, 2, 6])->findOrFail($id);

        DB::transaction(function () use ($contract) {
            // Escrow is held from offer time → refund the unspent remainder
            // (contract amount minus already-approved milestone payouts).
            $paidOut = (float) $contract->milestones()->where('status', 2)->sum('amount');
            $refund  = max(0, (float) $contract->amount - $paidOut);
            if ($refund > 0) {
                CoinService::credit(
                    $contract->employer,
                    $refund,
                    $contract->reference,
                    'work_refund',
                    'Contract force-cancelled by admin – refund: ' . $contract->title
                );
            }
            $contract->update(['status' => 5, 'employer_note' => 'Force-cancelled by admin.']);
        });

        return back()->with('success', 'Contract cancelled and employer refunded.');
    }
}
