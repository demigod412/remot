@extends('admin.layouts.app')
@section('title', 'Task Review')
@section('page-title', 'Task Review Queue')

@section('content')

@if($filterWork)
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(47,84,235,0.08);border:1px solid rgba(47,84,235,0.2);margin-bottom:16px;font-size:13px;">
    <i data-lucide="briefcase" style="width:15px;height:15px;color:var(--accent);flex-shrink:0;"></i>
    <span style="color:var(--fg-2);">Filtered by: <strong style="color:var(--fg);">{{ $filterWork->title }}</strong></span>
    <a href="{{ route('admin.task-review.index', ['tab' => $tab]) }}" style="margin-left:auto;font-size:11.5px;color:var(--fg-3);text-decoration:none;">Clear ×</a>
</div>
@endif

{{-- Queue tabs. Applications first, since that is where money is at stake. --}}
@php
    $tabs = [
        'applications' => ['Applications', $stats['applications'], 'Awaiting your yes or no'],
        'deliveries'   => ['Deliveries',   $stats['deliveries'],   'Work submitted, needs review'],
        'revisions'    => ['Revisions',    $stats['revisions'],    'Sent back to the worker'],
        'settled'      => ['Settled',      null,                   'Approved, rejected, expired'],
    ];
@endphp

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">
    @foreach ($tabs as $key => [$label, $count, $hint])
        <a href="{{ route('admin.task-review.index', ['tab' => $key]) }}"
           class="jobstation-card"
           style="padding:18px;text-decoration:none;display:block;
                  border:1px solid {{ $tab === $key ? 'var(--accent)' : 'var(--border)' }};">
            <div class="label" style="margin-bottom:6px;">{{ $label }}</div>
            <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:{{ $tab === $key ? 'var(--accent)' : 'var(--fg)' }};">
                {{ $count === null ? '—' : number_format($count) }}
            </div>
            <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">{{ $hint }}</div>
        </a>
    @endforeach
</div>

<div class="jobstation-card" style="padding:14px 16px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.task-review.index') }}" style="display:flex;gap:10px;align-items:center;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Worker username or email"
               style="flex:1;padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
        <button type="submit" style="padding:8px 16px;border:0;border-radius:8px;background:var(--accent);color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
            Search
        </button>
    </form>
</div>

<div class="jobstation-card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Worker</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Task</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Fee paid</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Stage</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Deadline</th>
                <th style="padding:12px 16px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($submissions as $s)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:12px 16px;">
                        <div style="font-weight:600;color:var(--fg);">{{ $s->worker->username ?? 'user #' . $s->worker_id }}</div>
                        <div style="color:var(--fg-3);font-size:12px;">{{ $s->worker->email ?? '' }}</div>
                    </td>
                    <td style="padding:12px 16px;color:var(--fg-2);">{{ $s->work->title ?? '—' }}</td>
                    <td style="padding:12px 16px;" class="mono">{{ formatCoins($s->fee_paid) }}</td>
                    <td style="padding:12px 16px;">
                        <span style="display:inline-block;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;background:rgba(120,120,120,0.12);color:var(--fg-2);">
                            {{ $s->lifecycle_label }}
                        </span>
                        @if ($s->revision_count > 0)
                            <span style="font-size:11px;color:var(--fg-3);margin-left:6px;">rev {{ $s->revision_count }}</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;color:{{ $s->deadline_at && $s->deadline_at->isPast() ? '#EF4444' : 'var(--fg-2)' }};">
                        {{ $s->deadline_at?->diffForHumans() ?? '—' }}
                    </td>
                    <td style="padding:12px 16px;text-align:right;">
                        <a href="{{ route('admin.task-review.show', $s->id) }}"
                           style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;">Open →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--fg-3);">Nothing in this queue.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">{{ $submissions->links() }}</div>

@endsection
