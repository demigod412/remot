<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    private function user(): User
    {
        return Auth::guard('web')->user();
    }

    /**
     * Returns [commission_amount, worker_payout] based on current global rate.
     */
    private static function calcCommission(float $amount): array
    {
        $rate       = (float) gs()->contract_commission;
        $commission = round($amount * $rate / 100, 8);
        $payout     = round($amount - $commission, 8);
        return [$commission, $payout];
    }

    /**
     * Coins still held in escrow = contract amount minus already-approved
     * (paid-out) milestone amounts. For non-milestone contracts this is the
     * full amount; for partially-completed milestone contracts it is the remainder.
     */
    private static function refundableEscrow(Contract $contract): float
    {
        $paidOut = (float) $contract->milestones()->where('status', 2)->sum('amount');

        return max(0, (float) $contract->amount - $paidOut);
    }

    // ── Employer: Send Offer ──────────────────────────────────────

    public function create(Request $request)
    {
        $worker = null;
        if ($request->filled('worker')) {
            $worker = User::where('username', $request->worker)
                ->orWhere('email', $request->worker)
                ->first();
        }
        $prefillTitle = $request->input('title', '');
        return view('user.contracts.create', compact('worker', 'prefillTitle'));
    }

    public function store(Request $request)
    {
        $employer = $this->user();

        $hasMilestones = $request->filled('milestones') && is_array($request->input('milestones'));

        $data = $request->validate([
            'worker_identifier'    => ['required', 'string'],
            'title'                => ['required', 'string', 'max:160'],
            'description'          => ['required', 'string', 'min:20'],
            'amount'               => ['required_without:milestones', 'nullable', 'numeric', 'min:1'],
            'deadline_at'          => ['nullable', 'date', 'after:today'],
            'milestones'           => ['nullable', 'array', 'max:20'],
            'milestones.*.title'   => ['required_with:milestones', 'string', 'max:160'],
            'milestones.*.amount'  => ['required_with:milestones', 'numeric', 'min:1'],
            'milestones.*.description' => ['nullable', 'string', 'max:1000'],
            'milestones.*.deadline_at' => ['nullable', 'date', 'after:today'],
        ]);

        $worker = User::where('username', $data['worker_identifier'])
            ->orWhere('email', $data['worker_identifier'])
            ->first();

        if (! $worker) {
            return back()->withInput()->with('error', 'Worker not found. Check the username or email.');
        }

        if ($worker->id === $employer->id) {
            return back()->withInput()->with('error', 'You cannot send a contract to yourself.');
        }

        $totalAmount = $hasMilestones
            ? array_sum(array_column($data['milestones'], 'amount'))
            : (float) $data['amount'];

        if (! CoinService::hasBalance($employer, $totalAmount)) {
            return back()->withInput()->with('error', "Insufficient balance. You need {$totalAmount} coins.");
        }

        try {
            $contract = DB::transaction(function () use ($employer, $worker, $data, $hasMilestones, $totalAmount) {
                $contract = Contract::create([
                    'employer_id' => $employer->id,
                    'worker_id'   => $worker->id,
                    'title'       => $data['title'],
                    'description' => $data['description'],
                    'amount'      => $totalAmount,
                    'deadline_at' => $data['deadline_at'] ?? null,
                    'status'      => 0,
                    'reference'   => Contract::generateReference(),
                ]);

                if ($hasMilestones) {
                    foreach ($data['milestones'] as $i => $ms) {
                        ContractMilestone::create([
                            'contract_id' => $contract->id,
                            'sort_order'  => $i,
                            'title'       => $ms['title'],
                            'description' => $ms['description'] ?? null,
                            'amount'      => $ms['amount'],
                            'deadline_at' => $ms['deadline_at'] ?? null,
                        ]);
                    }
                }

                // Hold the full budget in escrow at offer time so it's guaranteed for the worker.
                CoinService::deduct($employer, $totalAmount, $contract->reference, 'work_spend', 'Contract escrow held: ' . $contract->title);

                return $contract;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not hold the contract budget — please try again.');
        }

        \App\Models\UserNotification::notify(
            $worker->id,
            'contract_received',
            'New Contract Offer',
            $employer->fullname . ' sent you a contract: "' . $data['title'] . '" for ' . number_format($totalAmount, 0) . ' ' . coinSymbol() . '.',
            route('user.contracts.show', $contract->id),
            'file-input'
        );

        return redirect()->route('user.contracts.sent')
            ->with('success', 'Contract offer sent to ' . ($worker->fullname ?: $worker->username) . '. Coins are held in escrow.');
    }

    // ── Employer: Manage Sent Contracts ──────────────────────────

    public function sent(Request $request)
    {
        $user  = $this->user();
        $query = $user->contractsAsEmployer()->with('worker');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $contracts = $query->latest()->paginate(config('jobstation.per_page'));

        return view('user.contracts.sent', compact('contracts'));
    }

    public function cancel(int $id)
    {
        $employer = $this->user();
        $contract = Contract::where('id', $id)
            ->where('employer_id', $employer->id)
            ->whereIn('status', [0, 1]) // only offered or accepted (not yet submitted)
            ->firstOrFail();

        $refund = self::refundableEscrow($contract);

        DB::transaction(function () use ($employer, $contract, $refund) {
            // Escrow is held from offer time, so refund the unspent remainder.
            if ($refund > 0) {
                CoinService::credit(
                    $employer,
                    $refund,
                    $contract->reference,
                    'work_refund',
                    'Contract cancelled – refund: ' . $contract->title
                );
            }

            $contract->update(['status' => 5, 'employer_note' => 'Cancelled by employer.']);
        });

        return back()->with('success', 'Contract cancelled' . ($refund > 0 ? ' and ' . number_format($refund, 0) . ' coins refunded.' : '.'));
    }

    public function approve(int $id)
    {
        $employer = $this->user();
        $contract = Contract::where('id', $id)
            ->where('employer_id', $employer->id)
            ->where('status', 2) // submitted
            ->firstOrFail();

        DB::transaction(function () use ($contract) {
            [$commission, $workerPayout] = self::calcCommission((float) $contract->amount);

            // Pay the worker their net amount
            CoinService::credit(
                $contract->worker,
                $workerPayout,
                $contract->reference,
                'work_earn',
                'Contract payment (after ' . gs()->contract_commission . '% commission): ' . $contract->title
            );

            $contract->update([
                'status'           => 3,
                'completed_at'     => now(),
                'commission_amount'=> $commission,
                'worker_payout'    => $workerPayout,
            ]);
        });

        \App\Models\UserNotification::notify(
            $contract->worker_id,
            'contract_completed',
            'Contract Completed',
            'Your contract "' . $contract->title . '" was approved. Payment released!',
            route('user.contracts.show', $contract->id),
            'check-circle'
        );

        return back()->with('success', 'Contract marked complete. Payment released to worker.');
    }

    public function dispute(Request $request, int $id)
    {
        $employer = $this->user();
        $contract = Contract::where('id', $id)
            ->where('employer_id', $employer->id)
            ->where('status', 2) // submitted
            ->firstOrFail();

        $data = $request->validate([
            'employer_note' => ['required', 'string', 'max:1000'],
        ]);

        $contract->update(['status' => 6, 'employer_note' => $data['employer_note']]);

        return back()->with('success', 'Dispute raised. Our team will review this contract.');
    }

    // ── Worker: Received Contracts ────────────────────────────────

    public function received(Request $request)
    {
        $user  = $this->user();
        $query = $user->contractsAsWorker()->with('employer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $contracts = $query->latest()->paginate(config('jobstation.per_page'));

        return view('user.contracts.received', compact('contracts'));
    }

    public function show(int $id)
    {
        $user     = $this->user();
        $contract = Contract::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('employer_id', $user->id)
                  ->orWhere('worker_id', $user->id);
            })
            ->with(['employer', 'worker', 'milestones'])
            ->firstOrFail();

        $isEmployer = $contract->employer_id === $user->id;

        return view('user.contracts.show', compact('contract', 'isEmployer'));
    }

    public function accept(int $id)
    {
        $worker   = $this->user();
        $contract = Contract::where('id', $id)
            ->where('worker_id', $worker->id)
            ->where('status', 0)
            ->firstOrFail();

        // Escrow was already held when the offer was created — just activate it.
        $contract->update(['status' => 1, 'accepted_at' => now()]);

        \App\Models\UserNotification::notify(
            $contract->employer_id,
            'contract_accepted',
            'Contract Accepted',
            $worker->fullname . ' accepted your contract: "' . $contract->title . '".',
            route('user.contracts.show', $contract->id),
            'check-circle'
        );

        return back()->with('success', 'Contract accepted. Coins are held in escrow until completion.');
    }

    public function decline(Request $request, int $id)
    {
        $worker   = $this->user();
        $contract = Contract::where('id', $id)
            ->where('worker_id', $worker->id)
            ->where('status', 0)
            ->firstOrFail();

        $data = $request->validate([
            'declined_reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($contract, $data) {
            // Release the held escrow back to the employer.
            $refund = self::refundableEscrow($contract);
            if ($refund > 0) {
                CoinService::credit($contract->employer, $refund, $contract->reference, 'work_refund',
                    'Contract declined – escrow released: ' . $contract->title);
            }
            $contract->update([
                'status'          => 4,
                'declined_reason' => $data['declined_reason'] ?? null,
            ]);
        });

        \App\Models\UserNotification::notify(
            $contract->employer_id,
            'contract_declined',
            'Contract Declined',
            $worker->fullname . ' declined your contract: "' . $contract->title . '".',
            route('user.contracts.show', $contract->id),
            'x-circle'
        );

        return back()->with('success', 'Contract declined.');
    }

    public function submit(Request $request, int $id)
    {
        $worker   = $this->user();
        $contract = Contract::where('id', $id)
            ->where('worker_id', $worker->id)
            ->where('status', 1)
            ->firstOrFail();

        $data = $request->validate([
            'worker_note' => ['required', 'string', 'min:20', 'max:3000'],
            'proof_file'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt,zip,xls,xlsx,ppt,pptx', 'max:10240'],
        ]);

        $proofFile = $contract->proof_file;
        if ($request->hasFile('proof_file')) {
            if ($proofFile) {
                removePrivateFile('contract-proofs', $proofFile);
            }
            $proofFile = uploadPrivateFile($request->file('proof_file'), 'contract-proofs');
        }

        $contract->update([
            'status'       => 2,
            'worker_note'  => $data['worker_note'],
            'proof_file'   => $proofFile,
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Work submitted. Waiting for employer approval.');
    }

    // ── Milestones ────────────────────────────────────────────────

    public function submitMilestone(Request $request, int $contractId, int $milestoneId)
    {
        $worker   = $this->user();
        // status can be 1 (Accepted, no milestone submitted yet) or 2 (Submitted,
        // because an earlier milestone already flipped it) — either is a valid
        // state to submit another pending milestone from.
        $contract = Contract::where('id', $contractId)->where('worker_id', $worker->id)->whereIn('status', [1, 2])->firstOrFail();
        $ms       = ContractMilestone::where('id', $milestoneId)->where('contract_id', $contract->id)->where('status', 0)->firstOrFail();

        $data = $request->validate([
            'worker_note' => ['required', 'string', 'min:10', 'max:3000'],
            'proof_file'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt,zip,xls,xlsx,ppt,pptx', 'max:10240'],
        ]);

        $proofFile = null;
        if ($request->hasFile('proof_file')) {
            $proofFile = uploadPrivateFile($request->file('proof_file'), 'contract-proofs');
        }

        $ms->update([
            'status'       => 1,
            'worker_note'  => $data['worker_note'],
            'proof_file'   => $proofFile,
            'submitted_at' => now(),
        ]);

        // Advance overall contract to "submitted" if not already
        if ($contract->status === 1) {
            $contract->update(['status' => 2, 'submitted_at' => now()]);
        }

        \App\Models\UserNotification::notify(
            $contract->employer_id,
            'contract_milestone_submitted',
            'Milestone Submitted',
            $worker->fullname . ' submitted milestone "' . $ms->title . '" for: ' . $contract->title,
            route('user.contracts.show', $contract->id),
            'upload'
        );

        return back()->with('success', 'Milestone submitted. Waiting for employer approval.');
    }

    public function approveMilestone(int $contractId, int $milestoneId)
    {
        $employer = $this->user();
        $contract = Contract::where('id', $contractId)->where('employer_id', $employer->id)->firstOrFail();
        $ms       = ContractMilestone::where('id', $milestoneId)->where('contract_id', $contract->id)->where('status', 1)->firstOrFail();

        DB::transaction(function () use ($contract, $ms) {
            [$commission, $workerPayout] = self::calcCommission((float) $ms->amount);

            CoinService::credit(
                $contract->worker,
                $workerPayout,
                $contract->reference . '-M' . $ms->id,
                'work_earn',
                'Milestone payment: ' . $ms->title . ' (' . gs()->contract_commission . '% fee)'
            );

            $ms->update([
                'status'            => 2,
                'commission_amount' => $commission,
                'worker_payout'     => $workerPayout,
                'completed_at'      => now(),
            ]);

            // Complete the contract if all milestones are approved
            $allApproved = $contract->milestones()->where('status', '!=', 2)->doesntExist();
            if ($allApproved) {
                $totalCommission = $contract->milestones()->sum('commission_amount');
                $totalPayout     = $contract->milestones()->sum('worker_payout');
                $contract->update([
                    'status'            => 3,
                    'completed_at'      => now(),
                    'commission_amount' => $totalCommission,
                    'worker_payout'     => $totalPayout,
                ]);
            }
        });

        \App\Models\UserNotification::notify(
            $contract->worker_id,
            'contract_milestone_approved',
            'Milestone Approved',
            'Milestone "' . $ms->title . '" approved. Payment released!',
            route('user.contracts.show', $contract->id),
            'check-circle'
        );

        return back()->with('success', 'Milestone approved and payment released.');
    }

    public function disputeMilestone(Request $request, int $contractId, int $milestoneId)
    {
        $employer = $this->user();
        $contract = Contract::where('id', $contractId)->where('employer_id', $employer->id)->firstOrFail();
        $ms       = ContractMilestone::where('id', $milestoneId)->where('contract_id', $contract->id)->where('status', 1)->firstOrFail();

        $data = $request->validate(['employer_note' => ['required', 'string', 'max:1000']]);

        $ms->update(['status' => 3]);
        $contract->update(['status' => 6, 'employer_note' => $data['employer_note']]);

        return back()->with('success', 'Dispute raised for this milestone. Our team will review.');
    }
}
