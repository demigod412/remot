@extends('admin.layouts.app')
@section('title', 'Payout Methods')
@section('page-title', 'Payout Methods')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <span style="font-size:13px;color:var(--fg-3);">{{ $methods->count() }} payout method{{ $methods->count() != 1 ? 's' : '' }}</span>
    <a href="{{ route('admin.payout-methods.create') }}" class="btn btn-primary" style="padding:8px 18px;font-size:13px;">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Method
    </a>
</div>

<div class="jobstation-card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);">
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Name</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Currency</th>
                    <th style="text-align:right;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Min / Max</th>
                    <th style="text-align:right;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Rate</th>
                    <th style="text-align:right;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Fee</th>
                    <th style="text-align:center;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Cashouts</th>
                    <th style="text-align:center;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Status</th>
                    <th style="text-align:right;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($methods as $method)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:14px 20px;">
                        <div style="font-size:13px;font-weight:500;color:var(--fg);">{{ $method->name }}</div>
                        @if($method->description)
                        <div style="font-size:11.5px;color:var(--fg-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;">{{ $method->description }}</div>
                        @endif
                    </td>
                    <td style="padding:14px 20px;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-2);">{{ $method->currency }}</td>
                    <td style="padding:14px 20px;text-align:right;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-3);">
                        {{ number_format($method->min_coins) }} – {{ number_format($method->max_coins) }}
                    </td>
                    <td style="padding:14px 20px;text-align:right;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-2);">
                        1 {{ coinSymbol() }} = {{ $method->coin_to_currency_rate }} {{ $method->currency }}
                    </td>
                    <td style="padding:14px 20px;text-align:right;font-size:12px;color:var(--fg-3);">
                        {{ $method->percent_fee }}% + {{ number_format($method->fixed_fee) }} {{ coinSymbol() }}
                    </td>
                    <td style="padding:14px 20px;text-align:center;font-size:13px;color:var(--fg-2);">{{ $method->cashouts_count }}</td>
                    <td style="padding:14px 20px;text-align:center;">
                        @if($method->status)
                        <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:rgba(34,197,94,0.12);color:#22C55E;">Active</span>
                        @else
                        <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:var(--surface-2);color:var(--fg-3);">Inactive</span>
                        @endif
                    </td>
                    <td style="padding:14px 20px;text-align:right;">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                            <a href="{{ route('admin.payout-methods.edit', $method->id) }}"
                               style="padding:6px;border-radius:6px;color:var(--fg-3);display:flex;align-items:center;transition:.12s;"
                               onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                               onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'"
                               title="Edit">
                                <i data-lucide="edit-3" style="width:15px;height:15px;"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.payout-methods.toggle', $method->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit"
                                        style="padding:6px;border-radius:6px;color:var(--fg-3);background:transparent;border:none;cursor:pointer;display:flex;align-items:center;transition:.12s;"
                                        onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                        onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'"
                                        title="{{ $method->status ? 'Deactivate' : 'Activate' }}">
                                    <i data-lucide="{{ $method->status ? 'eye-off' : 'eye' }}" style="width:15px;height:15px;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="padding:48px;text-align:center;color:var(--fg-3);">
                        <i data-lucide="wallet" style="width:28px;height:28px;margin:0 auto 10px;display:block;opacity:0.3;"></i>
                        <div style="font-size:13px;">No payout methods configured.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
