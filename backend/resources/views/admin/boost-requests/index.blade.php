@extends('admin.layouts.app')
@section('title', 'Boost Requests')
@section('page-title', 'Boost Requests')

@section('content')

{{-- Pending --}}
<div class="jobstation-card" style="overflow:hidden;margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);">
        <div style="font-weight:600;font-size:14px;color:var(--fg);display:flex;align-items:center;gap:8px;">
            Pending Requests
            <span style="padding:2px 8px;border-radius:99px;background:rgba(245,158,11,0.1);color:#F59E0B;font-size:11px;font-weight:700;">{{ $pending->count() }}</span>
        </div>
    </div>

    @if($pending->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--fg-3);">
        <i data-lucide="check-circle" style="width:28px;height:28px;display:block;margin:0 auto 10px;opacity:0.3;color:#22C55E;"></i>
        <div style="font-size:13.5px;">No pending boost requests.</div>
    </div>
    @else
    @foreach($pending as $req)
    @php
        $item     = $req->boostable;
        $itemType = class_basename($req->boostable_type);
        $itemName = $item?->title ?? 'Deleted item';
        $itemUrl  = $item ? ($itemType === 'Work'
            ? route('admin.works.show', $item->id)
            : route('admin.jobs.listings.show', $item->id)) : null;
        $isWork = $itemType === 'Work';
    @endphp
    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);">
        <div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:{{ $isWork ? 'rgba(47,84,235,0.1)' : 'rgba(96,165,250,0.1)' }};">
            <i data-lucide="{{ $isWork ? 'briefcase' : 'building-2' }}" style="width:16px;height:16px;color:{{ $isWork ? 'var(--accent)' : '#60A5FA' }};"></i>
        </div>

        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                @if($itemUrl)
                <a href="{{ $itemUrl }}"
                   style="font-weight:500;font-size:13.5px;color:var(--fg);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                   onmouseover="this.style.color='var(--accent)'"
                   onmouseout="this.style.color='var(--fg)'">{{ $itemName }}</a>
                @else
                <span style="font-size:13.5px;color:var(--fg-3);font-style:italic;">{{ $itemName }}</span>
                @endif
                <span style="font-size:11px;padding:2px 8px;border-radius:99px;background:{{ $isWork ? 'rgba(47,84,235,0.1)' : 'rgba(96,165,250,0.1)' }};color:{{ $isWork ? 'var(--accent)' : '#60A5FA' }};">
                    {{ $isWork ? 'Work' : 'Job Listing' }}
                </span>
            </div>
            <div style="font-size:12.5px;color:var(--fg-3);">
                By <span style="color:var(--fg-2);">{{ $req->user->fullname ?? $req->user->username }}</span>
                · {{ $req->days }} day(s)
                · <span style="color:#F5D547;font-weight:600;font-family:ui-monospace,monospace;">{{ formatCoins($req->coins_paid) }} coins held</span>
                · {{ $req->created_at->diffForHumans() }}
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
            <form method="POST" action="{{ route('admin.boost-requests.approve', $req->id) }}">
                @csrf
                <button type="submit"
                        style="padding:7px 14px;border-radius:8px;background:rgba(34,197,94,0.1);color:#22C55E;border:none;cursor:pointer;font-size:12.5px;font-weight:500;font-family:inherit;display:flex;align-items:center;gap:5px;transition:background .14s;"
                        onclick="return confirm('Approve boost for {{ $req->days }} day(s)?')"
                        onmouseover="this.style.background='rgba(34,197,94,0.2)'"
                        onmouseout="this.style.background='rgba(34,197,94,0.1)'">
                    <i data-lucide="check" style="width:13px;height:13px;"></i> Approve
                </button>
            </form>

            <div x-data="{ open: false }" style="position:relative;">
                <button @click="open = !open"
                        style="padding:7px 14px;border-radius:8px;background:rgba(239,68,68,0.08);color:#EF4444;border:none;cursor:pointer;font-size:12.5px;font-weight:500;font-family:inherit;display:flex;align-items:center;gap:5px;transition:background .14s;"
                        onmouseover="this.style.background='rgba(239,68,68,0.16)'"
                        onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                    <i data-lucide="x" style="width:13px;height:13px;"></i> Reject
                </button>
                <div x-show="open" x-cloak x-transition
                     style="position:absolute;right:0;top:calc(100%+8px);width:260px;padding:14px;border-radius:12px;background:var(--surface);border:1px solid var(--border-strong);box-shadow:0 16px 48px rgba(0,0,0,0.15);z-index:20;"
                     @click.outside="open = false">
                    <form method="POST" action="{{ route('admin.boost-requests.reject', $req->id) }}" style="display:flex;flex-direction:column;gap:8px;">
                        @csrf
                        <textarea name="rejection_reason" rows="2" placeholder="Reason (optional)" style="font-size:12.5px;resize:none;"></textarea>
                        <button type="submit"
                                style="width:100%;padding:8px;font-size:13px;border-radius:8px;background:#EF4444;color:#fff;border:none;cursor:pointer;font-family:inherit;font-weight:500;transition:background .14s;"
                                onmouseover="this.style.background='#DC2626'"
                                onmouseout="this.style.background='#EF4444'">
                            Confirm Reject & Refund
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif
</div>

{{-- Recent Processed --}}
@if($recent->isNotEmpty())
<div class="jobstation-card" style="overflow:hidden;">
    <div style="padding:12px 20px;border-bottom:1px solid var(--border);">
        <div style="font-weight:600;font-size:13.5px;color:var(--fg);">Recently Processed</div>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:9px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Item</th>
                <th style="padding:9px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">User</th>
                <th style="padding:9px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Days</th>
                <th style="padding:9px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Coins</th>
                <th style="padding:9px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Status</th>
                <th style="padding:9px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recent as $req)
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:11px 20px;">
                    <span style="color:var(--fg-2);font-size:12.5px;">{{ $req->boostable?->title ?? 'Deleted' }}</span>
                    <span style="color:var(--fg-4);font-size:11.5px;margin-left:4px;">({{ class_basename($req->boostable_type) }})</span>
                </td>
                <td style="padding:11px 20px;color:var(--fg-3);font-size:12.5px;">{{ $req->user->username ?? '-' }}</td>
                <td style="padding:11px 20px;color:var(--fg-3);font-size:12.5px;">{{ $req->days }}d</td>
                <td style="padding:11px 20px;color:#F5D547;font-family:ui-monospace,monospace;font-size:12.5px;">{{ formatCoins($req->coins_paid) }}</td>
                <td style="padding:11px 20px;">
                    <span class="{{ $req->status_color }}" style="font-size:11px;">{{ $req->status_label }}</span>
                    @if($req->rejection_reason)
                    <div style="font-size:11.5px;color:var(--fg-4);margin-top:2px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $req->rejection_reason }}</div>
                    @endif
                </td>
                <td style="padding:11px 20px;color:var(--fg-3);font-size:12px;">{{ $req->updated_at->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
