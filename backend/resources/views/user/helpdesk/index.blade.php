@extends('user.layouts.app')
@section('title', 'Support')
@section('page-title', 'Support')

@section('content')

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap;">
    <div>
        <h2 style="font-size:18px; font-weight:600; letter-spacing:-0.3px; margin:0 0 2px;">Support tickets</h2>
        <p style="font-size:12.5px; color:var(--fg-3); margin:0;">Get help from our team. We usually respond within 24 hours.</p>
    </div>
    <a href="{{ route('user.helpdesk.create') }}" class="btn btn-primary" style="font-size:12.5px; padding:9px 18px; gap:6px; text-decoration:none;">
        <i data-lucide="plus" style="width:13px; height:13px;"></i> New ticket
    </a>
</div>

{{-- Status tabs --}}
<div style="display:flex; gap:2px; border-bottom:1px solid var(--border); margin-bottom:16px;">
    @foreach(['' => 'All', '0' => 'Open', '1' => 'Answered', '2' => 'My reply', '3' => 'Closed'] as $val => $label)
    @php $active = request('status', '') === (string)$val; @endphp
    <a href="{{ route('user.helpdesk.index', $val !== '' ? ['status' => $val] : []) }}"
       style="padding:9px 16px; text-decoration:none; font-size:13px; white-space:nowrap; display:inline-block; margin-bottom:-1px;
              color:{{ $active ? 'var(--fg)' : 'var(--fg-3)' }};
              font-weight:{{ $active ? '600' : '500' }};
              border-bottom:{{ $active ? '2px solid var(--accent)' : '2px solid transparent' }};">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- Ticket list --}}
<div style="display:flex; flex-direction:column; gap:8px;">
@forelse($tickets as $ticket)
@php
    $pColors = [1=>['#9CA3AF','rgba(156,163,175,0.1)'], 2=>['#F59E0B','rgba(245,158,11,0.1)'], 3=>['#EF4444','rgba(239,68,68,0.1)']];
    $sColors  = [0=>['#60A5FA','rgba(96,165,250,0.08)'], 1=>['#22C55E','rgba(34,197,94,0.08)'], 2=>['#F59E0B','rgba(245,158,11,0.08)'], 3=>['var(--fg-3)','var(--surface-2)']];
    $sIcons   = [0=>'help-circle', 1=>'message-circle', 2=>'message-circle', 3=>'check-circle'];
    [$pc, $pbg] = $pColors[$ticket->priority] ?? ['var(--fg-3)', 'transparent'];
    [$sc, $sbg] = $sColors[$ticket->status]   ?? ['var(--fg-3)', 'var(--surface-2)'];
@endphp
<a href="{{ route('user.helpdesk.show', $ticket->ticket_number) }}"
   class="jobstation-card"
   style="padding:16px 20px; display:block; text-decoration:none; transition:transform .12s, border-color .12s;
          {{ $ticket->status == 2 ? 'border-left:3px solid #F59E0B;' : '' }}"
   onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
    <div style="display:flex; align-items:flex-start; gap:14px;">
        <div style="width:36px; height:36px; border-radius:10px; background:{{ $sbg }}; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px;">
            <i data-lucide="{{ $sIcons[$ticket->status] ?? 'help-circle' }}" style="width:16px; height:16px; color:{{ $sc }};"></i>
        </div>
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px; flex-wrap:wrap;">
                <span style="font-size:10.5px; color:var(--fg-4); font-family:ui-monospace,monospace;">#{{ $ticket->ticket_number }}</span>
                <span style="font-size:10.5px; padding:2px 8px; border-radius:999px; background:{{ $pbg }}; color:{{ $pc }}; border:1px solid {{ $pc }}22; font-weight:500;">
                    {{ $ticket->priority_label }}
                </span>
                @if($ticket->status == 2)
                <span style="font-size:10.5px; padding:2px 9px; border-radius:999px; background:rgba(245,158,11,0.12); color:#F59E0B; border:1px solid rgba(245,158,11,0.25); font-weight:600;">
                    Awaiting your reply
                </span>
                @endif
            </div>
            <div style="font-size:14px; font-weight:500; color:var(--fg); margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $ticket->subject }}</div>
            <div style="font-size:11.5px; color:var(--fg-4);">Opened {{ $ticket->created_at->diffForHumans() }}</div>
        </div>
        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px; flex-shrink:0;">
            <span style="font-size:11px; padding:3px 10px; border-radius:999px; background:{{ $sbg }}; color:{{ $sc }}; border:1px solid {{ $sc }}22; font-weight:500;">
                {{ $ticket->status_label }}
            </span>
            <i data-lucide="chevron-right" style="width:14px; height:14px; color:var(--fg-4);"></i>
        </div>
    </div>
</a>
@empty
<div class="jobstation-card" style="padding:64px 24px; text-align:center;">
    <div style="width:56px; height:56px; border-radius:16px; background:var(--surface-2); display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
        <i data-lucide="headphones" style="width:24px; height:24px; color:var(--fg-3);"></i>
    </div>
    <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:6px;">No tickets yet</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px; line-height:1.55;">Need help? Open a ticket and our support team will get back to you shortly.</p>
    <a href="{{ route('user.helpdesk.create') }}" class="btn btn-primary" style="font-size:13px; padding:10px 22px; text-decoration:none;">Open a ticket</a>
</div>
@endforelse
</div>

<div style="margin-top:16px;">{{ $tickets->withQueryString()->links() }}</div>

@endsection
