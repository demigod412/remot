<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Cashout;
use App\Models\CoinPackage;
use App\Models\CoinTopup;
use App\Models\LedgerEntry;
use App\Models\PaymentChannel;
use App\Models\PayoutMethod;
use App\Models\UserPayoutAccount;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function overview()
    {
        $user = Auth::guard('web')->user();

        $stats = [
            'total_topup'   => $user->coinTopups()->where('status', 1)->sum('coins_credited'),
            'total_cashout' => $user->cashouts()->where('status', 1)->sum('net_coins_deducted'),
            'total_earned'  => $user->ledgerEntries()->where('entry_type', '+')->where('category', 'work_earn')->sum('coins'),
        ];

        $recentTopups   = $user->coinTopups()->latest()->limit(5)->get();
        $recentCashouts = $user->cashouts()->with('payoutMethod')->latest()->limit(5)->get();

        return view('user.wallet.overview', compact('user', 'stats', 'recentTopups', 'recentCashouts'));
    }

    public function ledger(Request $request)
    {
        $user    = Auth::guard('web')->user();
        $query   = $user->ledgerEntries();

        if ($request->filled('type')) {
            $query->where('entry_type', $request->type);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $entries = $query->latest()->paginate(config('jobstation.per_page'));

        return view('user.wallet.ledger', compact('entries'));
    }

    // ───────────────── TOP-UP ─────────────────

    public function topupMethods()
    {
        $packages  = CoinPackage::where('status', 1)->orderBy('coins')->get();
        $channels  = PaymentChannel::where('status', 1)->get();

        return view('user.wallet.topup', compact('packages', 'channels'));
    }

    public function topupInsert(Request $request)
    {
        $request->validate([
            'channel_code' => ['required'],
            'amount'       => ['required_without:package_id', 'nullable', 'numeric', 'min:0.01'],
            'package_id'   => ['required_without:amount', 'nullable', 'exists:coin_packages,id'],
        ]);

        $user    = Auth::guard('web')->user();
        $channel = PaymentChannel::where('code', $request->channel_code)->where('status', 1)->firstOrFail();

        $amount = $request->amount;
        $pkg    = null;
        if ($request->package_id) {
            $pkg    = CoinPackage::findOrFail($request->package_id);
            $amount = $pkg->price;
        }

        // Extract currency code from channel (stored as {"USD":{rate,min,...}})
        $currenciesRaw = $channel->currencies;
        if (is_string($currenciesRaw)) {
            $currenciesRaw = json_decode($currenciesRaw, true) ?? [];
        }
        $currencyCode = is_array($currenciesRaw) ? (array_key_first($currenciesRaw) ?? 'USD') : 'USD';

        // Rate = coins per 1 unit of currency.
        // If a package is selected, use the package's coin:price ratio.
        // Otherwise fall back to the channel's configured rate.
        if ($pkg) {
            $totalCoins = (float) $pkg->coins + (float) ($pkg->bonus_coins ?? 0);
            $rate       = $pkg->price > 0 ? round($totalCoins / $pkg->price, 8) : 1;
        } else {
            $currencyConf = is_array($currenciesRaw) ? ($currenciesRaw[$currencyCode] ?? []) : [];
            $rate         = (float) ($currencyConf['rate'] ?? 1);
        }

        $reference = generateReference(12);

        $topup = CoinTopup::create([
            'user_id'        => $user->id,
            'channel_code'   => $channel->code,
            'amount'         => $amount,
            'pay_currency'   => $currencyCode,
            'charge'         => 0,
            'rate'           => $rate,
            'payable_amount' => $amount,
            'reference'      => $reference,
            'status'         => 0, // initiated
            'package_id'     => $request->package_id,
        ]);

        // Manual channels go to confirmation screen
        if ($channel->is_manual) {
            $request->session()->put('topup_id', $topup->id);
            return redirect()->route('user.wallet.topup.manual-confirm')
                ->with('info', 'Please follow the payment instructions and submit your payment proof.');
        }

        // Automatic gateway: initiate and redirect
        try {
            $redirectTarget = PaymentService::initiate($topup, $channel);
        } catch (\Throwable $e) {
            Log::error('Payment initiate failed', ['topup' => $topup->id, 'error' => $e->getMessage()]);
            $topup->update(['status' => 3]);
            return redirect()->route('user.wallet.topup')
                ->with('error', 'Could not connect to payment gateway. Please try again.');
        }

        // Form-POST redirect (PayPal legacy, Paytm, PerfectMoney, Voguepay)
        if (str_starts_with($redirectTarget, 'FORM:')) {
            $form = json_decode(substr($redirectTarget, 5), true);
            return view('payment.redirect', [
                'action' => $form['action'],
                'method' => $form['method'],
                'fields' => $form['fields'],
            ]);
        }

        // Razorpay JS checkout
        if (str_starts_with($redirectTarget, 'RAZORPAY:')) {
            $rzp = json_decode(substr($redirectTarget, 9), true);
            return view('payment.razorpay', compact('rzp'));
        }

        // Standard URL redirect (Stripe, PayPal SDK, Paystack, Flutterwave, etc.)
        return redirect()->away($redirectTarget);
    }

    public function manualTopupConfirm(Request $request)
    {
        $topupId = $request->session()->pull('topup_id');
        if (! $topupId) {
            return redirect()->route('user.wallet.topup');
        }

        $topup   = CoinTopup::where('id', $topupId)->where('user_id', Auth::id())->firstOrFail();
        $channel = $topup->channel;

        return view('user.wallet.topup-confirm', compact('topup', 'channel'));
    }

    public function manualTopupUpdate(Request $request, int $id)
    {
        $topup = CoinTopup::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'proof_image'    => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $proofImage = $topup->proof_image;
        if ($request->hasFile('proof_image')) {
            // Payment proof — store privately (off the web root), serve via secure.topupProof.
            $proofImage = uploadPrivateFile($request->file('proof_image'), 'topup-proofs');
        }

        $topup->update([
            'gateway_response' => json_encode(['transaction_id' => $request->transaction_id, 'note' => $request->note]),
            'proof_image'      => $proofImage,
            'status'           => 2, // pending review
        ]);

        $request->session()->forget('topup_id');

        return redirect()->route('user.wallet.overview')
            ->with('success', 'Payment submitted. We\'ll verify and credit your balance shortly.');
    }

    // ───────────────── CASH-OUT ─────────────────

    public function cashoutMethods()
    {
        $methods = PayoutMethod::where('status', 1)->get();
        return view('user.wallet.cashout', compact('methods'));
    }

    // ───────────────── PAYOUT ACCOUNTS ─────────────────

    public function payoutAccounts()
    {
        $user     = Auth::guard('web')->user();
        $methods  = PayoutMethod::where('status', 1)->get();
        $accounts = UserPayoutAccount::where('user_id', $user->id)
            ->with('payoutMethod')
            ->latest()
            ->get();

        return view('user.wallet.payout-accounts', compact('accounts', 'methods'));
    }

    public function payoutAccountStore(Request $request)
    {
        $request->validate([
            'payout_method_id' => ['required', 'exists:payout_methods,id'],
            'label'            => ['nullable', 'string', 'max:60'],
            'details'          => ['required', 'array'],
        ]);

        $user = Auth::guard('web')->user();

        $account = UserPayoutAccount::create([
            'user_id'          => $user->id,
            'payout_method_id' => $request->payout_method_id,
            'label'            => $request->label,
            'details'          => $request->details,
            'is_default'       => false,
        ]);

        if (UserPayoutAccount::where('user_id', $user->id)->count() === 1) {
            $account->update(['is_default' => true]);
        }

        return back()->with('success', 'Payout account saved.');
    }

    public function payoutAccountDelete(int $id)
    {
        UserPayoutAccount::where('id', $id)->where('user_id', Auth::id())->delete();
        return back()->with('success', 'Account removed.');
    }

    public function payoutAccountSetDefault(int $id)
    {
        $userId = Auth::id();
        UserPayoutAccount::where('user_id', $userId)->update(['is_default' => false]);
        UserPayoutAccount::where('id', $id)->where('user_id', $userId)->update(['is_default' => true]);
        return back()->with('success', 'Default account updated.');
    }

    // ─────────────────────────────────────────────────

    public function cashoutPreview(Request $request)
    {
        $request->validate([
            'payout_method_id' => ['required', 'exists:payout_methods,id'],
            'coin_amount'      => ['required', 'numeric', 'min:1'],
        ]);

        $user   = Auth::guard('web')->user();
        $method = PayoutMethod::findOrFail($request->payout_method_id);

        // Withdrawals come out of USD earnings, never out of JC coins. Coins are
        // bought to spend on application fees and have no route out of the system.
        //
        // The request field and the cashouts columns are still named *coin*, from
        // when coins were the only currency. They now hold a USD amount. Renaming
        // them would touch every payout method, report and admin screen, so the
        // names stay and the meaning is documented here and in EarningsService.
        //
        // PayoutMethod::coin_to_currency_rate survives and is still meaningful: it
        // converts USD to the method's payout currency (NGN, etc). That is payout
        // FX, a different thing from a JC-to-USD rate, which does not exist.
        $globalMin = gs()->min_cashout ?? 50;
        if ($request->coin_amount < $globalMin) {
            return back()->with('error', "Minimum cashout is \${$globalMin}.");
        }
        if ($request->coin_amount < $method->min_coins) {
            return back()->with('error', "Minimum withdrawal via this method is \${$method->min_coins}.");
        }
        if ($method->max_coins && $request->coin_amount > $method->max_coins) {
            return back()->with('error', "Maximum withdrawal is \${$method->max_coins}.");
        }
        if ($request->coin_amount > $user->usd_balance) {
            return back()->with('error', 'Insufficient USD earnings balance.');
        }

        $preview = $method->calculatePayout((float) $request->coin_amount);

        $request->session()->put('cashout_preview', array_merge($preview, [
            'payout_method_id' => $method->id,
        ]));

        $savedAccounts = UserPayoutAccount::where('user_id', $user->id)
            ->where('payout_method_id', $method->id)
            ->get();

        return view('user.wallet.cashout-preview', compact('method', 'preview', 'savedAccounts'));
    }

    public function cashoutSubmit(Request $request)
    {
        $preview = $request->session()->get('cashout_preview');
        if (! $preview) {
            return redirect()->route('user.wallet.cashout');
        }

        $user   = Auth::guard('web')->user();
        $method = PayoutMethod::findOrFail($preview['payout_method_id']);

        if ($preview['net_coins_deducted'] > $user->usd_balance) {
            return redirect()->route('user.wallet.cashout')->with('error', 'Insufficient balance.');
        }

        $request->validate([
            'payout_details' => ['required', 'array'],
        ]);

        // Optionally save payout account for future use
        if ($request->boolean('save_account')) {
            $exists = UserPayoutAccount::where('user_id', $user->id)
                ->where('payout_method_id', $method->id)
                ->where('details->account', $request->input('payout_details.account'))
                ->exists();
            if (! $exists) {
                $isFirst = ! UserPayoutAccount::where('user_id', $user->id)->exists();
                UserPayoutAccount::create([
                    'user_id'          => $user->id,
                    'payout_method_id' => $method->id,
                    'label'            => $request->input('save_label') ?: $method->name,
                    'details'          => $request->payout_details,
                    'is_default'       => $isFirst,
                ]);
            }
        }

        try {
            DB::transaction(function () use ($user, $method, $preview, $request) {
                $ref = generateReference(12);

                // Locks the user row, re-checks the balance and writes the ledger
                // row itself. Throws RuntimeException on an overdraw, which rolls
                // this whole transaction back.
                \App\Services\EarningsService::withdraw(
                    $user,
                    (float) $preview['net_coins_deducted'],
                    $ref,
                    'cashout',
                    'Cashout request via ' . $method->name
                );
                Cashout::create([
                    'user_id'              => $user->id,
                    'payout_method_id'     => $method->id,
                    'coin_amount'          => $preview['coin_amount'],
                    'payout_currency'      => $method->currency,
                    'coin_to_currency_rate'=> $method->coin_to_currency_rate,
                    'fee'                  => $preview['fee'],
                    'reference'            => $ref,
                    'payout_amount'        => $preview['payout_amount'],
                    'net_coins_deducted'   => $preview['net_coins_deducted'],
                    'payout_details'       => $request->payout_details,
                    'status'               => 0, // pending
                ]);

                // No LedgerEntry here: EarningsService::withdraw() already wrote
                // the debit row, tagged with currency = usd. Writing a second one
                // would double-count the withdrawal in every report.
            });
        } catch (\Throwable $e) {
            return redirect()->route('user.wallet.cashout')->with('error', 'Insufficient balance.');
        }

        $request->session()->forget('cashout_preview');

        $latestCashout = $user->cashouts()->latest()->first();
        foreach (Admin::all() as $admin) {
            AdminNotification::notify($admin->id, 'New Cashout Request',
                "{$user->username} requested a cashout of {$preview['coin_amount']} coins via {$method->name}.",
                'info', $latestCashout ? route('admin.cashouts.show', $latestCashout->id) : null);
        }

        return redirect()->route('user.wallet.cashout.history')
            ->with('success', 'Cashout request submitted. We\'ll process it within 24–72 hours.');
    }

    public function cashoutHistory()
    {
        $cashouts = Auth::guard('web')->user()
            ->cashouts()
            ->with('payoutMethod')
            ->latest()
            ->paginate(config('jobstation.per_page'));

        return view('user.wallet.cashout-history', compact('cashouts'));
    }

    public function cashoutReceipt(int $id)
    {
        $cashout = Cashout::where('id', $id)
            ->where('user_id', Auth::id())
            ->with(['payoutMethod', 'user'])
            ->firstOrFail();

        return view('user.wallet.cashout-receipt', compact('cashout'));
    }
}
