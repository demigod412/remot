<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\WorkCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SkillController extends Controller
{
    public function index(Request $request)
    {
        $query = Skill::with('category');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $skills     = $query->orderBy('name')->paginate(config('jobstation.per_page'))->withQueryString();
        $categories = WorkCategory::orderBy('name')->get();

        return view('admin.skills.index', compact('skills', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:60', 'unique:skills,name'],
            'category_id' => ['nullable', 'exists:work_categories,id'],
        ]);

        Skill::create([
            'name'        => $data['name'],
            'slug'        => Str::slug($data['name']),
            'category_id' => $data['category_id'] ?? null,
            'status'      => true,
        ]);

        return back()->with('success', 'Skill created.');
    }

    public function update(Request $request, int $id)
    {
        $skill = Skill::findOrFail($id);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:60', "unique:skills,name,{$id}"],
            'category_id' => ['nullable', 'exists:work_categories,id'],
        ]);

        $skill->update([
            'name'        => $data['name'],
            'slug'        => Str::slug($data['name']),
            'category_id' => $data['category_id'] ?? null,
        ]);

        return back()->with('success', 'Skill updated.');
    }

    public function toggleStatus(int $id)
    {
        $skill = Skill::findOrFail($id);
        $skill->update(['status' => ! $skill->status]);
        return back()->with('success', 'Skill status updated.');
    }

    public function destroy(int $id)
    {
        Skill::findOrFail($id)->delete();
        return back()->with('success', 'Skill deleted.');
    }
}
