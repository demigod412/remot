<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use Illuminate\Http\Request;

class WorkCategoryController extends Controller
{
    public function index()
    {
        $categories = WorkCategory::withCount(['works', 'subcategories'])->orderBy('name')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:work_categories,name'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);
        $data['status'] = 1;
        WorkCategory::create($data);
        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, int $id)
    {
        $cat  = WorkCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:work_categories,name,{$id}"],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);
        $cat->update($data);
        return back()->with('success', 'Category updated.');
    }

    public function destroy(int $id)
    {
        $cat = WorkCategory::findOrFail($id);
        if ($cat->works()->count()) {
            return back()->with('error', 'Cannot delete category with existing works.');
        }
        $cat->subcategories()->delete();
        $cat->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function toggleStatus(int $id)
    {
        $cat = WorkCategory::findOrFail($id);
        $cat->status = $cat->status ? 0 : 1;
        $cat->save();
        return back()->with('success', 'Category status updated.');
    }

    // -------------------------------------------------------------------------
    // Subcategories (JSON endpoint used by create/edit work forms)
    // -------------------------------------------------------------------------

    public function subcategories(int $id)
    {
        $subs = WorkSubcategory::where('category_id', $id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($subs);
    }

    public function storeSubcategory(Request $request, int $id)
    {
        WorkCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);
        $data['category_id'] = $id;
        $data['status']      = 1;
        WorkSubcategory::create($data);
        return back()->with('success', 'Subcategory added.');
    }

    public function updateSubcategory(Request $request, int $sid)
    {
        $sub  = WorkSubcategory::findOrFail($sid);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);
        $sub->update($data);
        return back()->with('success', 'Subcategory updated.');
    }

    public function destroySubcategory(int $sid)
    {
        $sub = WorkSubcategory::findOrFail($sid);
        if ($sub->works()->count()) {
            return back()->with('error', 'Cannot delete subcategory with existing works.');
        }
        $sub->delete();
        return back()->with('success', 'Subcategory deleted.');
    }
}
