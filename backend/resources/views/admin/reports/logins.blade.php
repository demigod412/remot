@extends('admin.layouts.app')
@section('title', 'Login Report')
@section('page-title', 'Login Report')

@section('content')

{{-- Filters --}}
<div class="jobstation-card" style="padding:14px 18px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.reports.logins') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Search User</label>
            <div style="position:relative;">
                <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Username or email…" style="padding-left:32px;">
            </div>
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
            <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px;">Apply</button>
            @if(request()->hasAny(['search','from','to']))
                <a href="{{ route('admin.reports.logins') }}" class="btn" style="padding:8px 14px;font-size:13px;">Clear</a>
            @endif
        </div>
    </form>
</div>

<div class="jobstation-card" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">User</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">IP Address</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Location</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Browser / OS</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $session)
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:12px 20px;">
                    <a href="{{ route('admin.users.show', $session->user_id) }}"
                       style="font-size:13px;color:var(--fg-2);text-decoration:none;"
                       onmouseover="this.style.color='var(--accent)'"
                       onmouseout="this.style.color='var(--fg-2)'">
                        {{ $session->user?->username ?? '—' }}
                    </a>
                    <div style="font-size:11.5px;color:var(--fg-4);margin-top:1px;">{{ $session->user?->email }}</div>
                </td>
                <td style="padding:12px 20px;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-3);">{{ $session->user_ip ?? '—' }}</td>
                <td style="padding:12px 20px;font-size:12.5px;color:var(--fg-2);">
                    {{ implode(', ', array_filter([$session->city, $session->country])) ?: '—' }}
                </td>
                <td style="padding:12px 20px;font-size:12px;color:var(--fg-3);">
                    {{ $session->browser ?? '—' }} / {{ $session->os ?? '—' }}
                </td>
                <td style="padding:12px 20px;font-size:12px;color:var(--fg-3);">{{ $session->created_at->format('M d, Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding:56px;text-align:center;color:var(--fg-3);">No login records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($sessions->hasPages())
    <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--fg-3);">
        <div>Showing {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }}</div>
        <div style="display:flex;gap:4px;">
            @if(!$sessions->onFirstPage())
                <a href="{{ $sessions->previousPageUrl() }}" style="padding:4px 10px;border-radius:6px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;">Prev</a>
            @endif
            @if($sessions->hasMorePages())
                <a href="{{ $sessions->nextPageUrl() }}" style="padding:4px 10px;border-radius:6px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;">Next</a>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
