<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobBookmark;
use App\Models\JobListing;
use App\Models\Work;
use App\Models\WorkBookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    // ── Jobs ─────────────────────────────────────────────────────────────────

    /** GET /api/v1/jobs/bookmarks */
    public function jobBookmarks(Request $request): JsonResponse
    {
        $bookmarks = $request->user()
            ->jobBookmarks()
            ->with(['jobListing.category', 'jobListing.employer'])
            ->latest()
            ->paginate(15);

        $locTypes = [1 => 'Remote', 2 => 'On-site', 3 => 'Hybrid'];
        $empTypes = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'freelance' => 'Freelance'];

        return response()->json([
            'data' => $bookmarks->map(function ($b) use ($locTypes, $empTypes) {
                $l = $b->jobListing;
                if (! $l) return null;
                return [
                    'bookmark_id'     => $b->id,
                    'id'              => $l->id,
                    'title'           => $l->title,
                    'location_type'   => $l->location_type,
                    'location_label'  => $locTypes[$l->location_type] ?? null,
                    'employment_type' => $l->employment_type,
                    'employment_label'=> $empTypes[$l->employment_type] ?? null,
                    'is_featured'     => $l->is_featured,
                    'closes_at'       => $l->closes_at,
                    'created_at'      => $l->created_at,
                    'category'        => $l->category ? ['id' => $l->category->id, 'name' => $l->category->name] : null,
                    'employer'        => $l->employer ? ['id' => $l->employer->id, 'name' => $l->employer->fullname] : null,
                ];
            })->filter()->values(),
            'meta' => [
                'current_page' => $bookmarks->currentPage(),
                'last_page'    => $bookmarks->lastPage(),
                'total'        => $bookmarks->total(),
            ],
        ]);
    }

    /** POST /api/v1/jobs/{id}/bookmark — toggle */
    public function toggleJobBookmark(Request $request, int $id): JsonResponse
    {
        JobListing::open()->findOrFail($id);

        $user     = $request->user();
        $existing = JobBookmark::where('user_id', $user->id)->where('job_listing_id', $id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['bookmarked' => false, 'message' => 'Job removed from bookmarks.']);
        }

        JobBookmark::create(['user_id' => $user->id, 'job_listing_id' => $id]);
        return response()->json(['bookmarked' => true, 'message' => 'Job bookmarked.'], 201);
    }

    // ── Works ─────────────────────────────────────────────────────────────────

    /** GET /api/v1/works/bookmarks */
    public function workBookmarks(Request $request): JsonResponse
    {
        $bookmarks = $request->user()
            ->workBookmarks()
            ->with(['work.category'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $bookmarks->map(function ($b) {
                $w = $b->work;
                if (! $w) return null;
                return [
                    'bookmark_id'      => $b->id,
                    'id'               => $w->id,
                    'slug'             => $w->slug,
                    'title'            => $w->title,
                    'category'         => $w->category?->name,
                    'coins_per_worker' => (float) $w->coins_per_worker,
                    'worker_slots'     => $w->worker_slots,
                    'avg_minutes'      => $w->avg_minutes,
                    'expires_at'       => $w->expires_at,
                    'cover_image'      => $w->cover_image
                        ? fileUrl(config('jobstation.upload_paths.work_cover'), $w->cover_image)
                        : null,
                    'created_at'       => $w->created_at,
                ];
            })->filter()->values(),
            'meta' => [
                'current_page' => $bookmarks->currentPage(),
                'last_page'    => $bookmarks->lastPage(),
                'total'        => $bookmarks->total(),
            ],
        ]);
    }

    /** POST /api/v1/works/{id}/bookmark — toggle */
    public function toggleWorkBookmark(Request $request, int $id): JsonResponse
    {
        Work::where('work_status', 1)->where('approval_status', 1)->findOrFail($id);

        $user     = $request->user();
        $existing = WorkBookmark::where('user_id', $user->id)->where('work_id', $id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['bookmarked' => false, 'message' => 'Work removed from bookmarks.']);
        }

        WorkBookmark::create(['user_id' => $user->id, 'work_id' => $id]);
        return response()->json(['bookmarked' => true, 'message' => 'Work bookmarked.'], 201);
    }
}
