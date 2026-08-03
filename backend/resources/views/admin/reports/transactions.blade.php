@extends('admin.layouts.app')
@section('title', 'Transaction Report')
@section('page-title', 'Transaction Report')

@section('content')

{{-- Summary tiles --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
    <div class="jobstation-card" style="padding:18px;display:flex;align-items:center;gap:14px;">
        <div style="padding:10px;border-radius:12px;background:rgba(34,197,94,0.1);">
            <i data-lucide="trending-up" style="width:22px;height:22px;color:#22C55E;display:block;"></i>
        </div>
        <div>
            <div style="font-size:22px;font-weight:700;color:#22C55E;font-family:ui-monospace,monospace;letter-spacing:-0.5px;">{{ number_format($summary['total_credits'], 0) }} {{ coinSymbol() }}</div>
            <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">Total Credited {{ request('from') ? '(filtered)' : '' }}</div>
        </div>
    </div>
    <div class="jobstation-card" style="padding:18px;display:flex;align-items:center;gap:14px;">
        <div style="padding:10px;border-radius:12px;background:rgba(239,68,68,0.1);">
            <i data-lucide="trending-down" style="width:22px;height:22px;color:#EF4444;display:block;"></i>
        </div>
        <div>
            <div style="font-size:22px;font-weight:700;color:#EF4444;font-family:ui-monospace,monospace;letter-spacing:-0.5px;">{{ number_format($summary['total_debits'], 0) }} {{ coinSymbol() }}</div>
            <div style="font-size:12px;color:var(--fg-3);margin-top:2px;">Total Debited {{ request('from') ? '(filtered)' : '' }}</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="jobstation-card" style="padding:14px 18px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.reports.transactions') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div style="width:130px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Type</label>
            <select name="type">
                <option value="">All</option>
                <option value="+" @selected(request('type')=='+')>Credit (+)</option>
                <option value="-" @selected(request('type')=='-')>Debit (−)</option>
            </select>
        </div>
        <div style="width:176px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Category</label>
            <select name="category">
                <option value="">All</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(request('category')==$key)>{{ $label }}</option>
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
            <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px;">Apply</button>
            @if(request()->hasAny(['type','category','from','to']))
                <a href="{{ route('admin.reports.transactions') }}" class="btn" style="padding:8px 14px;font-size:13px;">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="jobstation-card" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">User</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Description</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Category</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Amount</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Balance</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:11px 20px;">
                    <a href="{{ route('admin.users.show', $entry->user_id) }}"
                       style="font-size:13px;color:var(--fg-2);text-decoration:none;"
                       onmouseover="this.style.color='var(--accent)'"
                       onmouseout="this.style.color='var(--fg-2)'">
                        {{ $entry->user?->username ?? '—' }}
                    </a>
                    <div style="font-size:11.5px;color:var(--fg-4);font-family:ui-monospace,monospace;margin-top:1px;">{{ $entry->reference }}</div>
                </td>
                <td style="padding:11px 20px;color:var(--fg-3);font-size:12.5px;max-width:220px;">
                    <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $entry->description }}</div>
                </td>
                <td style="padding:11px 20px;">
                    <span class="badge-default" style="font-size:11px;">{{ $entry->category_label }}</span>
                </td>
                <td style="padding:11px 20px;text-align:right;font-weight:600;font-family:ui-monospace,monospace;color:{{ $entry->entry_type === '+' ? '#22C55E' : '#EF4444' }};">
                    {{ $entry->entry_type }}{{ $entry->formatted_amount }}
                </td>
                <td style="padding:11px 20px;text-align:right;font-size:12px;color:var(--fg-3);font-family:ui-monospace,monospace;">
                    {{ number_format($entry->balance_after, 0) }}
                </td>
                <td style="padding:11px 20px;font-size:12px;color:var(--fg-3);">{{ $entry->created_at->format('M d, Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding:56px;text-align:center;color:var(--fg-3);">No transactions match the filter.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($entries->hasPages())
    <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--fg-3);">
        <div>Showing {{ $entries->firstItem() }}–{{ $entries->lastItem() }} of {{ $entries->total() }}</div>
        <div style="display:flex;gap:4px;">
            @if(!$entries->onFirstPage())
                <a href="{{ $entries->previousPageUrl() }}" style="padding:4px 10px;border-radius:6px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;">Prev</a>
            @endif
            @if($entries->hasMorePages())
                <a href="{{ $entries->nextPageUrl() }}" style="padding:4px 10px;border-radius:6px;background:var(--surface-2);color:var(--fg-2);text-decoration:none;">Next</a>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
