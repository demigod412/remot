<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Work;
use App\Models\WorkSubmission;
use App\Services\RankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();

        // Submission stats
        $submissionStats = [
            'total'    => $user->workSubmissions()->count(),
            'pending'  => $user->workSubmissions()->where('status', 1)->count(),
            'approved' => $user->workSubmissions()->where('status', 2)->count(),
            'rejected' => $user->workSubmissions()->where('status', 3)->count(),
        ];

        // Work (poster) stats
        $workStats = [
            'total'  => $user->works()->count(),
            'active' => $user->works()->where('work_status', 1)->count(),
        ];

        // Earnings chart: last 30 days of TASK EARNINGS, in USD.
        //
        // This previously summed every credit row, which meant coin top-ups and
        // referral bonuses were added to dollar payouts in one total. Because the
        // amount lives in a single `coins` column for both currencies, that produced
        // a meaningless figure labelled as coins. Scoped to work_earn + USD so the
        // number matches what the panel claims to show.
        $earningsChart = $user->ledgerEntries()
            ->where('entry_type', '+')
            ->where('category', 'work_earn')
            ->inUsd()
            ->where('created_at', '>=', now()->subDays(29))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(coins) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Build 30-day array with zero fill
        $chartDates  = [];
        $chartValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date          = now()->subDays($i)->format('Y-m-d');
            $chartDates[]  = now()->subDays($i)->format('M d');
            $chartValues[] = (float) ($earningsChart[$date] ?? 0);
        }

        // Recent ledger entries
        $recentLedger = $user->ledgerEntries()->latest()->limit(5)->get();

        // Recent submissions
        $recentSubmissions = $user->workSubmissions()
            ->with('work')
            ->latest()
            ->limit(5)
            ->get();

        // Total earned
        // work_earn rows are denominated in USD. Scoped, because the amount column
        // is shared between currencies and an unscoped sum adds coins to dollars.
        $totalEarned = $user->ledgerEntries()
            ->where('entry_type', '+')
            ->where('category', 'work_earn')
            ->inUsd()
            ->sum('coins');

        // Worker rank
        $rank = RankService::getLevel($user);

        // Profile completeness
        $profileSteps = [
            'avatar'   => (bool) $user->avatar,
            'mobile'   => (bool) $user->mobile,
            'country'  => (bool) $user->country_code,
            'kyc'      => $user->kyc_status == 1,
            'skills'   => $user->skills()->exists(),
        ];
        $profilePercent = (int) round(array_sum($profileSteps) / count($profileSteps) * 100);

        // Smart matching: recommended works based on categories the user has worked in
        $workedCategoryIds = $user->workSubmissions()
            ->join('works', 'work_submissions.work_id', '=', 'works.id')
            ->pluck('works.category_id')
            ->unique()
            ->filter();

        $recommendedWorks = Work::active()
            ->with('category')
            ->when($workedCategoryIds->isNotEmpty(), function ($q) use ($workedCategoryIds) {
                $q->whereIn('category_id', $workedCategoryIds)
                  ->whereDoesntHave('submissions', fn($s) =>
                      $s->where('worker_id', Auth::guard('web')->id())
                  );
            })
            ->orderByDesc('is_featured')
            ->latest()
            ->limit(4)
            ->get();

        return view('user.dashboard', compact(
            'user', 'submissionStats', 'workStats',
            'chartDates', 'chartValues',
            'recentLedger', 'recentSubmissions', 'totalEarned',
            'rank', 'recommendedWorks',
            'profileSteps', 'profilePercent'
        ));
    }

    public function onboarding()
    {
        $user = Auth::guard('web')->user();
        return view('user.onboarding', compact('user'));
    }

    public function completeOnboarding(Request $request)
    {
        $request->validate([
            'country_code' => ['required', 'string', 'max:10'],
            'mobile'       => ['nullable', 'string', 'max:20'],
        ]);

        $user = Auth::guard('web')->user();
        $user->update([
            'country_code'    => $request->country_code,
            'mobile'          => $request->mobile,
            'onboarding_step' => 1,
        ]);

        return redirect()->route('user.dashboard')->with('success', 'Profile setup complete. Welcome!');
    }
}
