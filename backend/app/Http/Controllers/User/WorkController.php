<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\BoostRequest;
use App\Models\LedgerEntry;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Services\CoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::guard('web')->user();
        $query = $user->works()->with(['category', 'submissions']);

        if ($request->filled('status')) {
            $query->where('work_status', $request->status);
        }

        $works = $query->latest()->paginate(config('jobstation.per_page'));

        return view('user.works.index', compact('works'));
    }

    public function create()
    {
        $categories = WorkCategory::where('status', 1)->orderBy('name')->get();
        return view('user.works.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::guard('web')->user();

        $data = $request->validate([
            'title'             => ['required', 'string', 'max:160'],
            'category_id'       => ['required', 'exists:work_categories,id'],
            'subcategory_id'    => ['nullable', 'exists:work_subcategories,id'],
            'description'       => ['required', 'string', 'min:50'],
            'worker_slots'      => ['required', 'integer', 'min:1', 'max:10000'],
            'coins_per_worker'  => ['required', 'numeric', 'min:1'],
            'avg_minutes'          => ['nullable', 'integer', 'min:1'],
            'expires_at'           => ['nullable', 'date', 'after:today'],
            'auto_approve_hours'   => ['nullable', 'integer', 'min:1', 'max:168'],
            'cover_image'          => ['nullable', 'image', 'max:2048'],
            'requires_kyc'         => ['sometimes', 'boolean'],
        ]);

        $totalCoins = $data['worker_slots'] * $data['coins_per_worker'];

        if ($user->coin_balance < $totalCoins) {
            return back()->with('error', "Insufficient balance. You need {$totalCoins} coins to post this work. Your balance: {$user->coin_balance}.");
        }

        try {
            DB::transaction(function () use ($user, $data, $request, $totalCoins) {
            // Lock + re-check balance atomically so concurrent posts can't overspend.
            $locked = \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked->coin_balance < $totalCoins) {
                throw new \RuntimeException('insufficient');
            }

            $coverImage = null;
            if ($request->hasFile('cover_image')) {
                $coverImage = uploadFile($request->file('cover_image'), config('jobstation.upload_paths.work_cover'));
            }

            $slug = Str::slug($data['title']) . '-' . Str::random(6);

            $work = Work::create([
                'poster_id'        => $user->id,
                'poster_type'      => 2,
                'category_id'      => $data['category_id'],
                'subcategory_id'   => $data['subcategory_id'] ?? null,
                'title'            => $data['title'],
                'slug'             => strtolower($slug),
                'description'      => $data['description'],
                'worker_slots'     => $data['worker_slots'],
                'coins_per_worker' => $data['coins_per_worker'],
                'total_coins'      => $totalCoins,
                'avg_minutes'        => $data['avg_minutes'] ?? null,
                'expires_at'         => $data['expires_at'] ?? null,
                'auto_approve_hours' => $data['auto_approve_hours'] ?? null,
                'cover_image'      => $coverImage,
                'requires_kyc'     => $request->boolean('requires_kyc'),
                'approval_status'  => 0, // pending admin approval
                'work_status'      => 0, // holding
            ]);

            // Deduct coins (held until work is finished/rejected)
            $locked->decrement('coin_balance', $totalCoins);
            $locked->refresh();
            $user->coin_balance = $locked->coin_balance;

            LedgerEntry::create([
                'user_id'       => $user->id,
                'coins'         => $totalCoins,
                'fee'           => 0,
                'balance_after' => $locked->coin_balance,
                'entry_type'    => '-',
                'category'      => 'work_spend',
                'reference'     => $work->slug,
                'description'   => 'Budget held for: ' . $work->title,
            ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Insufficient balance to post this work.');
        }

        $newWork = Work::where('poster_id', $user->id)->latest()->first();
        foreach (Admin::all() as $admin) {
            AdminNotification::notify($admin->id, 'New Work Pending Review',
                "{$user->username} submitted a new work for approval: \"{$data['title']}\"",
                'info', $newWork ? route('admin.works.show', $newWork->id) : null);
        }

        return redirect()->route('user.works.index')
            ->with('success', 'Work submitted for review. We\'ll notify you once it\'s approved.');
    }

    public function show(int $id)
    {
        $user = Auth::guard('web')->user();
        $work = Work::where('id', $id)
            ->where('poster_id', $user->id)
            ->where('poster_type', 2)
            ->with(['category', 'subcategory', 'submissions'])
            ->firstOrFail();

        $submissionStats = [
            'total'    => $work->submissions()->count(),
            'pending'  => $work->submissions()->where('status', 1)->count(),
            'approved' => $work->submissions()->where('status', 2)->count(),
            'rejected' => $work->submissions()->where('status', 3)->count(),
        ];

        $pendingBoost = $work->boostRequests()->where('status', 0)->latest()->first();

        return view('user.works.show', compact('work', 'submissionStats', 'pendingBoost'));
    }

    public function delete(int $id)
    {
        $user = Auth::guard('web')->user();
        $work = Work::where('id', $id)
            ->where('poster_id', $user->id)
            ->where('poster_type', 2)
            ->firstOrFail();

        if ($work->approval_status === 1 && $work->work_status === 1) {
            return back()->with('error', 'Cannot delete an active, approved work. Please contact support.');
        }

        DB::transaction(function () use ($user, $work) {
            // Refund remaining budget
            $approvedCount = $work->approvedSubmissions()->count();
            $spent         = $approvedCount * $work->coins_per_worker;
            $refund        = $work->total_coins - $spent;

            if ($refund > 0) {
                $user->increment('coin_balance', $refund);
                $user->refresh();
                LedgerEntry::create([
                    'user_id'       => $user->id,
                    'coins'         => $refund,
                    'fee'           => 0,
                    'balance_after' => $user->coin_balance,
                    'entry_type'    => '+',
                    'category'      => 'work_refund',
                    'reference'     => $work->slug,
                    'description'   => 'Refund for deleted work: ' . $work->title,
                ]);
            }

            if ($work->cover_image) {
                removeFile(config('jobstation.upload_paths.work_cover'), $work->cover_image);
            }

            $work->delete();
        });

        return redirect()->route('user.works.index')->with('success', 'Work deleted and remaining budget refunded.');
    }

    public function boost(Request $request, int $id)
    {
        $user = Auth::guard('web')->user();
        $work = Work::where('id', $id)
            ->where('poster_id', $user->id)
            ->where('poster_type', 2)
            ->where('approval_status', 1)
            ->firstOrFail();

        if ($work->boostRequests()->where('status', 0)->exists()) {
            return back()->with('error', 'You already have a pending boost request for this work. Please wait for admin approval.');
        }

        $maxDays    = max(1, (int) gs()->boost_days_work);
        $days       = max(1, min((int) $request->input('days', 1), $maxDays));
        $costPerDay = (float) gs()->boost_cost_work;
        $totalCost  = $costPerDay * $days;

        if (! CoinService::hasBalance($user, $totalCost)) {
            return back()->with('error', "Insufficient coins. You need " . formatCoins($totalCost) . " coins for {$days} day(s).");
        }

        DB::transaction(function () use ($user, $work, $days, $totalCost) {
            CoinService::deduct($user, $totalCost, $work->slug, 'boost_request', "Boost request: {$work->title} ({$days} days)");

            BoostRequest::create([
                'boostable_type' => Work::class,
                'boostable_id'   => $work->id,
                'user_id'        => $user->id,
                'days'           => $days,
                'coins_paid'     => $totalCost,
                'status'         => 0,
            ]);
        });

        foreach (Admin::all() as $admin) {
            AdminNotification::notify($admin->id, 'New Boost Request',
                "{$user->username} requested a boost for work \"{$work->title}\" ({$days} day(s)).",
                'info', route('admin.boost-requests.index'));
        }

        return back()->with('success', 'Boost request submitted! Admin will review and activate it shortly. Coins have been held.');
    }
}
