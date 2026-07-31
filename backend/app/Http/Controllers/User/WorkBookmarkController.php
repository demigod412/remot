<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkBookmark;
use Illuminate\Support\Facades\Auth;

class WorkBookmarkController extends Controller
{
    public function toggle(int $id)
    {
        $user = Auth::guard('web')->user();
        $work = Work::where('approval_status', 1)->findOrFail($id);

        $existing = WorkBookmark::where('user_id', $user->id)
            ->where('work_id', $work->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $bookmarked = false;
        } else {
            WorkBookmark::create([
                'user_id' => $user->id,
                'work_id' => $work->id,
            ]);
            $bookmarked = true;
        }

        if (request()->expectsJson()) {
            return response()->json(['bookmarked' => $bookmarked]);
        }

        return back()->with('success', $bookmarked ? 'Work saved.' : 'Work removed from saved.');
    }

    public function index()
    {
        $user = Auth::guard('web')->user();

        $bookmarks = WorkBookmark::where('user_id', $user->id)
            ->with(['work.category'])
            ->latest()
            ->paginate(15);

        return view('user.works.bookmarks', compact('bookmarks'));
    }
}
