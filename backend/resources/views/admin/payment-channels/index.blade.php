@extends('admin.layouts.app')
@section('title', 'Payment Channels')
@section('page-title', 'Payment Channels')

@section('content')

<div style="font-size:13px;color:var(--fg-3);margin-bottom:18px;">Configure payment gateways for coin top-ups.</div>

@php $auto = $channels->where('is_manual', false); $manual = $channels->where('is_manual', true); @endphp

{{-- Automatic Gateways --}}
@if($auto->count())
<div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-3);margin-bottom:10px;">Automatic Gateways</div>
<div class="jobstation-card" style="overflow:hidden;margin-bottom:20px;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Gateway</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Driver</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Currencies</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Crypto</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Status</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($auto as $channel)
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:13px 20px;">
                    <div style="font-weight:500;color:var(--fg);">{{ $channel->name }}</div>
                    <div style="font-size:11.5px;color:var(--fg-4);font-family:ui-monospace,monospace;margin-top:1px;">{{ $channel->code }}</div>
                </td>
                <td style="padding:13px 20px;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-3);">{{ $channel->driver }}</td>
                <td style="padding:13px 20px;font-size:12.5px;color:var(--fg-3);">
                    {{ $channel->currencies ? implode(', ', array_keys((array)$channel->currencies)) : '—' }}
                </td>
                <td style="padding:13px 20px;text-align:center;">
                    @if($channel->is_crypto)
                        <span class="badge-warning" style="font-size:11px;">Yes</span>
                    @else
                        <span class="badge-default" style="font-size:11px;">No</span>
                    @endif
                </td>
                <td style="padding:13px 20px;text-align:center;">
                    @if($channel->status)
                        <span class="badge-success" style="font-size:11px;">Active</span>
                    @else
                        <span class="badge-default" style="font-size:11px;">Inactive</span>
                    @endif
                </td>
                <td style="padding:13px 20px;text-align:right;">
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                        <a href="{{ route('admin.payment-channels.edit', $channel->id) }}"
                           style="padding:6px;border-radius:7px;color:var(--fg-3);display:flex;align-items:center;"
                           title="Configure"
                           onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                           onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                            <i data-lucide="settings" style="width:15px;height:15px;"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.payment-channels.toggle', $channel->id) }}">
                            @csrf
                            <button type="submit"
                                    style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                    title="{{ $channel->status ? 'Disable' : 'Enable' }}"
                                    onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                                <i data-lucide="{{ $channel->status ? 'eye-off' : 'eye' }}" style="width:15px;height:15px;"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Manual Methods --}}
@if($manual->count())
<div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-3);margin-bottom:10px;">Manual Payment Methods</div>
<div class="jobstation-card" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Method</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Instructions</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Status</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($manual as $channel)
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:13px 20px;">
                    <div style="font-weight:500;color:var(--fg);">{{ $channel->name }}</div>
                    <div style="font-size:11.5px;color:var(--fg-4);font-family:ui-monospace,monospace;margin-top:1px;">{{ $channel->code }}</div>
                </td>
                <td style="padding:13px 20px;font-size:12.5px;color:var(--fg-3);max-width:260px;">
                    <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $channel->instructions ?? '—' }}</div>
                </td>
                <td style="padding:13px 20px;text-align:center;">
                    @if($channel->status)
                        <span class="badge-success" style="font-size:11px;">Active</span>
                    @else
                        <span class="badge-default" style="font-size:11px;">Inactive</span>
                    @endif
                </td>
                <td style="padding:13px 20px;text-align:right;">
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                        <a href="{{ route('admin.payment-channels.edit', $channel->id) }}"
                           style="padding:6px;border-radius:7px;color:var(--fg-3);display:flex;align-items:center;"
                           title="Configure"
                           onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                           onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                            <i data-lucide="settings" style="width:15px;height:15px;"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.payment-channels.toggle', $channel->id) }}">
                            @csrf
                            <button type="submit"
                                    style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                    title="{{ $channel->status ? 'Disable' : 'Enable' }}"
                                    onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                                <i data-lucide="{{ $channel->status ? 'eye-off' : 'eye' }}" style="width:15px;height:15px;"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($channels->isEmpty())
<div class="jobstation-card" style="padding:56px;text-align:center;color:var(--fg-3);">
    <i data-lucide="credit-card" style="width:36px;height:36px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
    <div style="font-size:14px;">No payment channels found. Run the seeder to populate defaults.</div>
</div>
@endif

@endsection
