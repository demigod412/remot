<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::orderBy('name')->paginate(10);
        return view('admin.notif-templates.index', compact('templates'));
    }

    public function edit(int $id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return view('admin.notif-templates.edit', compact('template'));
    }

    public function update(Request $request, int $id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $data = $request->validate([
            'subj'         => ['required', 'string', 'max:200'],
            'email_body'   => ['nullable', 'string'],
            'sms_body'     => ['nullable', 'string', 'max:500'],
            'email_status' => ['nullable', 'boolean'],
            'sms_status'   => ['nullable', 'boolean'],
        ]);
        $data['email_status'] = $request->boolean('email_status');
        $data['sms_status']   = $request->boolean('sms_status');
        $template->update($data);
        return redirect()->route('admin.notif-templates.index')->with('success', 'Template updated.');
    }
}
