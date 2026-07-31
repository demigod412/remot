<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkController extends Controller
{
    /** GET /api/v1/works — public browsing */
    public function index(Request $request): JsonResponse
    {
        $query = Work::with(['category', 'subcategory'])
            ->where('work_status', 1)
            ->where('approval_status', 1)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($catId = $request->input('category_id')) {
            $query->where('category_id', $catId);
        }

        if ($subcatId = $request->input('subcategory_id')) {
            $query->where('subcategory_id', $subcatId);
        }

        match ($request->input('sort', 'latest')) {
            'coins_high' => $query->orderByDesc('coins_per_worker'),
            'coins_low'  => $query->orderBy('coins_per_worker'),
            'slots'      => $query->orderByDesc('worker_slots'),
            default      => $query->latest(),
        };

        $works = $query->paginate($request->input('per_page', 12));

        return response()->json([
            'data'  => $works->map(fn ($w) => $this->workResource($w)),
            'meta'  => [
                'current_page' => $works->currentPage(),
                'last_page'    => $works->lastPage(),
                'per_page'     => $works->perPage(),
                'total'        => $works->total(),
            ],
        ]);
    }

    /** GET /api/v1/works/{slug} */
    public function show(Request $request, string $slug): JsonResponse
    {
        $work = Work::with(['category', 'subcategory'])
            ->where('slug', $slug)
            ->where('work_status', 1)
            ->where('approval_status', 1)
            ->firstOrFail();

        $alreadyApplied = false;
        $mySubmission   = null;
        $isBookmarked   = false;
        // This route is public, so resolve the bearer token explicitly (optional auth).
        if ($user = $request->user('sanctum')) {
            $sub = WorkSubmission::where('work_id', $work->id)
                ->where('worker_id', $user->id)
                ->first();
            if ($sub) {
                $alreadyApplied = true;
                $mySubmission   = ['id' => $sub->id, 'status' => $sub->status];
            }
            $isBookmarked = \App\Models\WorkBookmark::where('user_id', $user->id)
                ->where('work_id', $work->id)->exists();
        }

        $similar = Work::with('category')
            ->where('category_id', $work->category_id)
            ->where('id', '!=', $work->id)
            ->where('work_status', 1)
            ->where('approval_status', 1)
            ->latest()
            ->limit(4)
            ->get();

        $workData = $this->workResource($work, detailed: true);
        $workData['is_bookmarked'] = $isBookmarked;

        return response()->json([
            'work'            => $workData,
            'already_applied' => $alreadyApplied,
            'my_submission'   => $mySubmission,
            'similar'         => $similar->map(fn ($w) => $this->workResource($w)),
        ]);
    }

    /** GET /api/v1/works/suggested — works matching the user's skills. */
    public function suggested(Request $request): JsonResponse
    {
        $user = $request->user();

        // A user's skills map to work categories; suggest works in those categories.
        $categoryIds = $user->skills()->whereNotNull('category_id')
            ->pluck('category_id')->unique()->values();

        $base = fn () => Work::with(['category', 'subcategory'])
            ->where('work_status', 1)->where('approval_status', 1);

        $works = collect();
        if ($categoryIds->isNotEmpty()) {
            $works = $base()->whereIn('category_id', $categoryIds)->latest()->limit(10)->get();
        }

        // Fall back to the latest works when the user has no skills or no matches.
        if ($works->isEmpty()) {
            $works = $base()->latest()->limit(10)->get();
        }

        return response()->json([
            'data'         => $works->map(fn ($w) => $this->workResource($w)),
            'skill_based'  => $categoryIds->isNotEmpty() && $works->isNotEmpty(),
        ]);
    }

    /** GET /api/v1/categories */
    public function categories(): JsonResponse
    {
        $cats = WorkCategory::where('status', 1)
            ->withCount(['works' => fn ($q) => $q->where('work_status', 1)->where('approval_status', 1)])
            ->with(['subcategories' => fn ($q) => $q->where('status', 1)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return response()->json(['categories' => $cats->map(fn ($c) => [
            'id'          => $c->id,
            'name'        => $c->name,
            'icon'        => $c->icon,
            'works_count' => $c->works_count,
            'subcategories' => $c->subcategories->map(fn ($s) => [
                'id'   => $s->id,
                'name' => $s->name,
            ]),
        ])]);
    }

    /** POST /api/v1/works — authenticated */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $gs   = gs();

        $data = $request->validate([
            'title'              => ['required', 'string', 'max:200'],
            'category_id'        => ['required', 'exists:work_categories,id'],
            'subcategory_id'     => ['nullable', 'exists:work_subcategories,id'],
            'description'        => ['required', 'string', 'min:50'],
            'worker_slots'       => ['required', 'integer', 'min:1', 'max:10000'],
            'coins_per_worker'   => ['required', 'numeric', 'min:1'],
            'avg_minutes'        => ['nullable', 'integer', 'min:1'],
            'expires_at'         => ['nullable', 'date', 'after:today'],
            'auto_approve_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'requires_kyc'       => ['sometimes', 'boolean'],
            'cover_image'        => ['nullable', 'image', 'max:2048'],
        ]);

        $totalCoins = $data['worker_slots'] * $data['coins_per_worker'];

        if ($user->coin_balance < $totalCoins) {
            return response()->json([
                'message' => 'Insufficient balance. You need ' . number_format($totalCoins, 2) . ' coins.',
            ], 422);
        }

        $coverImage = null;
        if ($request->hasFile('cover_image')) {
            $coverImage = uploadFile($request->file('cover_image'), config('jobstation.upload_paths.work_cover'));
        }

        $slug = Str::slug($data['title']) . '-' . strtolower(Str::random(6));

        try {
            $work = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data, $totalCoins, $slug, $coverImage) {
                // Lock + re-check balance atomically to prevent concurrent overspend.
                $locked = \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                if ($locked->coin_balance < $totalCoins) {
                    throw new \RuntimeException('insufficient');
                }

                $work = Work::create([
                    'poster_id'        => $user->id,
                    'poster_type'      => 0, // user
                    'category_id'      => $data['category_id'],
                    'subcategory_id'   => $data['subcategory_id'] ?? null,
                    'slug'             => $slug,
                    'title'            => $data['title'],
                    'description'      => $data['description'],
                    'worker_slots'     => $data['worker_slots'],
                    'coins_per_worker' => $data['coins_per_worker'],
                    'total_coins'      => $totalCoins,
                    'avg_minutes'        => $data['avg_minutes'] ?? null,
                    'expires_at'         => $data['expires_at'] ?? null,
                    'auto_approve_hours' => $data['auto_approve_hours'] ?? null,
                    'requires_kyc'       => $data['requires_kyc'] ?? false,
                    'cover_image'        => $coverImage,
                    'work_status'        => 1,
                    'approval_status'    => 0, // pending admin approval
                ]);

                // Deduct coins (held until work is finished/rejected) + record the ledger entry.
                $locked->decrement('coin_balance', $totalCoins);
                $locked->refresh();
                $user->coin_balance = $locked->coin_balance;

                \App\Models\LedgerEntry::create([
                    'user_id'       => $user->id,
                    'coins'         => $totalCoins,
                    'fee'           => 0,
                    'balance_after' => $locked->coin_balance,
                    'entry_type'    => '-',
                    'category'      => 'work_spend',
                    'reference'     => $work->slug,
                    'description'   => 'Budget held for: ' . $work->title,
                ]);

                return $work;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Insufficient balance. You need ' . number_format($totalCoins, 2) . ' coins.',
            ], 422);
        }

        return response()->json([
            'message' => 'Work submitted for review.',
            'work'    => $this->workResource($work),
        ], 201);
    }

    /** GET /api/v1/my-works */
    public function myWorks(Request $request): JsonResponse
    {
        $works = $request->user()
            ->works()
            ->with(['category'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $works->map(fn ($w) => $this->workResource($w)),
            'meta' => [
                'current_page' => $works->currentPage(),
                'last_page'    => $works->lastPage(),
                'total'        => $works->total(),
            ],
        ]);
    }

    /** DELETE /api/v1/my-works/{id} */
    public function delete(Request $request, int $id): JsonResponse
    {
        $work = $request->user()->works()->findOrFail($id);

        if ($work->submissions()->whereIn('status', [1, 2])->exists()) {
            return response()->json(['message' => 'Cannot delete a work with active or approved submissions.'], 422);
        }

        // Refund unspent coins
        $approved  = $work->approvedSubmissions()->count();
        $refund    = max(0, ($work->worker_slots - $approved)) * $work->coins_per_worker;
        if ($refund > 0) {
            $request->user()->increment('coin_balance', $refund);
        }

        $work->delete();

        return response()->json(['message' => 'Work deleted.']);
    }

    /** POST /api/v1/works/{slug}/apply */
    public function apply(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();
        $work = Work::where('slug', $slug)
            ->where('work_status', 1)
            ->where('approval_status', 1)
            ->firstOrFail();

        if ($work->poster_id === $user->id && $work->poster_type === 0) {
            return response()->json(['message' => 'You cannot apply to your own work.'], 422);
        }

        if ($work->requires_kyc && (int) $user->kyc_status !== 1) {
            return response()->json(['message' => 'This task requires identity (KYC) verification. Please complete KYC first.'], 403);
        }

        if (WorkSubmission::where('work_id', $work->id)->where('worker_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already applied to this work.'], 422);
        }

        $taken = WorkSubmission::where('work_id', $work->id)->count();
        if ($taken >= $work->worker_slots) {
            return response()->json(['message' => 'All worker slots are taken.'], 422);
        }

        $submission = WorkSubmission::create([
            'work_id'          => $work->id,
            'work_poster_id'   => $work->poster_id,
            'work_poster_type' => $work->poster_type,
            'worker_id'        => $user->id,
            'worker_type'      => 2, // app user
            'status'           => 0, // applied/started — worker still owes proof (matches web)
            'deadline_at'      => $work->auto_approve_hours
                ? now()->addHours($work->auto_approve_hours)
                : null,
        ]);

        return response()->json([
            'message'       => 'Applied successfully.',
            'submission_id' => $submission->id,
        ], 201);
    }

    /** DELETE /api/v1/works/{slug}/apply — end a started work, freeing the slot. */
    public function cancel(Request $request, string $slug): JsonResponse
    {
        $work = Work::where('slug', $slug)->firstOrFail();

        $submission = WorkSubmission::where('work_id', $work->id)
            ->where('worker_id', $request->user()->id)
            ->first();

        if (! $submission) {
            return response()->json(['message' => 'You have not started this work.'], 404);
        }

        // Only a freshly-started submission (no proof yet) can be ended; once
        // proof is submitted (status >= 1) it is locked for review.
        if ($submission->status != 0) {
            return response()->json(['message' => 'This work is already under review and cannot be ended.'], 422);
        }

        $submission->delete();

        return response()->json(['message' => 'Work ended.']);
    }

    private function workResource(Work $work, bool $detailed = false): array
    {
        $slotsTaken = $work->submissions()->count();

        $data = [
            'id'               => $work->id,
            'slug'             => $work->slug,
            'title'            => $work->title,
            'category'         => $work->category?->name,
            'subcategory'      => $work->subcategory?->name,
            'coins_per_worker' => (float) $work->coins_per_worker,
            'worker_slots'     => $work->worker_slots,
            'slots_taken'      => $slotsTaken,
            'slots_remaining'  => max(0, $work->worker_slots - $slotsTaken),
            'avg_minutes'      => $work->avg_minutes,
            'difficulty'       => $this->difficultyFromMinutes($work->avg_minutes),
            'requires_kyc'     => (bool) $work->requires_kyc,
            'work_status'      => $work->work_status,
            'approval_status'  => $work->approval_status,
            'expires_at'       => $work->expires_at,
            'cover_image'      => $work->cover_image
                ? fileUrl(config('jobstation.upload_paths.work_cover'), $work->cover_image)
                : null,
            'created_at'       => $work->created_at,
        ];

        if ($detailed) {
            $data['description'] = $work->description;
            $data['total_coins'] = (float) $work->total_coins;
        }

        return $data;
    }

    private function difficultyFromMinutes(?int $minutes): ?string
    {
        if ($minutes === null) return null;
        if ($minutes <= 10) return 'Easy';
        if ($minutes <= 30) return 'Medium';
        return 'Hard';
    }
}
