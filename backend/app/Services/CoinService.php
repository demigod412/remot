<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CoinService
{
    public const CURRENCY_COIN = 'coin';
    public const CURRENCY_USD  = 'usd';

    public const TYPE_APPLICATION_FEE    = 'application_fee';
    public const TYPE_APPLICATION_REFUND = 'application_refund';
    public const TYPE_WORK_EARN          = 'work_earn';
    public const TYPE_TASK_COMMISSION    = 'task_commission';

    /**
     * Deduct a task/membership application fee in JC coins.
     * Idempotent on $reference.
     */
    public function chargeApplicationFee(
        User $user,
        float $amount,
        string $reference,
        ?string $description = null
    ): LedgerEntry {
        $amount = $this->normalize($amount);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Application fee must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $reference, $description) {
            $existing = $this->findEntry($user->id, self::TYPE_APPLICATION_FEE, $reference);
            if ($existing) {
                return $existing;
            }

            /** @var User $locked */
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($this->normalize($locked->coin_balance) < $amount) {
                throw new InsufficientBalanceException(sprintf(
                    'Insufficient JC coin balance. Required %.4f, available %.4f.',
                    $amount,
                    (float) $locked->coin_balance
                ));
            }

            $locked->decrement('coin_balance', $amount);
            $locked->refresh();

            $entry = $this->writeEntry(
                userId: $locked->id,
                type: self::TYPE_APPLICATION_FEE,
                amount: -$amount,
                currency: self::CURRENCY_COIN,
                reference: $reference,
                description: $description ?? 'Application fee',
                balanceAfter: (float) $locked->coin_balance,
            );

            $user->setAttribute('coin_balance', $locked->coin_balance);

            return $entry;
        });
    }

    /**
     * Refund an application fee in JC coins.
     * ONLY valid when an admin rejects the membership/task application.
     * Never call on quality rejection or missed deadline.
     * Idempotent on "refund_{$reference}".
     */
    public function refundApplicationFee(
        User $user,
        float $amount,
        string $reference,
        ?string $description = null
    ): LedgerEntry {
        $amount    = $this->normalize($amount);
        $refundRef = "refund_{$reference}";

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $reference, $refundRef, $description) {
            $existing = $this->findEntry($user->id, self::TYPE_APPLICATION_REFUND, $refundRef);
            if ($existing) {
                return $existing;
            }

            $charge = $this->findEntry($user->id, self::TYPE_APPLICATION_FEE, $reference);
            if (! $charge) {
                throw new ModelNotFoundException("No application fee found for reference {$reference}.");
            }

            $charged = abs($this->normalize($charge->amount));
            if ($amount > $charged) {
                throw new \InvalidArgumentException(sprintf(
                    'Refund %.4f exceeds the %.4f originally charged on %s.',
                    $amount,
                    $charged,
                    $reference
                ));
            }

            /** @var User $locked */
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $locked->increment('coin_balance', $amount);
            $locked->refresh();

            $entry = $this->writeEntry(
                userId: $locked->id,
                type: self::TYPE_APPLICATION_REFUND,
                amount: $amount,
                currency: self::CURRENCY_COIN,
                reference: $refundRef,
                description: $description ?? 'Application fee refunded (admin rejection)',
                balanceAfter: (float) $locked->coin_balance,
            );

            $user->setAttribute('coin_balance', $locked->coin_balance);

            return $entry;
        });
    }

    /**
     * Credit net USD earnings to a worker and log platform commission to
     * system account user_id = 0, in one transaction. Idempotent on $reference.
     *
     * @return array{net: float, commission: float, worker_entry: LedgerEntry, commission_entry: ?LedgerEntry}
     */
    public function creditTaskEarnings(
        User $worker,
        float $grossAmount,
        float $commissionAmount,
        string $reference,
        ?string $description = null
    ): array {
        $gross      = $this->normalize($grossAmount);
        $commission = $this->normalize($commissionAmount);

        if ($gross <= 0) {
            throw new \InvalidArgumentException('Gross task amount must be greater than zero.');
        }

        if ($commission < 0 || $commission > $gross) {
            throw new \InvalidArgumentException('Commission must be between 0 and the gross amount.');
        }

        // Derive net from gross so both ledger rows always sum back to gross.
        $net = $this->normalize($gross - $commission);

        return DB::transaction(function () use ($worker, $gross, $commission, $net, $reference, $description) {
            $existing = $this->findEntry($worker->id, self::TYPE_WORK_EARN, $reference);

            if ($existing) {
                return [
                    'net'              => $this->normalize($existing->amount),
                    'commission'       => $commission,
                    'worker_entry'     => $existing,
                    'commission_entry' => $this->findEntry(
                        LedgerEntry::SYSTEM_ACCOUNT_ID,
                        self::TYPE_TASK_COMMISSION,
                        $reference
                    ),
                ];
            }

            /** @var User $locked */
            $locked = User::query()->whereKey($worker->id)->lockForUpdate()->firstOrFail();

            $locked->increment('usd_balance', $net);
            $locked->refresh();

            $workerEntry = $this->writeEntry(
                userId: $locked->id,
                type: self::TYPE_WORK_EARN,
                amount: $net,
                currency: self::CURRENCY_USD,
                reference: $reference,
                description: $description ?? 'Task earnings (net of commission)',
                balanceAfter: (float) $locked->usd_balance,
            );

            $commissionEntry = null;

            if ($commission > 0) {
                $commissionEntry = $this->writeEntry(
                    userId: LedgerEntry::SYSTEM_ACCOUNT_ID,
                    type: self::TYPE_TASK_COMMISSION,
                    amount: $commission,
                    currency: self::CURRENCY_USD,
                    reference: $reference,
                    description: sprintf('Platform commission on %s (gross %.4f USD)', $reference, $gross),
                    balanceAfter: null,
                );
            }

            $worker->setAttribute('usd_balance', $locked->usd_balance);

            return [
                'net'              => $net,
                'commission'       => $commission,
                'worker_entry'     => $workerEntry,
                'commission_entry' => $commissionEntry,
            ];
        });
    }

    /**
     * Split a gross USD amount by commission percentage.
     * Commission rounds first; net is always gross minus commission.
     *
     * @return array{gross: float, commission: float, net: float}
     */
    public function splitCommission(float $grossAmount, float $commissionPercent): array
    {
        $gross      = $this->normalize($grossAmount);
        $percent    = max(0.0, min(100.0, (float) $commissionPercent));
        $commission = $this->normalize($gross * $percent / 100);
        $net        = $this->normalize($gross - $commission);

        return ['gross' => $gross, 'commission' => $commission, 'net' => $net];
    }

    private function findEntry(int $userId, string $type, string $reference): ?LedgerEntry
    {
        return LedgerEntry::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('reference', $reference)
            ->lockForUpdate()
            ->first();
    }

    private function writeEntry(
        int $userId,
        string $type,
        float $amount,
        string $currency,
        string $reference,
        string $description,
        ?float $balanceAfter = null,
    ): LedgerEntry {
        return LedgerEntry::create([
            'user_id'       => $userId,
            'type'          => $type,
            'amount'        => $amount,
            'currency'      => $currency,
            'reference'     => $reference,
            'description'   => $description,
            'balance_after' => $balanceAfter,
        ]);
    }

    private function normalize(float|string|null $value): float
    {
        return round((float) $value, 4);
    }
}