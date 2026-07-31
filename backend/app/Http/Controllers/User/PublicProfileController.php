<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Rating;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkSubmission;
use App\Services\RankService;

class PublicProfileController extends Controller
{
    public function show(string $username)
    {
        $profile = User::where('username', $username)
            ->where('status', 1)
            ->firstOrFail();

        $tasksCompleted     = WorkSubmission::where('worker_id', $profile->id)->where('status', 2)->count();
        $worksPosted        = Work::where('poster_id', $profile->id)->where('poster_type', 2)->count();
        $contractsAsWorker  = Contract::where('worker_id', $profile->id)->where('status', 3)->count();
        $contractsAsEmployer = Contract::where('employer_id', $profile->id)->where('status', 3)->count();

        // Collect all ratings this user received
        $allReceived = Rating::where('ratee_id', $profile->id)->with('rater')->get();

        // Blind reveal: only show a rating if the counterpart has also rated
        $revealedRatings = $allReceived->filter(function (Rating $r) {
            return Rating::where('rater_id', $r->ratee_id)
                ->where('ratee_id', $r->rater_id)
                ->where('ratable_id', $r->ratable_id)
                ->where('ratable_type', $r->ratable_type)
                ->exists();
        })->values();

        $avgRating    = $revealedRatings->avg('rating') ?? 0;
        $totalReviews = $revealedRatings->count();

        $rank = RankService::getLevel($profile);

        return view('web.profile.show', compact(
            'profile',
            'tasksCompleted',
            'worksPosted',
            'contractsAsWorker',
            'contractsAsEmployer',
            'revealedRatings',
            'avgRating',
            'totalReviews',
            'rank'
        ));
    }
}
