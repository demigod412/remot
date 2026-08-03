@extends('user.layouts.app')
@section('title', __('Transaction History'))
@section('page-title', __('Transaction History'))

@section('content')

{{-- Filters --}}
<form method="GET" action="{{ route('user.wallet.ledger') }}"
      style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px; margin-bottom:18px;">
    <div>
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">{{ __('Type') }}</label>
        <select name="type" onchange="this.form.submit()"
                style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:var(--fg); font-size:13px; outline:none; min-width:130px;">
            <option value="">{{ __('All Types') }}</option>
            <option value="+" {{ request('type') == '+' ? 'selected' : '' }}>{{ __('Credits (+)') }}</option>
            <option value="-" {{ request('type') == '-' ? 'selected' : '' }}>{{ __('Debits (−)') }}</option>
        </select>
    </div>
    <div>
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">{{ __('Category') }}</label>
        <select name="category" onchange="this.form.submit()"
                style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:var(--fg); font-size:13px; outline:none; min-width:155px;">
            <option value="">{{ __('All Categories') }}</option>
            @foreach(config('jobstation.ledger_categories') as $key => $label)
            <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @if(request('type') || request('category'))
    <a href="{{ route('user.wallet.ledger') }}"
       style="font-size:12.5px; color:var(--fg-3); text-decoration:none; padding:8px 0; align-self:flex-end; transition:color .15s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">{{ __('Reset') }}</a>
    @endif
</form>

{{-- Table --}}
<div class="card" style="overflow:hidden; padding:0;">
    <table style="width:100%; border-collapse:collapse; font-size:13.5px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:12px 18px; text-align:left; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap;">{{ __('Type') }}</th>
                <th style="padding:12px 18px; text-align:left; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Category') }}</th>
                <th class="hide-sm" style="padding:12px 18px; text-align:left; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Note') }}</th>
                <th class="hide-md" style="padding:12px 18px; text-align:left; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap;">{{ __('Date') }}</th>
                <th style="padding:12px 18px; text-align:right; font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em;">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
            <tr style="border-bottom:1px solid var(--border); transition:background .1s;" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
                <td style="padding:13px 18px;">
                    <div style="width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center;
                                background:{{ $entry->entry_type === '+' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)' }};">
                        @if($entry->entry_type === '+')
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7 7 7-7"/></svg>
                        @else
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12l7-7 7 7"/></svg>
                        @endif
                    </div>
                </td>
                <td style="padding:13px 18px;">
                    <div style="color:var(--fg); font-weight:500;">{{ config('jobstation.ledger_categories')[$entry->category] ?? ucfirst($entry->category) }}</div>
                    @if($entry->reference)
                    <div class="mono" style="font-size:11px; color:var(--fg-4);">{{ $entry->reference }}</div>
                    @endif
                </td>
                <td class="hide-sm" style="padding:13px 18px; color:var(--fg-3);">{{ $entry->description ?? '—' }}</td>
                <td class="hide-md" style="padding:13px 18px; color:var(--fg-3); white-space:nowrap;">{{ $entry->created_at->format('M d, Y H:i') }}</td>
                <td style="padding:13px 18px; text-align:right; font-weight:600; white-space:nowrap;
                           color:{{ $entry->entry_type === '+' ? '#22C55E' : '#EF4444' }};">
                    {{ $entry->entry_type }}{{ $entry->formatted_amount }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding:56px 18px; text-align:center; color:var(--fg-3);">
                    <div style="font-size:28px; margin-bottom:10px;">🧾</div>
                    <div style="font-size:14px; font-weight:500; margin-bottom:4px; color:var(--fg);">{{ __('No transactions found') }}</div>
                    <div style="font-size:13px;">{{ __('Your transaction history will appear here.') }}</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:20px;">{{ $entries->withQueryString()->links() }}</div>

<style>
@media (max-width: 768px) { .hide-sm { display: none !important; } }
@media (max-width: 1024px) { .hide-md { display: none !important; } }
</style>
@endsection
