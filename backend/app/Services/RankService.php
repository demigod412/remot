<?php

namespace App\Services;

use App\Models\User;

class RankService
{
    /*
     * Rank tiers based on total approved submissions + completed contracts.
     *
     * Level      | Score threshold | Badge color
     * -----------+-----------------+-----------
     * Newcomer   |  0              | gray
     * Bronze     |  5              | amber-600
     * Silver     | 25              | slate-400
     * Gold       | 75              | yellow-400
     * Platinum   | 200             | cyan-400
     */

    public static function getLevel(User $user): array
    {
        $score = static::score($user);

        return match (true) {
            $score >= 200 => ['label' => 'Platinum', 'color' => 'text-cyan-400',   'bg' => 'bg-cyan-400/10',   'icon' => '💎', 'score' => $score],
            $score >= 75  => ['label' => 'Gold',     'color' => 'text-yellow-400', 'bg' => 'bg-yellow-400/10', 'icon' => '🥇', 'score' => $score],
            $score >= 25  => ['label' => 'Silver',   'color' => 'text-slate-300',  'bg' => 'bg-slate-400/10',  'icon' => '🥈', 'score' => $score],
            $score >= 5   => ['label' => 'Bronze',   'color' => 'text-amber-600',  'bg' => 'bg-amber-600/10',  'icon' => '🥉', 'score' => $score],
            default        => ['label' => 'Newcomer','color' => 'text-white/40',   'bg' => 'bg-white/5',       'icon' => '🌱', 'score' => $score],
        };
    }

    public static function score(User $user): int
    {
        // Approved microtask submissions (weight 1 each)
        $approved = $user->workSubmissions()->where('status', 2)->count();

        // Completed contracts as worker (weight 5 each — higher trust signal)
        $contracts = $user->contractsAsWorker()->where('status', 3)->count();

        // KYC bonus (+5)
        $kycBonus = $user->kyc_status === 1 ? 5 : 0;

        return $approved + ($contracts * 5) + $kycBonus;
    }

    /**
     * Next tier threshold for progress bar display.
     */
    public static function nextThreshold(int $score): int
    {
        return match (true) {
            $score >= 200 => 200,
            $score >= 75  => 200,
            $score >= 25  => 75,
            $score >= 5   => 25,
            default        => 5,
        };
    }

    public static function prevThreshold(int $score): int
    {
        return match (true) {
            $score >= 200 => 200,
            $score >= 75  => 75,
            $score >= 25  => 25,
            $score >= 5   => 5,
            default        => 0,
        };
    }
}
