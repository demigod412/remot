@extends('admin.layouts.app')
@section('title', 'Support Tickets')
@section('page-title', 'Support Tickets')

@section('content')

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;" class="tickets-stat-grid">
    @foreach([
        ['open',     'Open',            'inbox',           '#60A5FA', 'rgba(96,165,250,0.1)'],
        ['answered', 'Answered',        'message-circle',  '#22C55E', 'rgba(34,197,94,0.1)'],
        ['replied',  'Customer Reply',  'reply',           '#F59E0B', 'rgba(245,158,11,0.1)'],
        ['closed',   'Closed',          'archive',         'var(--fg-4)', 'var(--surface-2)'],
    ] as [$key, $label, $icon, $color, $bg])
    <div class="jobstation-card" style="padding:20px;">
        <div style="width:36px;height:36px;border-radius:10px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <i data-lucide="{{ $icon }}" style="width:18px;height:18px;color:{{ $color }};"></i>
        </div>
        <div style="font-size:26px;font-weight:700;font-family:ui-monospace,monospace;color:var(--fg);letter-spacing:-0.5px;">{{ $stats[$key] }}</div>
        <div style="font-size:12px;color:var(--fg-3);margin-top:4px;">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- Filter --}}
<div class="jobstation-card" style="padding:20px;margin-bottom:20px;">
    <form method="GET" action="{{ route('admin.tickets.index') }}"
          style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Search</label>
            <div style="position:relative;">
                <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--fg-4);pointer-events:none;"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Ticket #, subject, email…"
                       style="padding-left:32px;width:100%;font-size:13px;">
            </div>
        </div>
        <div style="width:150px;">
            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Status</label>
            <select name="status" style="width:100%;font-size:13px;font-family:inherit;">
                <option value="">All</option>
                <option value="0" @selected(request('status')==='0')>Open</option>
                <option value="1" @selected(request('status')==='1')>Answered</option>
                <option value="2" @selected(request('status')==='2')>Customer Reply</option>
                <option value="3" @selected(request('status')==='3')>Closed</option>
            </select>
        </div>
        <div style="width:130px;">
            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Priority</label>
            <select name="priority" style="width:100%;font-size:13px;font-family:inherit;">
                <option value="">All</option>
                <option value="1" @selected(request('priority')==='1')>Low</option>
                <option value="2" @selected(request('priority')==='2')>Medium</option>
                <option value="3" @selected(request('priority')==='3')>High</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['search','status','priority']))
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="jobstation-card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);">
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Ticket</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Subject</th>
                    <th style="text-align:center;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Priority</th>
                    <th style="text-align:center;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Status</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Last Reply</th>
                    <th style="text-align:right;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                @php
                    $statusStyles = [
                        0 => ['bg'=>'rgba(96,165,250,0.12)', 'color'=>'#60A5FA',  'label'=>'Open'],
                        1 => ['bg'=>'rgba(34,197,94,0.12)',  'color'=>'#22C55E',  'label'=>'Answered'],
                        2 => ['bg'=>'rgba(245,158,11,0.12)', 'color'=>'#F59E0B',  'label'=>'Customer Reply'],
                        3 => ['bg'=>'var(--surface-2)',      'color'=>'var(--fg-3)','label'=>'Closed'],
                    ][$ticket->status] ?? ['bg'=>'var(--surface-2)','color'=>'var(--fg-3)','label'=>'Unknown'];
                    $prioStyles = [
                        1 => ['bg'=>'var(--surface-2)',      'color'=>'var(--fg-3)', 'label'=>'Low'],
                        2 => ['bg'=>'rgba(245,158,11,0.12)', 'color'=>'#F59E0B',     'label'=>'Medium'],
                        3 => ['bg'=>'rgba(239,68,68,0.12)',  'color'=>'#EF4444',     'label'=>'High'],
                    ][$ticket->priority] ?? ['bg'=>'var(--surface-2)','color'=>'var(--fg-3)','label'=>'—'];
                @endphp
                <tr style="border-bottom:1px solid var(--border);{{ $ticket->status === 2 ? 'border-left:2px solid #F59E0B;' : '' }}">
                    <td style="padding:14px 20px;">
                        <div style="font-family:ui-monospace,monospace;font-size:12px;font-weight:600;color:var(--accent);">#{{ $ticket->ticket_number }}</div>
                        <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">{{ $ticket->name ?? $ticket->user?->username ?? '—' }}</div>
                        <div style="font-size:11px;color:var(--fg-4);">{{ $ticket->email }}</div>
                    </td>
                    <td style="padding:14px 20px;max-width:240px;">
                        <div style="color:var(--fg-2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ticket->subject }}</div>
                    </td>
                    <td style="padding:14px 20px;text-align:center;">
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:500;background:{{ $prioStyles['bg'] }};color:{{ $prioStyles['color'] }};">
                            {{ $prioStyles['label'] }}
                        </span>
                    </td>
                    <td style="padding:14px 20px;text-align:center;">
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:500;background:{{ $statusStyles['bg'] }};color:{{ $statusStyles['color'] }};">
                            {{ $statusStyles['label'] }}
                        </span>
                    </td>
                    <td style="padding:14px 20px;font-size:12px;color:var(--fg-3);">
                        {{ ($ticket->last_reply_at ?? $ticket->created_at)->diffForHumans() }}
                    </td>
                    <td style="padding:14px 20px;text-align:right;">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                            <a href="{{ route('admin.tickets.show', $ticket->id) }}"
                               style="padding:6px;border-radius:6px;color:var(--fg-3);display:flex;align-items:center;transition:.12s;"
                               onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                               onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'"
                               title="View">
                                <i data-lucide="eye" style="width:15px;height:15px;"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.tickets.delete', $ticket->id) }}"
                                  onsubmit="return confirm('Delete ticket #{{ $ticket->ticket_number }}?')"
                                  style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        style="padding:6px;border-radius:6px;color:var(--fg-4);background:transparent;border:none;cursor:pointer;display:flex;align-items:center;transition:.12s;"
                                        onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                                        onmouseout="this.style.background='transparent';this.style.color='var(--fg-4)'"
                                        title="Delete">
                                    <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding:48px;text-align:center;color:var(--fg-3);">
                        <i data-lucide="inbox" style="width:32px;height:32px;margin:0 auto 10px;display:block;opacity:0.3;"></i>
                        <div style="font-size:13px;">No tickets found.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tickets->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--fg-3);">
        <div>Showing {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} of {{ $tickets->total() }}</div>
        <div style="display:flex;gap:6px;">
            @if(!$tickets->onFirstPage())
            <a href="{{ $tickets->previousPageUrl() }}"
               class="btn btn-sm">Prev</a>
            @endif
            @if($tickets->hasMorePages())
            <a href="{{ $tickets->nextPageUrl() }}"
               class="btn btn-sm">Next</a>
            @endif
        </div>
    </div>
    @endif
</div>

<style>
@media (max-width: 900px) {
    .tickets-stat-grid { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width: 500px) {
    .tickets-stat-grid { grid-template-columns: 1fr 1fr !important; }
}
</style>

@endsection
