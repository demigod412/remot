@extends('admin.layouts.app')
@section('title', 'Audit Log')
@section('page-title', 'Admin Audit Log')

@section('content')

<div style="padding:12px 16px;border-radius:10px;background:rgba(47,84,235,0.06);border:1px solid rgba(47,84,235,0.15);margin-bottom:16px;font-size:12.5px;color:var(--fg-2);line-height:1.6;">
    Every irreversible admin action writes exactly one row here: membership decisions,
    application approvals and rejections, payouts, refunds, bans and manual credits.
    Rows are append-only and cannot be edited from the panel.
</div>

<div class="jobstation-card" style="padding:14px 16px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.audit-log') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Subject, meta or IP"
               style="flex:1;min-width:180px;padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
        <select name="action" style="padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
            <option value="">All actions</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" style="padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
        <input type="date" name="to" value="{{ request('to') }}" style="padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
        <button type="submit" style="padding:8px 16px;border:0;border-radius:8px;background:var(--accent);color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
            Filter
        </button>
    </form>
</div>

<div class="jobstation-card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">When</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Admin</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Action</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Subject</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Detail</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:11px 16px;color:var(--fg-2);white-space:nowrap;">
                        {{ $log->created_at?->format('d M Y H:i') }}
                    </td>
                    <td style="padding:11px 16px;color:var(--fg-2);">
                        {{ $log->admin->username ?? ($log->admin_id === 0 ? 'system' : '#' . $log->admin_id) }}
                    </td>
                    <td style="padding:11px 16px;">
                        <span class="mono" style="font-size:12px;color:var(--fg);">{{ $log->action }}</span>
                    </td>
                    <td style="padding:11px 16px;color:var(--fg-3);font-size:12px;">{{ $log->subject_label }}</td>
                    <td style="padding:11px 16px;color:var(--fg-3);font-size:12px;max-width:320px;">
                        @if (!empty($log->meta))
                            @php
                                // Show the money fields first, they are what matters in an audit.
                                $priority = array_filter([
                                    'coins'      => $log->meta['coins']      ?? null,
                                    'net'        => $log->meta['net']        ?? null,
                                    'commission' => $log->meta['commission'] ?? null,
                                    'reason'     => $log->meta['reason']     ?? null,
                                ], fn ($v) => $v !== null && $v !== '');
                            @endphp
                            @foreach ($priority as $k => $v)
                                <div><span style="color:var(--fg-3);">{{ $k }}:</span> <span style="color:var(--fg-2);">{{ \Illuminate\Support\Str::limit((string) $v, 60) }}</span></div>
                            @endforeach
                        @else
                            —
                        @endif
                    </td>
                    <td style="padding:11px 16px;color:var(--fg-3);font-size:12px;" class="mono">{{ $log->ip_address ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--fg-3);">No log entries yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">{{ $logs->links() }}</div>

@endsection
