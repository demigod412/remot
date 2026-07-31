@extends('admin.layouts.app')
@section('title', 'Notification Logs')
@section('page-title', 'Notification Logs')

@section('content')

{{-- Filters --}}
<div class="jobstation-card" style="padding:14px 18px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.reports.notifications') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Search</label>
            <div style="position:relative;">
                <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Email, subject, username…" style="padding-left:32px;">
            </div>
        </div>
        <div style="width:176px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Type</label>
            <select name="type">
                <option value="">All Types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">From</label>
            <input type="date" name="from" value="{{ request('from') }}" style="width:148px;">
        </div>
        <div>
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">To</label>
            <input type="date" name="to" value="{{ request('to') }}" style="width:148px;">
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px;">Filter</button>
            @if(request()->hasAny(['search','type','from','to']))
                <a href="{{ route('admin.reports.notifications') }}" class="btn" style="padding:8px 14px;font-size:13px;">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="jobstation-card" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Recipient</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Subject</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Type</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Via</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Sent</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:12px 20px;">
                    <div style="font-size:13px;font-weight:500;color:var(--fg-2);">{{ $log->sent_to }}</div>
                    @if($log->user)
                        <div style="font-size:11.5px;color:var(--fg-4);margin-top:2px;">{{ '@' . $log->user->username }}</div>
                    @endif
                </td>
                <td style="padding:12px 20px;">
                    <div style="color:var(--fg-2);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->subject }}</div>
                </td>
                <td style="padding:12px 20px;text-align:center;">
                    <span class="badge-info" style="font-size:11px;">{{ $log->notification_type }}</span>
                </td>
                <td style="padding:12px 20px;text-align:center;">
                    @php $via = $log->sender ?? 'email'; @endphp
                    <span class="{{ $via === 'sms' ? 'badge-warning' : 'badge-default' }}" style="font-size:11px;">{{ $via }}</span>
                </td>
                <td style="padding:12px 20px;font-size:12px;color:var(--fg-3);">{{ $log->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding:56px;text-align:center;color:var(--fg-3);">
                    <i data-lucide="bell-off" style="width:32px;height:32px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
                    No notification logs found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($logs->hasPages())
    <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--fg-3);">
        <div>Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</div>
        <div style="display:flex;gap:4px;">
            @if(!$logs->onFirstPage())
                <a href="{{ $logs->previousPageUrl() }}" style="padding:4px 10px;border-radius:6px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;">Prev</a>
            @endif
            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" style="padding:4px 10px;border-radius:6px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;">Next</a>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
