<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\CoinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ContractController extends Controller
{
    private function calcCommission(float $amount): array
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
    private function refundableEscrow(Contract $contract): float
    {
        $paidOut = (float) $contract->milestones()->where('status', 2)->sum('amount');
        return max(0, (float) $contract->amount - $paidOut);
    }

    /** GET /api/contracts — all my contracts (sent + received) */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $contracts = Contract::with(['employer', 'worker', 'milestones'])
            ->where(fn ($q) => $q->where('employer_id', $user->id)->orWhere('worker_id', $user->id))
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $contracts->map(fn ($c) => $this->contractResource($c, asEmployer: $c->employer_id === $user->id)),
            'meta' => ['current_page' => $contracts->currentPage(), 'last_page' => $contracts->lastPage(), 'total' => $contracts->total()],
        ]);
    }

    /** GET /api/contracts/sent */
    public function sent(Request $request): JsonResponse
    {
        $contracts = $request->user()->contractsAsEmployer()
            ->with(['worker', 'milestones'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $contracts->map(fn ($c) => $this->contractResource($c, asEmployer: true)),
            'meta' => ['current_page' => $contracts->currentPage(), 'last_page' => $contracts->lastPage(), 'total' => $contracts->total()],
        ]);
    }

    /** GET /api/contracts/received */
    public function received(Request $request): JsonResponse
    {
        $contracts = $request->user()->contractsAsWorker()
            ->with(['employer', 'milestones'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $contracts->map(fn ($c) => $this->contractResource($c, asEmployer: false)),
            'meta' => ['current_page' => $contracts->currentPage(), 'last_page' => $contracts->lastPage(), 'total' => $contracts->total()],
        ]);
    }

    /** GET /api/contracts/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $contract = Contract::where('id', $id)
            ->where(fn ($q) => $q->where('employer_id', $user->id)->orWhere('worker_id', $user->id))
            ->with(['employer', 'worker', 'milestones'])
            ->firstOrFail();

        return response()->json([
            'contract'    => $this->contractResource($contract, asEmployer: $contract->employer_id === $user->id, detailed: true),
            'is_employer' => $contract->employer_id === $user->id,
        ]);
    }

    /** POST /api/contracts — send offer (flat amount or milestone-based) */
    public function store(Request $request): JsonResponse
    {
        $employer = $request->user();

        $hasMilestones = $request->filled('milestones') && is_array($request->input('milestones'));

        $data = $request->validate([
            'worker_identifier'         => ['required', 'string'],
            'title'                     => ['required', 'string', 'max:160'],
            'description'               => ['required', 'string', 'min:20'],
            'amount'                    => ['required_without:milestones', 'nullable', 'numeric', 'min:1'],
            'deadline_at'               => ['nullable', 'date', 'after:today'],
            'milestones'                => ['nullable', 'array', 'max:20'],
            'milestones.*.title'        => ['required_with:milestones', 'string', 'max:160'],
            'milestones.*.amount'       => ['required_with:milestones', 'numeric', 'min:1'],
            'milestones.*.description'  => ['nullable', 'string', 'max:1000'],
            'milestones.*.deadline_at'  => ['nullable', 'date', 'after:today'],
        ]);

        $worker = User::where('username', $data['worker_identifier'])
            ->orWhere('email', $data['worker_identifier'])
            ->first();

        if (! $worker) return response()->json(['message' => 'Worker not found. Check the username or email.'], 422);
        if ($worker->id === $employer->id) return response()->json(['message' => 'You cannot send a contract to yourself.'], 422);

        $totalAmount = $hasMilestones
            ? array_sum(array_column($data['milestones'], 'amount'))
            : (float) $data['amount'];

        if (! CoinService::hasBalance($employer, $totalAmount)) {
            return response()->json(['message' => "Insufficient balance. You need {$totalAmount} coins."], 422);
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
            return response()->json(['message' => 'Could not hold the contract budget — please try again.'], 422);
        }

        UserNotification::notify($worker->id, 'contract_received', 'New Contract Offer',
            $employer->fullname . ' sent you a contract: "' . $data['title'] . '" for ' . number_format($totalAmount, 0) . ' ' . coinSymbol() . '.',
            null, 'file-input');

        return response()->json([
            'message'  => 'Contract offer sent. Coins are held in escrow.',
            'contract' => $this->contractResource($contract->fresh('milestones'), asEmployer: true, detailed: true),
        ], 201);
    }

    /** POST /api/contracts/{id}/accept */
    public function accept(Request $request, int $id): JsonResponse
    {
        $worker   = $request->user();
        $contract = Contract::where('id', $id)->where('worker_id', $worker->id)->where('status', 0)->firstOrFail();

        // Escrow was already held when the offer was created.
        $contract->update(['status' => 1, 'accepted_at' => now()]);

        UserNotification::notify($contract->employer_id, 'contract_accepted', 'Contract Accepted',
            $worker->fullname . ' accepted your contract: "' . $contract->title . '".', null, 'check-circle');

        return response()->json(['message' => 'Contract accepted. Coins are held in escrow until completion.']);
    }

    /** POST /api/contracts/{id}/decline */
    public function decline(Request $request, int $id): JsonResponse
    {
        $worker   = $request->user();
        $contract = Contract::where('id', $id)->where('worker_id', $worker->id)->where('status', 0)->firstOrFail();

        $data = $request->validate(['declined_reason' => ['nullable', 'string', 'max:500']]);

        DB::transaction(function () use ($contract, $data) {
            $refund = $this->refundableEscrow($contract);
            if ($refund > 0) {
                CoinService::credit($contract->employer, $refund, $contract->reference, 'work_refund',
                    'Contract declined – escrow released: ' . $contract->title);
            }
            $contract->update(['status' => 4, 'declined_reason' => $data['declined_reason'] ?? null]);
        });

        UserNotification::notify($contract->employer_id, 'contract_declined', 'Contract Declined',
            $worker->fullname . ' declined your contract: "' . $contract->title . '".', null, 'x-circle');

        return response()->json(['message' => 'Contract declined.']);
    }

    /** POST /api/contracts/{id}/cancel — employer withdraws an offer/active contract before submission */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $employer = $request->user();
        $contract = Contract::where('id', $id)
            ->where('employer_id', $employer->id)
            ->whereIn('status', [0, 1]) // only offered or accepted (not yet submitted)
            ->firstOrFail();

        $refund = $this->refundableEscrow($contract);

        DB::transaction(function () use ($employer, $contract, $refund) {
            if ($refund > 0) {
                CoinService::credit($employer, $refund, $contract->reference, 'work_refund',
                    'Contract cancelled – refund: ' . $contract->title);
            }
            $contract->update(['status' => 5, 'employer_note' => 'Cancelled by employer.']);
        });

        return response()->json([
            'message' => 'Contract cancelled' . ($refund > 0 ? ' and ' . number_format($refund, 0) . ' coins refunded.' : '.'),
        ]);
    }

    /** POST /api/contracts/{id}/submit — worker submits the whole (non-milestone) contract */
    public function submit(Request $request, int $id): JsonResponse
    {
        $worker   = $request->user();
        $contract = Contract::where('id', $id)->where('worker_id', $worker->id)->where('status', 1)->firstOrFail();

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

        UserNotification::notify($contract->employer_id, 'contract_submitted', 'Work Submitted',
            $worker->fullname . ' submitted work for: ' . $contract->title, null, 'upload');

        return response()->json(['message' => 'Work submitted. Waiting for employer approval.']);
    }

    /** POST /api/contracts/{id}/approve */
    public function approve(Request $request, int $id): JsonResponse
    {
        $employer = $request->user();
        $contract = Contract::where('id', $id)->where('employer_id', $employer->id)->where('status', 2)->firstOrFail();

        DB::transaction(function () use ($contract) {
            [$commission, $workerPayout] = $this->calcCommission((float) $contract->amount);
            CoinService::credit($contract->worker, $workerPayout, $contract->reference, 'work_earn',
                'Contract payment (after ' . gs()->contract_commission . '% commission): ' . $contract->title);
            $contract->update(['status' => 3, 'completed_at' => now(), 'commission_amount' => $commission, 'worker_payout' => $workerPayout]);
        });

        UserNotification::notify($contract->worker_id, 'contract_completed', 'Contract Completed',
            'Your contract "' . $contract->title . '" was approved. Payment released!', null, 'check-circle');

        return response()->json(['message' => 'Contract approved. Payment released.']);
    }

    /** POST /api/contracts/{id}/dispute — employer disputes submitted (non-milestone) work */
    public function dispute(Request $request, int $id): JsonResponse
    {
        $employer = $request->user();
        $contract = Contract::where('id', $id)->where('employer_id', $employer->id)->where('status', 2)->firstOrFail();

        $data = $request->validate(['employer_note' => ['required', 'string', 'max:1000']]);

        $contract->update(['status' => 6, 'employer_note' => $data['employer_note']]);

        UserNotification::notify($contract->worker_id, 'contract_disputed', 'Contract Disputed',
            'Your contract "' . $contract->title . '" was disputed by the employer.', null, 'alert-triangle');

        return response()->json(['message' => 'Dispute raised. Our team will review this contract.']);
    }

    /** POST /api/contracts/{id}/milestones/{milestoneId}/submit */
    public function submitMilestone(Request $request, int $id, int $milestoneId): JsonResponse
    {
        $worker   = $request->user();
        // status can be 1 (Accepted, no milestone submitted yet) or 2 (Submitted,
        // because an earlier milestone already flipped it) — either is a valid
        // state to submit another pending milestone from.
        $contract = Contract::where('id', $id)->where('worker_id', $worker->id)->whereIn('status', [1, 2])->firstOrFail();
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

        if ($contract->status === 1) {
            $contract->update(['status' => 2, 'submitted_at' => now()]);
        }

        UserNotification::notify($contract->employer_id, 'contract_milestone_submitted', 'Milestone Submitted',
            $worker->fullname . ' submitted milestone "' . $ms->title . '" for: ' . $contract->title, null, 'upload');

        return response()->json(['message' => 'Milestone submitted. Waiting for employer approval.']);
    }

    /** POST /api/contracts/{id}/milestones/{milestoneId}/approve */
    public function approveMilestone(Request $request, int $id, int $milestoneId): JsonResponse
    {
        $employer = $request->user();
        $contract = Contract::where('id', $id)->where('employer_id', $employer->id)->firstOrFail();
        $ms       = ContractMilestone::where('id', $milestoneId)->where('contract_id', $contract->id)->where('status', 1)->firstOrFail();

        DB::transaction(function () use ($contract, $ms) {
            [$commission, $workerPayout] = $this->calcCommission((float) $ms->amount);

            CoinService::credit($contract->worker, $workerPayout, $contract->reference . '-M' . $ms->id, 'work_earn',
                'Milestone payment: ' . $ms->title . ' (' . gs()->contract_commission . '% fee)');

            $ms->update([
                'status'            => 2,
                'commission_amount' => $commission,
                'worker_payout'     => $workerPayout,
                'completed_at'      => now(),
            ]);

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

        UserNotification::notify($contract->worker_id, 'contract_milestone_approved', 'Milestone Approved',
            'Milestone "' . $ms->title . '" approved. Payment released!', null, 'check-circle');

        return response()->json(['message' => 'Milestone approved and payment released.']);
    }

    /** POST /api/contracts/{id}/milestones/{milestoneId}/dispute */
    public function disputeMilestone(Request $request, int $id, int $milestoneId): JsonResponse
    {
        $employer = $request->user();
        $contract = Contract::where('id', $id)->where('employer_id', $employer->id)->firstOrFail();
        $ms       = ContractMilestone::where('id', $milestoneId)->where('contract_id', $contract->id)->where('status', 1)->firstOrFail();

        $data = $request->validate(['employer_note' => ['required', 'string', 'max:1000']]);

        $ms->update(['status' => 3]);
        $contract->update(['status' => 6, 'employer_note' => $data['employer_note']]);

        UserNotification::notify($contract->worker_id, 'contract_milestone_disputed', 'Milestone Disputed',
            'Milestone "' . $ms->title . '" was disputed by the employer.', null, 'alert-triangle');

        return response()->json(['message' => 'Dispute raised for this milestone. Our team will review.']);
    }

    private function contractResource(Contract $c, bool $asEmployer, bool $detailed = false): array
    {
        $statusLabels = [0 => 'Offered', 1 => 'Accepted', 2 => 'Submitted', 3 => 'Completed', 4 => 'Declined', 5 => 'Cancelled', 6 => 'Disputed'];
        $other = $asEmployer ? $c->worker : $c->employer;

        $base = [
            'id'          => $c->id,
            'reference'   => $c->reference,
            'title'       => $c->title,
            'amount'      => (float) $c->amount,
            'status'      => $c->status,
            'status_label'=> $statusLabels[$c->status] ?? 'Unknown',
            'has_milestones' => $c->relationLoaded('milestones') ? $c->milestones->isNotEmpty() : $c->hasMilestones(),
            'milestones_count'     => $c->relationLoaded('milestones') ? $c->milestones->count() : 0,
            'milestones_completed' => $c->relationLoaded('milestones') ? $c->milestones->where('status', 2)->count() : 0,
            'deadline_at' => $c->deadline_at,
            'created_at'  => $c->created_at,
            'other_party' => $other ? ['id' => $other->id, 'name' => $other->fullname, 'username' => $other->username] : null,
            'role'        => $asEmployer ? 'sender' : 'receiver',
        ];

        if ($detailed) {
            $base['description']      = $c->description;
            $base['worker_note']      = $c->worker_note;
            $base['employer_note']    = $c->employer_note;
            $base['declined_reason']  = $c->declined_reason;
            $base['commission_amount']= $c->commission_amount ? (float) $c->commission_amount : null;
            $base['worker_payout']    = $c->worker_payout ? (float) $c->worker_payout : null;
            $base['accepted_at']      = $c->accepted_at;
            $base['submitted_at']     = $c->submitted_at;
            $base['completed_at']     = $c->completed_at;
            $base['proof_file_url']   = $c->proof_file
                ? URL::temporarySignedRoute('secure.contractProof', now()->addMinutes(30), ['contract' => $c->id])
                : null;
            $base['milestones'] = $c->relationLoaded('milestones')
                ? $c->milestones->map(fn (ContractMilestone $m) => $this->milestoneResource($c, $m))->values()->all()
                : [];
        }

        return $base;
    }

    private function milestoneResource(Contract $c, ContractMilestone $m): array
    {
        $msStatusLabels = [0 => 'Pending', 1 => 'Submitted', 2 => 'Approved', 3 => 'Disputed'];

        return [
            'id'          => $m->id,
            'sort_order'  => $m->sort_order,
            'title'       => $m->title,
            'description' => $m->description,
            'amount'      => (float) $m->amount,
            'status'      => $m->status,
            'status_label'=> $msStatusLabels[$m->status] ?? 'Unknown',
            'deadline_at' => $m->deadline_at,
            'worker_note' => $m->worker_note,
            'worker_payout' => $m->worker_payout ? (float) $m->worker_payout : null,
            'submitted_at'  => $m->submitted_at,
            'completed_at'  => $m->completed_at,
            'proof_file_url'=> $m->proof_file
                ? URL::temporarySignedRoute('secure.contractProof', now()->addMinutes(30), ['contract' => $c->id, 'milestone' => $m->id])
                : null,
        ];
    }
}
