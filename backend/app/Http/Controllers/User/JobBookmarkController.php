<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JobBookmark;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobBookmarkController extends Controller
{
    public function toggle(int $id)
    {
        $user    = Auth::guard('web')->user();
        $listing = JobListing::where('status', 1)->findOrFail($id);

        $existing = JobBookmark::where('user_id', $user->id)
            ->where('job_listing_id', $listing->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $bookmarked = false;
        } else {
            JobBookmark::create([
                'user_id'        => $user->id,
                'job_listing_id' => $listing->id,
            ]);
            $bookmarked = true;
        }

        if (request()->expectsJson()) {
            return response()->json(['bookmarked' => $bookmarked]);
        }

        return back()->with('success', $bookmarked ? 'Job saved.' : 'Job removed from saved.');
    }

    public function index()
    {
        $user = Auth::guard('web')->user();

        $bookmarks = JobBookmark::where('user_id', $user->id)
            ->with(['jobListing.category', 'jobListing.employer'])
            ->latest()
            ->paginate(12);

        return view('user.jobs.bookmarks', compact('bookmarks'));
    }
}
