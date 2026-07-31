@extends('user.layouts.app')
@section('title', 'Ticket #' . $ticket->ticket_number)
@section('page-title', 'Support Ticket')

@section('content')

<div style="">

    {{-- Back --}}
    <a href="{{ route('user.helpdesk.index') }}"
       style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:var(--fg-3); text-decoration:none; margin-bottom:18px; transition:color .14s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">
        <i data-lucide="arrow-left" style="width:14px; height:14px;"></i> All tickets
    </a>

    {{-- Ticket header --}}
    @php
        $sColors = [0=>'#60A5FA', 1=>'#22C55E', 2=>'#F59E0B', 3=>'var(--fg-3)'];
        $sBgs    = [0=>'rgba(96,165,250,0.08)', 1=>'rgba(34,197,94,0.08)', 2=>'rgba(245,158,11,0.08)', 3=>'var(--surface-2)'];
        $sc  = $sColors[$ticket->status]  ?? 'var(--fg-3)';
        $sbg = $sBgs[$ticket->status]     ?? 'var(--surface-2)';
        $pColors = [1=>'#9CA3AF', 2=>'#F59E0B', 3=>'#EF4444'];
        $pc  = $pColors[$ticket->priority] ?? 'var(--fg-3)';
    @endphp

    <div class="jobstation-card" style="padding:20px; margin-bottom:12px;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px; flex-wrap:wrap;">
                    <span style="font-size:11px; color:var(--fg-4); font-family:ui-monospace,monospace;">#{{ $ticket->ticket_number }}</span>
                    <span style="font-size:11px; padding:3px 10px; border-radius:999px; font-weight:500; border:1px solid;
                                 background:{{ $sbg }}; color:{{ $sc }}; border-color:{{ $sc }}33;">
                        {{ $ticket->status_label }}
                    </span>
                    <span style="font-size:11px; padding:3px 10px; border-radius:999px; font-weight:500; border:1px solid;
                                 color:{{ $pc }}; border-color:{{ $pc }}33; background:{{ $pc }}11;">
                        {{ $ticket->priority_label }} priority
                    </span>
                </div>
                <div style="font-size:16px; font-weight:600; color:var(--fg); margin-bottom:4px;">{{ $ticket->subject }}</div>
                <div style="font-size:12px; color:var(--fg-4);">Opened {{ $ticket->created_at->diffForHumans() }}</div>
            </div>
            @if($ticket->status != 3)
            <form method="POST" action="{{ route('user.helpdesk.close', $ticket->ticket_number) }}">
                @csrf
                <button type="submit" onclick="return confirm('Close this ticket?')"
                        class="btn" style="font-size:12px; padding:7px 14px; color:var(--fg-3); gap:6px;">
                    <i data-lucide="x-circle" style="width:13px; height:13px;"></i> Close ticket
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Messages thread --}}
    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
        @foreach($ticket->messages as $msg)
        @php
            $isAdmin  = $msg->is_admin_reply;
            $userInit = strtoupper(substr(auth()->user()->firstname ?? auth()->user()->username, 0, 1));
            $uColors  = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
            $uColor   = $uColors[ord($userInit) % count($uColors)];
        @endphp
        <div class="jobstation-card" style="padding:18px; {{ $isAdmin ? 'border-left:3px solid var(--accent);' : '' }}">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px; font-weight:600; color:white;
                            background:{{ $isAdmin ? 'linear-gradient(135deg,#2f54eb,#60A5FA)' : 'linear-gradient(135deg,'.$uColor.','.($uColors[(ord($userInit)+2)%count($uColors)]).')' }};">
                    {{ $isAdmin ? 'S' : $userInit }}
                </div>
                <div style="flex:1;">
                    <div style="font-size:13px; font-weight:500; color:var(--fg); display:flex; align-items:center; gap:8px;">
                        {{ $isAdmin ? 'Support Team' : auth()->user()->fullname }}
                        @if($isAdmin)
                        <span style="font-size:10px; font-weight:700; color:var(--accent); background:var(--accent-soft); border:1px solid rgba(47,84,235,0.25); padding:1px 7px; border-radius:999px;">STAFF</span>
                        @endif
                    </div>
                </div>
                <span style="font-size:11.5px; color:var(--fg-4);">{{ $msg->created_at->diffForHumans() }}</span>
            </div>
            <div style="font-size:13.5px; color:var(--fg-2); line-height:1.65; white-space:pre-wrap;">{{ $msg->message }}</div>
            @if($msg->files && $msg->files->count())
            <div style="margin-top:12px; display:flex; flex-wrap:wrap; gap:8px;">
                @foreach($msg->files as $file)
                <a href="{{ route('user.helpdesk.download', $file->id) }}"
                   style="display:inline-flex; align-items:center; gap:6px; font-size:12px; padding:5px 12px; border-radius:999px;
                          background:var(--surface-2); border:1px solid var(--border-strong); color:var(--accent); text-decoration:none; transition:.14s;"
                   onmouseover="this.style.background='var(--accent-soft)'" onmouseout="this.style.background='var(--surface-2)'">
                    <i data-lucide="paperclip" style="width:12px; height:12px;"></i>
                    {{ $file->attachment }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Reply form / Closed state --}}
    @if($ticket->status != 3)
    <div class="jobstation-card" style="padding:20px;">
        <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
            <i data-lucide="message-square" style="width:14px; height:14px; color:var(--accent);"></i>
            Reply
        </div>
        <form method="POST" action="{{ route('user.helpdesk.reply', $ticket->ticket_number) }}" enctype="multipart/form-data">
            @csrf
            <div style="display:flex; flex-direction:column; gap:14px;">

                <div>
                    <textarea name="message" rows="5"
                              placeholder="Type your reply…"
                              style="resize:vertical; {{ $errors->has('message') ? 'border-color:var(--danger);' : '' }}"
                              required>{{ old('message') }}</textarea>
                    @error('message')
                    <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <div style="font-size:12px; color:var(--fg-3); margin-bottom:6px;">Attachments (optional)</div>
                    <div style="border:2px dashed var(--border-strong); border-radius:10px; padding:12px; background:var(--surface-2);">
                        <input type="file" name="attachments[]" multiple accept="image/*,application/pdf,.zip,.txt"
                               style="font-size:13px; color:var(--fg-3); width:100%; cursor:pointer; background:transparent; border:none; box-shadow:none; padding:0;">
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:10px 22px; gap:6px;">
                        <i data-lucide="send" style="width:13px; height:13px;"></i> Send reply
                    </button>
                </div>
            </div>
        </form>
    </div>
    @else
    <div class="jobstation-card" style="padding:40px 24px; text-align:center;">
        <div style="width:44px; height:44px; border-radius:12px; background:var(--surface-2); display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
            <i data-lucide="check-circle" style="width:20px; height:20px; color:var(--fg-3);"></i>
        </div>
        <div style="font-size:14px; font-weight:600; color:var(--fg); margin-bottom:4px;">Ticket closed</div>
        <div style="font-size:13px; color:var(--fg-3);">
            Need more help?
            <a href="{{ route('user.helpdesk.create') }}" style="color:var(--accent); text-decoration:none;">Open a new ticket</a>
        </div>
    </div>
    @endif

</div>

@endsection
