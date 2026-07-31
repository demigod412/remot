<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Cashout;
use App\Models\CoinPackage;
use App\Models\CoinTopup;
use App\Models\LedgerEntry;
use App\Models\PaymentChannel;
use App\Models\PayoutMethod;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /** GET /api/v1/wallet */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $earnedThisMonth = (float) $user->ledgerEntries()
            ->where('entry_type', '+')
            ->where('category', 'work_earn')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('coins');

        $pendingBalance = (float) DB::table('work_submissions')
            ->join('works', 'works.id', '=', 'work_submissions.work_id')
            ->where('work_submissions.worker_id', $user->id)
            ->where('work_submissions.status', 1)
            ->sum('works.coins_per_worker');

        $contractCount = $user->workSubmissions()->where('status', 1)->count();

        return response()->json([
            'balance'          => (float) $user->coin_balance,
            'coin_balance'     => (float) $user->coin_balance,
            'earned_this_month'=> $earnedThisMonth,
            'pending_balance'  => $pendingBalance,
            'contract_count'   => $contractCount,
            'total_topup'      => (float) $user->coinTopups()->where('status', 1)->sum('coins_credited'),
            'total_cashout'    => (float) $user->cashouts()->where('status', 1)->sum('net_coins_deducted'),
            'total_earned'     => (float) $user->ledgerEntries()->where('entry_type', '+')->where('category', 'work_earn')->sum('coins'),
            'recent_topups'    => $user->coinTopups()->latest()->limit(5)->get()->map(fn ($t) => $this->topupResource($t)),
            'recent_cashouts'  => $user->cashouts()->with('payoutMethod')->latest()->limit(5)->get()->map(fn ($c) => $this->cashoutResource($c)),
        ]);
    }

    /** GET /api/v1/wallet/ledger */
    public function ledger(Request $request): JsonResponse
    {
        $query = $request->user()->ledgerEntries();

        if ($request->filled('type'))     $query->where('entry_type', $request->type);
        if ($request->filled('category')) $query->where('category', $request->category);

        $entries = $query->latest()->paginate(20);

        return response()->json([
            'data' => $entries->map(fn ($e) => [
                'id'           => $e->id,
                'entry_type'   => $e->entry_type,
                'coins'        => (float) $e->coins,
                'fee'          => (float) $e->fee,
                'balance_after'=> (float) $e->balance_after,
                'category'     => $e->category,
                'description'  => $e->description,
                'reference'    => $e->reference,
                'created_at'   => $e->created_at,
            ]),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page'    => $entries->lastPage(),
                'total'        => $entries->total(),
            ],
        ]);
    }

    /** GET /api/v1/wallet/coin-packages */
    public function coinPackages(): JsonResponse
    {
        $packages = CoinPackage::where('status', 1)->orderBy('coins')->get();
        return response()->json(['packages' => $packages->map(fn ($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'coins' => $p->coins,
            'price' => $p->price,
        ])]);
    }

    /** GET /api/v1/wallet/topup/methods */
    public function topupMethods(): JsonResponse
    {
        $channels = PaymentChannel::where('status', 1)->get();
        return response()->json(['channels' => $channels->map(fn ($c) => [
            'id'        => $c->id,
            'name'      => $c->name,
            'code'      => $c->code,
            'is_manual' => (bool) $c->is_manual,
            'currencies'=> $c->currencies ?? [],
            'image'     => $c->image
                ? fileUrl(config('jobstation.upload_paths.payment_channel'), $c->image)
                : null,
        ])]);
    }

    /** POST /api/v1/wallet/topup */
    public function topupInsert(Request $request): JsonResponse
    {
        $request->validate([
            'channel_code' => ['required', 'string'],
            'amount'       => ['required_without:package_id', 'nullable', 'numeric', 'min:0.01'],
            'package_id'   => ['required_without:amount', 'nullable', 'exists:coin_packages,id'],
        ]);

        $user    = $request->user();
        $channel = PaymentChannel::where('code', $request->channel_code)->where('status', 1)->firstOrFail();

        $amount = $request->amount;
        $pkg    = null;
        if ($request->package_id) {
            $pkg    = CoinPackage::findOrFail($request->package_id);
            $amount = $pkg->price;
        }

        // Resolve the channel currency + coin rate exactly like the web flow,
        // so the IPN credits the correct number of coins. currencies is stored
        // as {"USD": {rate, min, ...}} — the first key is the currency code.
        $currenciesRaw = $channel->currencies;
        if (is_string($currenciesRaw)) {
            $currenciesRaw = json_decode($currenciesRaw, true) ?? [];
        }
        $currencyCode = is_array($currenciesRaw) ? (array_key_first($currenciesRaw) ?? 'USD') : 'USD';

        if ($pkg) {
            $totalCoins = (float) $pkg->coins + (float) ($pkg->bonus_coins ?? 0);
            $rate       = $pkg->price > 0 ? round($totalCoins / $pkg->price, 8) : 1;
        } else {
            $currencyConf = is_array($currenciesRaw) ? ($currenciesRaw[$currencyCode] ?? []) : [];
            $rate         = (float) ($currencyConf['rate'] ?? 1);
        }

        $topup = CoinTopup::create([
            'user_id'        => $user->id,
            'channel_code'   => $channel->code,
            'amount'         => $amount,
            'pay_currency'   => $currencyCode,
            'charge'         => 0,
            'rate'           => $rate,
            'payable_amount' => $amount,
            'reference'      => generateReference(12),
            'status'         => 0,
            'package_id'     => $request->package_id,
        ]);

        if ($channel->is_manual) {
            return response()->json([
                'type'         => 'manual',
                'topup_id'     => $topup->id,
                'reference'    => $topup->reference,
                'instructions' => $channel->instructions,
            ]);
        }

        // Automatic gateway: hand off to the payment service.
        try {
            $redirectTarget = PaymentService::initiate($topup, $channel);
        } catch (\Throwable $e) {
            Log::error('API payment initiate failed', ['topup' => $topup->id, 'error' => $e->getMessage()]);
            $topup->update(['status' => 3]);
            return response()->json(['message' => 'Could not connect to the payment gateway. Please try again.'], 502);
        }

        // Gateways that need an in-browser form POST / JS checkout (PerfectMoney,
        // Paytm, Voguepay, Razorpay, legacy PayPal) can't be driven from the app —
        // tell the client to use the website for those.
        if (! str_starts_with($redirectTarget, 'http')) {
            return response()->json([
                'type'    => 'unsupported',
                'message' => 'Please complete this payment method on our website.',
            ], 200);
        }

        return response()->json([
            'type'      => 'redirect',
            'topup_id'  => $topup->id,
            'reference' => $topup->reference,
            'url'       => $redirectTarget,
        ]);
    }

    /** POST /api/v1/wallet/topup/manual */
    public function manualTopupConfirm(Request $request): JsonResponse
    {
        $request->validate([
            'topup_id'     => ['required', 'exists:coin_topups,id'],
            'transaction_id'=> ['required', 'string', 'max:255'],
            'screenshot'   => ['nullable', 'image', 'max:4096'],
        ]);

        $topup = CoinTopup::where('id', $request->topup_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 0)
            ->firstOrFail();

        $proofImage = null;
        if ($request->hasFile('screenshot')) {
            // Payment proof — store privately, serve via secure.topupProof.
            $proofImage = uploadPrivateFile($request->file('screenshot'), 'topup-proofs');
        }

        $topup->update([
            'transaction_id' => $request->transaction_id,
            'proof_image'    => $proofImage,
            'status'         => 2, // pending review
        ]);

        return response()->json(['message' => 'Payment proof submitted. We will review it shortly.']);
    }

    /** GET /api/v1/wallet/cashout/methods */
    public function cashoutMethods(): JsonResponse
    {
        $methods = PayoutMethod::with('form')->where('status', 1)->get();
        return response()->json(['methods' => $methods->map(fn ($m) => [
            'id'                    => $m->id,
            'name'                  => $m->name,
            'currency'              => $m->currency,
            'coin_to_currency_rate' => (float) $m->coin_to_currency_rate,
            'percent_fee'           => (float) $m->percent_fee,
            'fixed_fee'             => (float) $m->fixed_fee,
            'min_coins'             => (float) $m->min_coins,
            'max_coins'             => (float) $m->max_coins,
            'fields'                => $this->payoutFields($m),
            'image'                 => $m->image
                ? fileUrl(config('jobstation.upload_paths.payout_method'), $m->image)
                : null,
        ])]);
    }

    /**
     * The account fields the user must fill for a payout method. Mirrors the web
     * cashout form: a method's DynamicForm fields, or a sensible default.
     */
    private function payoutFields(PayoutMethod $method): array
    {
        $fields = $method->form->form_data ?? [];

        if (! is_array($fields) || count($fields) === 0) {
            return [
                ['name' => 'account', 'label' => 'Account number / address', 'required' => true,  'placeholder' => ''],
                ['name' => 'name',    'label' => 'Account name',             'required' => false, 'placeholder' => ''],
            ];
        }

        return array_map(fn ($f) => [
            'name'        => $f['name'] ?? '',
            'label'       => $f['label'] ?? ($f['name'] ?? ''),
            'required'    => (bool) ($f['required'] ?? false),
            'placeholder' => $f['placeholder'] ?? '',
        ], $fields);
    }

    /** POST /api/v1/wallet/cashout/preview */
    public function cashoutPreview(Request $request): JsonResponse
    {
        $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'method_id' => ['required', 'exists:payout_methods,id'],
        ]);

        $user   = $request->user();
        $amount = (float) $request->amount;
        $method = PayoutMethod::findOrFail($request->method_id);

        if ($error = $this->validateCashoutAmount($amount, $method, $user)) {
            return response()->json(['message' => $error], 422);
        }

        $preview = $method->calculatePayout($amount);

        return response()->json([
            'coin_amount'        => (float) $preview['coin_amount'],
            'fee'                => (float) $preview['fee'],
            'net_coins_deducted' => (float) $preview['net_coins_deducted'],
            'payout_amount'      => (float) $preview['payout_amount'],
            'currency'           => $method->currency,
        ]);
    }

    /** POST /api/v1/wallet/cashout */
    public function cashoutStore(Request $request): JsonResponse
    {
        $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'method_id'    => ['required', 'exists:payout_methods,id'],
            'account_data' => ['required', 'array'],
        ]);

        $user   = $request->user();
        $amount = (float) $request->amount;
        $method = PayoutMethod::findOrFail($request->method_id);

        if ($error = $this->validateCashoutAmount($amount, $method, $user)) {
            return response()->json(['message' => $error], 422);
        }

        $preview = $method->calculatePayout($amount);

        try {
            DB::transaction(function () use ($user, $method, $preview, $request) {
                // Lock + re-check inside the transaction so concurrent cashouts can't overdraw.
                $locked = \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                if ($locked->coin_balance < $preview['net_coins_deducted']) {
                    throw new \RuntimeException('insufficient');
                }

                $locked->decrement('coin_balance', $preview['net_coins_deducted']);
                $locked->refresh();

                $ref = generateReference(12);
                Cashout::create([
                    'user_id'               => $user->id,
                    'payout_method_id'      => $method->id,
                    'coin_amount'           => $preview['coin_amount'],
                    'payout_currency'       => $method->currency,
                    'coin_to_currency_rate' => $method->coin_to_currency_rate,
                    'fee'                   => $preview['fee'],
                    'reference'             => $ref,
                    'payout_amount'         => $preview['payout_amount'],
                    'net_coins_deducted'    => $preview['net_coins_deducted'],
                    'payout_details'        => $request->account_data,
                    'status'                => 0, // pending
                ]);

                LedgerEntry::create([
                    'user_id'       => $user->id,
                    'coins'         => $preview['net_coins_deducted'],
                    'fee'           => $preview['fee'],
                    'balance_after' => $locked->coin_balance,
                    'entry_type'    => '-',
                    'category'      => 'cashout',
                    'reference'     => $ref,
                    'description'   => 'Cashout request via ' . $method->name,
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        $latest = $user->cashouts()->latest()->first();
        foreach (Admin::all() as $admin) {
            AdminNotification::notify(
                $admin->id,
                'New Cashout Request',
                "{$user->username} requested a cashout of {$amount} coins via {$method->name}.",
                'info',
                $latest ? route('admin.cashouts.show', $latest->id) : null,
            );
        }

        return response()->json(['message' => 'Cashout request submitted. We will process it within 24–72 hours.'], 201);
    }

    /** POST /api/v1/wallet/send — transfer coins to another user by username */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'amount'   => ['required', 'numeric', 'min:1'],
            'note'     => ['nullable', 'string', 'max:255'],
        ]);

        $sender   = $request->user();
        $amount   = (float) $request->amount;
        $note     = $request->note ? trim($request->note) : null;
        $username = ltrim(trim($request->username), '@');

        $recipient = \App\Models\User::where('username', $username)->first();
        if (! $recipient) {
            return response()->json(['message' => 'No user found with username @' . $username . '.'], 422);
        }
        if ($recipient->id === $sender->id) {
            return response()->json(['message' => 'You cannot send coins to yourself.'], 422);
        }
        if ($sender->coin_balance < $amount) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        try {
            DB::transaction(function () use ($sender, $recipient, $amount, $note) {
                // Lock both rows; re-check the sender balance to avoid concurrent overdraw.
                $lockedSender    = \App\Models\User::whereKey($sender->id)->lockForUpdate()->firstOrFail();
                $lockedRecipient = \App\Models\User::whereKey($recipient->id)->lockForUpdate()->firstOrFail();
                if ($lockedSender->coin_balance < $amount) {
                    throw new \RuntimeException('insufficient');
                }

                $ref = generateReference(12);

                $lockedSender->decrement('coin_balance', $amount);
                $lockedSender->refresh();
                LedgerEntry::create([
                    'user_id'       => $lockedSender->id,
                    'coins'         => $amount,
                    'fee'           => 0,
                    'balance_after' => $lockedSender->coin_balance,
                    'entry_type'    => '-',
                    'category'      => 'transfer_sent',
                    'reference'     => $ref,
                    'description'   => 'Sent to @' . $lockedRecipient->username . ($note ? ' — ' . $note : ''),
                ]);

                $lockedRecipient->increment('coin_balance', $amount);
                $lockedRecipient->refresh();
                LedgerEntry::create([
                    'user_id'       => $lockedRecipient->id,
                    'coins'         => $amount,
                    'fee'           => 0,
                    'balance_after' => $lockedRecipient->coin_balance,
                    'entry_type'    => '+',
                    'category'      => 'transfer_received',
                    'reference'     => $ref,
                    'description'   => 'Received from @' . $lockedSender->username . ($note ? ' — ' . $note : ''),
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Transfer failed. Please try again.'], 422);
        }

        return response()->json([
            'message' => 'Coins sent to @' . $recipient->username . '.',
            'balance' => (float) $sender->fresh()->coin_balance,
        ]);
    }

    /**
     * Shared min/max/balance validation for cashout preview + submit.
     * Returns an error string, or null when the amount is valid.
     */
    private function validateCashoutAmount(float $amount, PayoutMethod $method, $user): ?string
    {
        $globalMin = (float) (gs()->min_cashout ?? 0);

        if ($amount < $globalMin) {
            return "Minimum cashout is {$globalMin} coins.";
        }
        if ($amount < (float) $method->min_coins) {
            return "Minimum withdrawal via this method is {$method->min_coins} coins.";
        }
        if ($method->max_coins && $amount > (float) $method->max_coins) {
            return "Maximum withdrawal is {$method->max_coins} coins.";
        }
        if ($amount > (float) $user->coin_balance) {
            return 'Insufficient coin balance.';
        }

        return null;
    }

    /** GET /api/v1/wallet/cashouts */
    public function cashoutHistory(Request $request): JsonResponse
    {
        $cashouts = $request->user()
            ->cashouts()
            ->with('payoutMethod')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $cashouts->map(fn ($c) => $this->cashoutResource($c)),
            'meta' => [
                'current_page' => $cashouts->currentPage(),
                'last_page'    => $cashouts->lastPage(),
                'total'        => $cashouts->total(),
            ],
        ]);
    }

    private function topupResource(CoinTopup $t): array
    {
        return [
            'id'             => $t->id,
            'reference'      => $t->reference,
            'amount'         => (float) $t->amount,
            'coins_credited' => $t->coins_credited,
            'channel_code'   => $t->channel_code,
            'status'         => $t->status,
            'created_at'     => $t->created_at,
        ];
    }

    private function cashoutResource(Cashout $c): array
    {
        return [
            'id'                 => $c->id,
            'reference'          => $c->reference,
            'coin_amount'        => (float) $c->coin_amount,
            'fee'                => (float) $c->fee,
            'payout_amount'      => (float) $c->payout_amount,
            'payout_currency'    => $c->payout_currency,
            'net_coins_deducted' => (float) $c->net_coins_deducted,
            'payout_method'      => $c->payoutMethod?->name,
            'status'             => $c->status,
            'created_at'         => $c->created_at,
        ];
    }
}
