<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelpFile;
use App\Models\HelpMessage;
use App\Models\HelpTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpDeskController extends Controller
{
    /** GET /api/v1/tickets */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->helpTickets()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(15);

        return response()->json([
            'data' => $tickets->map(fn ($t) => $this->ticketResource($t)),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'total'        => $tickets->total(),
            ],
        ]);
    }

    /** POST /api/v1/tickets */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'subject'       => ['required', 'string', 'max:200'],
            'message'       => ['required', 'string', 'min:20'],
            'priority'      => ['required', 'in:1,2,3'],
            'attachments'   => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
        ]);

        $user = $request->user();

        $ticket = HelpTicket::create([
            'user_id'       => $user->id,
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

        return response()->json([
            'message' => 'Ticket created successfully.',
            'ticket'  => $this->ticketResource($ticket),
        ], 201);
    }

    /** GET /api/v1/tickets/{number} */
    public function show(Request $request, string $number): JsonResponse
    {
        $user   = $request->user();
        $ticket = HelpTicket::where('ticket_number', $number)
            ->where('user_id', $user->id)
            ->with(['messages.files'])
            ->firstOrFail();

        return response()->json([
            'ticket'   => $this->ticketResource($ticket),
            'messages' => $ticket->messages->map(fn ($m) => $this->messageResource($m)),
        ]);
    }

    /** POST /api/v1/tickets/{number}/reply */
    public function reply(Request $request, string $number): JsonResponse
    {
        $request->validate([
            'message'       => ['required', 'string', 'min:5'],
            'attachments'   => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,txt', 'max:5120'],
        ]);

        $user   = $request->user();
        $ticket = HelpTicket::where('ticket_number', $number)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($ticket->status == 3) {
            return response()->json(['message' => 'This ticket is closed.'], 422);
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

        if ($ticket->status == 1) {
            $ticket->update(['status' => 2]);
        }

        return response()->json([
            'message' => 'Reply sent.',
            'reply'   => $this->messageResource($message->load('files')),
        ]);
    }

    /** POST /api/v1/tickets/{number}/close */
    public function close(Request $request, string $number): JsonResponse
    {
        $ticket = HelpTicket::where('ticket_number', $number)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $ticket->update(['status' => 3]);

        return response()->json(['message' => 'Ticket closed.']);
    }

    private function ticketResource(HelpTicket $t): array
    {
        return [
            'id'            => $t->id,
            'ticket_number' => $t->ticket_number,
            'subject'       => $t->subject,
            'priority'      => $t->priority,
            'status'        => $t->status,
            'created_at'    => $t->created_at,
            'updated_at'    => $t->updated_at,
        ];
    }

    private function messageResource(HelpMessage $m): array
    {
        return [
            'id'         => $m->id,
            'message'    => $m->message,
            'by_admin'   => ! is_null($m->admin_id),
            'files'      => ($m->files ?? collect())->map(fn ($f) => [
                'id'  => $f->id,
                'url' => \Illuminate\Support\Facades\URL::temporarySignedRoute('secure.helpFile', now()->addMinutes(30), $f->id),
            ])->values()->all(),
            'created_at' => $m->created_at,
        ];
    }
}
