<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index()
    {
        $plugins = Plugin::orderBy('name')->get();
        return view('admin.plugins.index', compact('plugins'));
    }

    public function update(Request $request, int $id)
    {
        $plugin = Plugin::findOrFail($id);

        $request->validate([
            'script'      => ['nullable', 'string', 'max:5000'],
            'shortcode'   => ['nullable', 'array'],
            'shortcode.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [];
        if ($request->has('script')) {
            $payload['script'] = $request->input('script');
        }
        if ($request->has('shortcode')) {
            // Merge so any keys the form didn't render are preserved.
            $payload['shortcode'] = array_merge($plugin->shortcode ?? [], $request->input('shortcode', []));
        }

        $plugin->update($payload);

        return back()->with('success', 'Plugin updated.');
    }

    public function toggleStatus(int $id)
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->status = $plugin->status ? 0 : 1;
        $plugin->save();
        return back()->with('success', 'Plugin status updated.');
    }
}
