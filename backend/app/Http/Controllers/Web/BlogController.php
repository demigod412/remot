<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SitePage;

class BlogController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = SitePage::where('tempname', 'blog');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereJsonContains('secs', $search);
            });
        }

        $posts = $query->latest()->paginate(9)->withQueryString();
        $search = $request->input('search', '');

        return view('web.blog.index', compact('posts', 'search'));
    }

    public function show(int $id)
    {
        $post = SitePage::where('id', $id)
            ->where('tempname', 'blog')
            ->firstOrFail();

        $recent = SitePage::where('tempname', 'blog')
            ->where('id', '!=', $id)
            ->latest()
            ->limit(4)
            ->get();

        return view('web.blog.show', compact('post', 'recent'));
    }
}
