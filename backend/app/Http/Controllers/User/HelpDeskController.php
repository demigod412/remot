<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\HelpFile;
use App\Models\HelpMessage;
use App\Models\HelpTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpDeskController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::guard('web')->user();
        $query = $user->helpTickets()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(config('jobstation.per_page'));

        return view('user.helpdesk.index', compact('tickets'));
    }

    public function create()
    {
        return view('user.helpdesk.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'      => ['required', 'string', 'max:200'],
            'message'      => ['required', 'string', 'min:20'],
            'priority'     => ['required', 'in:1,2,3'],
            'attachments'  => ['nullable', 'array', 'max:3'],
            'attachments.*'=> ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
        ]);

        $user = Auth::guard('web')->user();

        $ticket = HelpTicket::create([
            'user_id'       => $user->id,
            'name'          => $user->fullname ?: $user->username,
            'email'         => $user->email,
            'ticket_number' => 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'subject'       => $request->subject,
            'priority'      => (int) $request->priority,
            'status'        => 0,
        ]);

        $message = HelpMessage::create([
            'help_ticket_id' => $ticket->id,
            'admin_id'       => null,
            'message'        => $request->message,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = uploadPrivateFile($file, 'helpdesk');
                HelpFile::create([
                    'help_message_id' => $message->id,
                    'attachment'      => $filename,
                ]);
            }
        }

        foreach (Admin::all() as $admin) {
            AdminNotification::notify($admin->id, 'New Support Ticket',
                "{$user->fullname} opened ticket #{$ticket->ticket_number}: \"{$request->subject}\"",
                'info', route('admin.tickets.show', $ticket->id));
        }

        return redirect()->route('user.helpdesk.index')
            ->with('success', 'Ticket #' . $ticket->ticket_number . ' created. We\'ll respond shortly.');
    }

    public function show(string $number)
    {
        $user   = Auth::guard('web')->user();
        $ticket = HelpTicket::where('ticket_number', $number)
            ->where('user_id', $user->id)
            ->with(['messages.files'])
            ->firstOrFail();

        return view('user.helpdesk.show', compact('ticket'));
    }

    public function reply(Request $request, string $number)
    {
        $request->validate([
            'message'      => ['required', 'string', 'min:5'],
            'attachments'  => ['nullable', 'array', 'max:3'],
            'attachments.*'=> ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
        ]);

        $user   = Auth::guard('web')->user();
        $ticket = HelpTicket::where('ticket_number', $number)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($ticket->status == 3) {
            return back()->with('error', 'This ticket is closed. Please open a new ticket.');
        }

        $message = HelpMessage::create([
            'help_ticket_id' => $ticket->id,
            'admin_id'       => null,
            'message'        => $request->message,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = uploadPrivateFile($file, 'helpdesk');
                HelpFile::create([
                    'help_message_id' => $message->id,
                    'attachment'      => $filename,
                ]);
            }
        }

        // If admin had answered, mark as customer reply
        if ($ticket->status == 1) {
            $ticket->update(['status' => 2]);
        }

        return back()->with('success', 'Reply sent.');
    }

    public function close(string $number)
    {
        $user   = Auth::guard('web')->user();
        $ticket = HelpTicket::where('ticket_number', $number)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $ticket->update(['status' => 3]);

        return redirect()->route('user.helpdesk.index')
            ->with('success', 'Ticket closed.');
    }

    public function download(int $id)
    {
        $user = Auth::guard('web')->user();
        $file = HelpFile::findOrFail($id);

        $message = HelpMessage::findOrFail($file->help_message_id);
        HelpTicket::where('id', $message->help_ticket_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $path = 'helpdesk/' . $file->attachment;
        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($path, $file->attachment);
    }
}
