@extends('admin.layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div style="font-size:13px;color:var(--fg-3);">
        {{ $notifications->total() }} notification{{ $notifications->total() !== 1 ? 's' : '' }}
        @php $unread = $notifications->where('is_read', false)->count(); @endphp
        @if($unread > 0)
        <span style="margin-left:8px;padding:2px 8px;border-radius:99px;background:rgba(47,84,235,0.12);color:var(--accent);font-size:11px;font-weight:600;">{{ $unread }} unread</span>
        @endif
    </div>
    @if($notifications->total())
    <form method="POST" action="{{ route('admin.notifications.read-all') }}">
        @csrf
        <button type="submit" class="btn" style="padding:7px 14px;font-size:13px;display:flex;align-items:center;gap:6px;">
            <i data-lucide="check-check" style="width:14px;height:14px;"></i> Mark all as read
        </button>
    </form>
    @endif
</div>

<div class="jobstation-card" style="overflow:hidden;">
    @forelse($notifications as $n)
    @php
        $iconMap = [
            'success' => ['check-circle', '#22C55E', 'rgba(34,197,94,0.1)'],
            'warning' => ['alert-triangle', '#F59E0B', 'rgba(245,158,11,0.1)'],
            'danger'  => ['alert-circle',  '#EF4444', 'rgba(239,68,68,0.1)'],
            'info'    => ['info',           '#60A5FA', 'rgba(96,165,250,0.1)'],
        ];
        [$icon, $color, $bg] = $iconMap[$n->type] ?? ['bell', 'var(--fg-3)', 'var(--surface-2)'];
    @endphp
    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);{{ !$n->is_read ? 'background:rgba(47,84,235,0.03);border-left:3px solid var(--accent);' : '' }}">
        <div style="width:36px;height:36px;border-radius:10px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
            <i data-lucide="{{ $icon }}" style="width:16px;height:16px;color:{{ $color }};"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="font-weight:600;font-size:13.5px;color:var(--fg);">{{ $n->title }}</span>
                @if(!$n->is_read)
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--accent);display:inline-block;flex-shrink:0;"></span>
                @endif
            </div>
            <p style="font-size:13px;color:var(--fg-3);margin-top:2px;line-height:1.5;">{{ $n->message }}</p>
            <div style="display:flex;align-items:center;gap:12px;margin-top:6px;">
                <span style="font-size:11.5px;color:var(--fg-4);">{{ $n->created_at->diffForHumans() }}</span>
                @if($n->url)
                    <a href="{{ $n->url }}" style="font-size:11.5px;color:var(--accent);text-decoration:none;">View →</a>
                @endif
                @if(!$n->is_read)
                    <form method="POST" action="{{ route('admin.notifications.read', $n->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" style="font-size:11.5px;color:var(--fg-4);background:none;border:none;cursor:pointer;font-family:inherit;padding:0;"
                                onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">
                            Mark read
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div style="padding:64px 24px;text-align:center;color:var(--fg-3);">
        <i data-lucide="bell-off" style="width:36px;height:36px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
        <div style="font-size:14px;">No notifications yet.</div>
    </div>
    @endforelse
</div>

@if($notifications->hasPages())
<div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--fg-3);">
    <div>Showing {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} of {{ $notifications->total() }}</div>
    <div style="display:flex;gap:4px;">
        @if(!$notifications->onFirstPage())
            <a href="{{ $notifications->previousPageUrl() }}" style="padding:5px 12px;border-radius:7px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;font-size:12px;">Prev</a>
        @endif
        @if($notifications->hasMorePages())
            <a href="{{ $notifications->nextPageUrl() }}" style="padding:5px 12px;border-radius:7px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;font-size:12px;">Next</a>
        @endif
    </div>
</div>
@endif

@endsection
