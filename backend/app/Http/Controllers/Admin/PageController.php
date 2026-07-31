<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        $pages = SitePage::orderBy('name')->get();
        return view('admin.pages.index', compact('pages'));
    }

    public function edit(int $id)
    {
        $page = SitePage::findOrFail($id);
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, int $id)
    {
        $page = SitePage::findOrFail($id);

        $request->validate([
            'name'               => ['required', 'string', 'max:100'],
            'secs'               => ['nullable', 'array'],
            'secs.*.heading'     => ['nullable', 'string', 'max:200'],
            'secs.*.content'     => ['nullable', 'string'],
        ]);

        // Re-index and strip empty sections
        $secs = collect($request->input('secs', []))
            ->filter(fn($s) => !empty(trim($s['content'] ?? '')))
            ->values()
            ->all();

        $page->update([
            'name' => $request->input('name'),
            'secs' => $secs,
        ]);

        return redirect()->route('admin.pages.edit', $page->id)->with('success', 'Page saved successfully.');
    }
}
