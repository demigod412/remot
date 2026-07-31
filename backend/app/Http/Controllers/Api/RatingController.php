<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * POST /api/v1/contracts/{id}/rate
     *
     * Rate the other party on a completed contract.
     * Ratings use blind reveal: neither party sees the other's score
     * until both have submitted.
     */
    public function rateContract(Request $request, int $id): JsonResponse
    {
        $me = $request->user();

        $contract = Contract::where('id', $id)
            ->where('status', 3) // completed only
            ->where(fn ($q) => $q->where('employer_id', $me->id)->orWhere('worker_id', $me->id))
            ->firstOrFail();

        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        $rateeId = $contract->employer_id === $me->id
            ? $contract->worker_id
            : $contract->employer_id;

        // Prevent duplicate rating for the same contract
        $alreadyRated = Rating::where('rater_id', $me->id)
            ->where('ratable_id', $contract->id)
            ->where('ratable_type', Contract::class)
            ->exists();

        if ($alreadyRated) {
            return response()->json(['message' => 'You have already rated this contract.'], 422);
        }

        Rating::create([
            'rater_id'     => $me->id,
            'ratee_id'     => $rateeId,
            'ratable_id'   => $contract->id,
            'ratable_type' => Contract::class,
            'rating'       => $request->rating,
            'review'       => $request->review,
        ]);

        // Check if the other party has also rated (blind reveal)
        $counterExists = Rating::where('rater_id', $rateeId)
            ->where('ratable_id', $contract->id)
            ->where('ratable_type', Contract::class)
            ->exists();

        return response()->json([
            'message'    => 'Rating submitted.',
            'revealed'   => $counterExists, // true when both parties have rated
        ], 201);
    }

    /**
     * GET /api/v1/users/{username}
     *
     * Public user profile with revealed ratings.
     */
    public function publicProfile(Request $request, string $username): JsonResponse
    {
        $user = User::where('username', $username)->where('status', 1)->firstOrFail();

        $ratingsQuery = Rating::where('ratee_id', $user->id)
            ->whereExists(function ($q) {
                $q->from('ratings as r2')
                    ->whereColumn('r2.rater_id', 'ratings.ratee_id')
                    ->whereColumn('r2.ratee_id', 'ratings.rater_id')
                    ->whereColumn('r2.ratable_id', 'ratings.ratable_id')
                    ->whereColumn('r2.ratable_type', 'ratings.ratable_type');
            })
            ->with('rater:id,username,firstname,lastname,image');

        $ratings = $ratingsQuery->latest()->paginate(10);

        $ratingData = $user->publicRatingData();

        $worksCompleted = \App\Models\WorkSubmission::where('worker_id', $user->id)
            ->where('status', 3)
            ->count();

        $contractsCompleted = Contract::where('worker_id', $user->id)
            ->where('status', 3)
            ->count();

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'username'   => $user->username,
                'firstname'  => $user->firstname,
                'lastname'   => $user->lastname,
                'kyc_status' => $user->kyc_status,
                'avatar'     => $user->image
                    ? fileUrl(config('jobstation.upload_paths.user_avatar'), $user->image)
                    : null,
                'joined_at'  => $user->created_at,
            ],
            'stats' => [
                'rating_avg'          => $ratingData['avg'],
                'rating_count'        => $ratingData['count'],
                'works_completed'     => $worksCompleted,
                'contracts_completed' => $contractsCompleted,
            ],
            'ratings' => [
                'data' => $ratings->map(fn ($r) => [
                    'id'         => $r->id,
                    'rating'     => $r->rating,
                    'review'     => $r->review,
                    'rater'      => $r->rater ? [
                        'username'  => $r->rater->username,
                        'firstname' => $r->rater->firstname,
                        'avatar'    => $r->rater->image
                            ? fileUrl(config('jobstation.upload_paths.user_avatar'), $r->rater->image)
                            : null,
                    ] : null,
                    'created_at' => $r->created_at,
                ]),
                'meta' => [
                    'current_page' => $ratings->currentPage(),
                    'last_page'    => $ratings->lastPage(),
                    'total'        => $ratings->total(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/contracts/{id}/my-rating
     *
     * Return the authenticated user's own rating for a contract (if submitted).
     */
    public function myContractRating(Request $request, int $id): JsonResponse
    {
        $me = $request->user();

        $rating = Rating::where('rater_id', $me->id)
            ->where('ratable_id', $id)
            ->where('ratable_type', Contract::class)
            ->first();

        return response()->json([
            'rated'    => (bool) $rating,
            'rating'   => $rating?->rating,
            'review'   => $rating?->review,
            'revealed' => $rating ? $rating->isRevealed() : false,
        ]);
    }
}
