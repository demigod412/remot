@extends('admin.layouts.app')
@section('title', 'Ledger')
@section('page-title', 'Coin Ledger')

@section('content')

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px;">
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total Entries</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;" data-countup="{{ $stats['total_entries'] }}">0</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">all time</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total Credited</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#22C55E;">{{ number_format($stats['total_credits'], 0) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">{{ coinSymbol() }}</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total Debited</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#EF4444;">{{ number_format($stats['total_debits'], 0) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">{{ coinSymbol() }}</div>
    </div>
</div>

{{-- Filter --}}
<div class="jobstation-card" style="padding:14px 18px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.ledger.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Search</label>
            <div style="position:relative;">
                <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, description, username…" style="padding-left:32px;">
            </div>
        </div>
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
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px;">Filter</button>
            @if(request()->hasAny(['search','type','category']))
                <a href="{{ route('admin.ledger.index') }}" class="btn" style="padding:8px 14px;font-size:13px;">Clear</a>
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
                    {{ $entry->entry_type }}{{ number_format($entry->coins, 0) }}
                </td>
                <td style="padding:11px 20px;text-align:right;font-size:12px;color:var(--fg-3);font-family:ui-monospace,monospace;">
                    {{ number_format($entry->balance_after, 0) }}
                </td>
                <td style="padding:11px 20px;font-size:12px;color:var(--fg-3);">{{ $entry->created_at->format('M d, Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding:56px;text-align:center;color:var(--fg-3);">
                    <i data-lucide="book-open" style="width:32px;height:32px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
                    No ledger entries found.
                </td>
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
