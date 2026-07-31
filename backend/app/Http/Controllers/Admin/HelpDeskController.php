<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpFile;
use App\Models\HelpMessage;
use App\Models\HelpTicket;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpDeskController extends Controller
{
    public function index(Request $request)
    {
        $query = HelpTicket::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $tickets = $query->latest('last_reply_at')->paginate(config('jobstation.per_page', 20))->withQueryString();

        $stats = [
            'open'     => HelpTicket::where('status', 0)->count(),
            'answered' => HelpTicket::where('status', 1)->count(),
            'replied'  => HelpTicket::where('status', 2)->count(),
            'closed'   => HelpTicket::where('status', 3)->count(),
        ];

        return view('admin.helpdesk.index', compact('tickets', 'stats'));
    }

    public function show(int $id)
    {
        $ticket   = HelpTicket::with(['user', 'messages.files'])->findOrFail($id);
        $messages = $ticket->messages()->with('files')->orderBy('created_at')->get();

        // Mark as answered when admin views an open ticket
        if ($ticket->status === 0) {
            $ticket->update(['status' => 1]);
        }

        return view('admin.helpdesk.show', compact('ticket', 'messages'));
    }

    public function reply(Request $request, int $id)
    {
        $ticket = HelpTicket::findOrFail($id);

        $data = $request->validate([
            'message'     => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:4096'],
        ]);

        $message = HelpMessage::create([
            'help_ticket_id' => $ticket->id,
            'admin_id'       => Auth::guard('admin')->id(),
            'message'        => $data['message'],
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

        $ticket->update([
            'status'        => 1,
            'last_reply_at' => now(),
        ]);

        $ticket->load('user');
        if ($ticket->user) {
            NotifyService::send($ticket->user, 'TICKET_REPLY', [
                'ticket_number' => $ticket->ticket_number,
                'subject'       => $ticket->subject,
            ]);
        }

        return back()->with('success', 'Reply sent.');
    }

    public function close(Request $request, int $id)
    {
        $ticket = HelpTicket::findOrFail($id);
        $ticket->update(['status' => 3]);
        return back()->with('success', 'Ticket closed.');
    }

    public function destroy(int $id)
    {
        $ticket = HelpTicket::with('messages.files')->findOrFail($id);

        foreach ($ticket->messages as $msg) {
            foreach ($msg->files as $file) {
                removePrivateFile('helpdesk', $file->attachment);
                $file->delete();
            }
            $msg->delete();
        }

        $ticket->delete();

        return redirect()->route('admin.tickets.index')->with('success', 'Ticket deleted.');
    }
}
