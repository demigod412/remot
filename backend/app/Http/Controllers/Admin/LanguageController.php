<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = Language::orderBy('is_default', 'desc')->orderBy('name')->get();
        return view('admin.languages.index', compact('languages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'code'       => ['required', 'string', 'max:10', 'unique:languages,code'],
            'icon'       => ['nullable', 'string', 'max:10'],
            'text_align' => ['nullable', 'boolean'],
        ]);
        $data['text_align'] = $request->boolean('text_align');
        $data['is_default'] = false;
        Language::create($data);
        return back()->with('success', __('Language added.'));
    }

    public function edit(int $id)
    {
        $language = Language::findOrFail($id);
        return view('admin.languages.edit', compact('language'));
    }

    public function update(Request $request, int $id)
    {
        $lang = Language::findOrFail($id);
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'icon'       => ['nullable', 'string', 'max:10'],
            'text_align' => ['nullable', 'boolean'],
        ]);
        $data['text_align'] = $request->boolean('text_align');
        $lang->update($data);
        return redirect()->route('admin.languages.index')->with('success', __('Language updated.'));
    }

    public function destroy(int $id)
    {
        $lang = Language::findOrFail($id);
        if ($lang->is_default) {
            return back()->with('error', __('Cannot delete the default language.'));
        }
        $lang->delete();
        return back()->with('success', __('Language deleted.'));
    }

    public function setDefault(int $id)
    {
        Language::query()->update(['is_default' => false]);
        Language::findOrFail($id)->update(['is_default' => true]);
        return back()->with('success', __('Default language updated.'));
    }
}
