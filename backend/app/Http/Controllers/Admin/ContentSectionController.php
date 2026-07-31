<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentSection;
use Illuminate\Http\Request;

class ContentSectionController extends Controller
{
    public function index()
    {
        $sections = ContentSection::orderBy('section_key')->get();
        return view('admin.content.sections', compact('sections'));
    }

    public function edit(int $id)
    {
        $section = ContentSection::findOrFail($id);
        return view('admin.content.edit-section', compact('section'));
    }

    public function update(Request $request, int $id)
    {
        $section = ContentSection::findOrFail($id);

        $request->validate([
            'section_data' => ['required', 'string'],
        ]);

        $decoded = json_decode($request->input('section_data'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['section_data' => 'Invalid JSON format.'])->withInput();
        }

        $section->update(['section_data' => $decoded]);

        return back()->with('success', 'Section updated successfully.');
    }
}
