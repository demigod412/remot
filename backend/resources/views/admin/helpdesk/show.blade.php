@extends('admin.layouts.app')
@section('title', 'Ticket #' . $ticket->ticket_number)
@section('page-title', 'Support Ticket')

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.tickets.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Tickets</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);flex-shrink:0;"></i>
    <span style="font-family:ui-monospace,monospace;color:var(--fg-2);">#{{ $ticket->ticket_number }}</span>
    <span style="margin-left:4px;font-size:13px;font-weight:500;color:var(--fg);">— {{ $ticket->subject }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;" class="ticket-show-grid">

    {{-- ── LEFT: Thread + Reply ──────────────────────────────── --}}
    <div>

        {{-- Messages thread --}}
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;">
            @forelse($messages as $msg)
            @php $isAdmin = (bool) $msg->admin_id; @endphp
            <div class="jobstation-card" style="padding:20px;{{ $isAdmin ? 'border-left:3px solid var(--accent);' : '' }}">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                    background:{{ $isAdmin ? 'rgba(47,84,235,0.12)' : 'var(--surface-2)' }};
                                    border:1px solid {{ $isAdmin ? 'rgba(47,84,235,0.25)' : 'var(--border)' }};">
                            <i data-lucide="{{ $isAdmin ? 'shield' : 'user' }}"
                               style="width:15px;height:15px;color:{{ $isAdmin ? 'var(--accent)' : 'var(--fg-4)' }};"></i>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--fg);">
                                {{ $isAdmin ? 'Support Team' : ($ticket->name ?? $ticket->user?->fullname ?? 'User') }}
                            </div>
                            <div style="font-size:11px;color:var(--fg-3);">{{ $msg->created_at->format('M d, Y · H:i') }}</div>
                        </div>
                    </div>
                    @if($isAdmin)
                    <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:10.5px;font-weight:500;background:rgba(96,165,250,0.12);color:#60A5FA;">
                        Admin Reply
                    </span>
                    @endif
                </div>

                <div style="font-size:13.5px;color:var(--fg-2);line-height:1.75;white-space:pre-wrap;">{{ $msg->message }}</div>

                @if($msg->files->count())
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
                    <div style="font-size:11px;color:var(--fg-3);margin-bottom:8px;">Attachments</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @foreach($msg->files as $file)
                        <a href="{{ route('secure.helpFile', $file->id) }}"
                           target="_blank"
                           style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);font-size:12px;color:var(--fg-2);text-decoration:none;transition:.12s;"
                           onmouseover="this.style.background='var(--surface-3)';this.style.color='var(--fg)'"
                           onmouseout="this.style.background='var(--surface-2)';this.style.color='var(--fg-2)'">
                            <i data-lucide="{{ $file->is_image ? 'image' : 'file' }}" style="width:13px;height:13px;"></i>
                            {{ Str::limit($file->attachment, 28) }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @empty
            <div class="jobstation-card" style="padding:40px;text-align:center;color:var(--fg-3);font-size:13px;">
                <i data-lucide="message-circle" style="width:28px;height:28px;margin:0 auto 10px;display:block;opacity:0.3;"></i>
                No messages yet.
            </div>
            @endforelse
        </div>

        {{-- Reply form --}}
        @if($ticket->status !== 3)
        <div class="jobstation-card" style="padding:22px;">
            <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 16px;">Write a Reply</h3>
            <form method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom:12px;">
                    <textarea name="message" rows="5"
                              placeholder="Type your reply…"
                              style="width:100%;resize:vertical;font-size:13.5px;font-family:inherit;line-height:1.6;"
                              required>{{ old('message') }}</textarea>
                    @error('message')
                    <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Attachments (optional, max 5 files)</label>
                    <input type="file" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.txt"
                           style="width:100%;font-size:13px;font-family:inherit;">
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary" style="font-size:13px;padding:8px 20px;display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="send" style="width:14px;height:14px;"></i> Send Reply
                    </button>
                    <form method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn" style="font-size:13px;padding:8px 18px;display:inline-flex;align-items:center;gap:6px;"
                                onclick="return confirm('Close this ticket?')">
                            <i data-lucide="archive" style="width:14px;height:14px;"></i> Close Ticket
                        </button>
                    </form>
                </div>
            </form>
        </div>
        @else
        <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:10px;background:var(--surface-2);border:1px solid var(--border);font-size:13px;color:var(--fg-3);">
            <i data-lucide="archive" style="width:15px;height:15px;"></i>
            This ticket is closed.
        </div>
        @endif

    </div>

    {{-- ── RIGHT: Sidebar ────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Ticket info --}}
        <div class="jobstation-card" style="padding:18px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin:0 0 14px;">Ticket Info</h3>
            @php
                $statusMap = [
                    0 => ['label'=>'Open',           'bg'=>'rgba(96,165,250,0.12)', 'color'=>'#60A5FA'],
                    1 => ['label'=>'Answered',        'bg'=>'rgba(34,197,94,0.12)',  'color'=>'#22C55E'],
                    2 => ['label'=>'Customer Reply',  'bg'=>'rgba(245,158,11,0.12)', 'color'=>'#F59E0B'],
                    3 => ['label'=>'Closed',          'bg'=>'var(--surface-2)',      'color'=>'var(--fg-3)'],
                ];
                $prioMap = [
                    1 => ['label'=>'Low',    'bg'=>'var(--surface-2)',      'color'=>'var(--fg-3)'],
                    2 => ['label'=>'Medium', 'bg'=>'rgba(245,158,11,0.12)', 'color'=>'#F59E0B'],
                    3 => ['label'=>'High',   'bg'=>'rgba(239,68,68,0.12)',  'color'=>'#EF4444'],
                ];
                $sm = $statusMap[$ticket->status] ?? $statusMap[0];
                $pm = $prioMap[$ticket->priority]  ?? $prioMap[1];
            @endphp
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach([
                    ['Number',   '<span style="font-family:ui-monospace,monospace;font-size:12px;font-weight:700;color:var(--accent);">#'.$ticket->ticket_number.'</span>'],
                    ['Status',   '<span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:'.$sm['bg'].';color:'.$sm['color'].'">'.$sm['label'].'</span>'],
                    ['Priority', '<span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:'.$pm['bg'].';color:'.$pm['color'].'">'.$pm['label'].'</span>'],
                    ['Created',  '<span style="font-size:12px;color:var(--fg-3);">'.$ticket->created_at->format('M d, Y').'</span>'],
                    ['Messages', '<span style="font-size:12px;color:var(--fg-2);">'.$messages->count().'</span>'],
                ] as [$lbl, $val])
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:12px;color:var(--fg-3);">{{ $lbl }}</span>
                    {!! $val !!}
                </div>
                @endforeach
            </div>
        </div>

        {{-- User --}}
        <div class="jobstation-card" style="padding:18px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin:0 0 12px;">User</h3>
            @if($ticket->user)
            @php
                $uInit  = strtoupper(substr($ticket->user->firstname ?? $ticket->user->username, 0, 1));
                $uColor = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'][ord($uInit) % 5];
            @endphp
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,{{ $uColor }},{{ ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'][(ord($uInit)+2)%5] }});display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;flex-shrink:0;">
                    {{ $uInit }}
                </div>
                <div style="min-width:0;">
                    <a href="{{ route('admin.users.show', $ticket->user_id) }}"
                       style="font-size:13px;font-weight:500;color:var(--fg);text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;transition:.12s;"
                       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg)'">
                        {{ $ticket->user->fullname }}
                    </a>
                    <div style="font-size:11.5px;color:var(--fg-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ticket->email }}</div>
                </div>
            </div>
            @else
            <div style="font-size:13px;color:var(--fg-2);">{{ $ticket->name }}</div>
            <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">{{ $ticket->email }}</div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="jobstation-card" style="padding:18px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin:0 0 10px;">Actions</h3>
            @if($ticket->status !== 3)
            <form method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}" style="margin-bottom:6px;">
                @csrf
                <button type="submit"
                        style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 12px;border-radius:8px;font-size:13px;color:var(--fg-2);background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;transition:.12s;"
                        onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                        onmouseout="this.style.background='transparent';this.style.color='var(--fg-2)'"
                        onclick="return confirm('Close this ticket?')">
                    <i data-lucide="archive" style="width:14px;height:14px;flex-shrink:0;"></i> Close Ticket
                </button>
            </form>
            @endif
            <form method="POST" action="{{ route('admin.tickets.delete', $ticket->id) }}"
                  onsubmit="return confirm('Permanently delete this ticket?')">
                @csrf @method('DELETE')
                <button type="submit"
                        style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 12px;border-radius:8px;font-size:13px;color:var(--fg-3);background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;transition:.12s;"
                        onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                        onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                    <i data-lucide="trash-2" style="width:14px;height:14px;flex-shrink:0;"></i> Delete Ticket
                </button>
            </form>
        </div>

    </div>
</div>

<style>
@media (max-width: 900px) {
    .ticket-show-grid { grid-template-columns: 1fr !important; }
}
</style>

@endsection
