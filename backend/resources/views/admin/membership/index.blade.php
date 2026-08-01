@extends('admin.layouts.app')
@section('title', 'Membership Applications')
@section('page-title', 'Membership Applications')

@section('content')

{{-- Stat tiles --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;">{{ number_format($stats['total']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">all time</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Pending</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#F59E0B;">{{ number_format($stats['pending']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">awaiting review</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Approved</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#22C55E;">{{ number_format($stats['approved']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">accounts created</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Rejected</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#EF4444;">{{ number_format($stats['rejected']) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">declined</div>
    </div>
</div>

{{-- Filters --}}
<div class="jobstation-card" style="padding:14px 16px;margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.membership.index') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, reference, business"
               style="flex:1;min-width:200px;padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
        <select name="status" style="padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
            <option value="">All statuses</option>
            <option value="0" @selected(request('status') === '0')>Pending</option>
            <option value="1" @selected(request('status') === '1')>Approved</option>
            <option value="2" @selected(request('status') === '2')>Rejected</option>
        </select>
        <select name="applicant_type" style="padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
            <option value="">All types</option>
            <option value="1" @selected(request('applicant_type') === '1')>Individual</option>
            <option value="2" @selected(request('applicant_type') === '2')>Business</option>
        </select>
        <button type="submit" style="padding:8px 16px;border:0;border-radius:8px;background:var(--accent);color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
            Filter
        </button>
    </form>
</div>

{{-- Table --}}
<div class="jobstation-card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Applicant</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Type</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Reference</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Submitted</th>
                <th style="text-align:left;padding:12px 16px;font-weight:600;color:var(--fg-3);font-size:11.5px;text-transform:uppercase;">Status</th>
                <th style="padding:12px 16px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $app)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:12px 16px;">
                        <div style="font-weight:600;color:var(--fg);">{{ $app->full_name }}</div>
                        <div style="color:var(--fg-3);font-size:12px;">{{ $app->email }}</div>
                        @if ($app->business_name)
                            <div style="color:var(--fg-3);font-size:12px;">{{ $app->business_name }}</div>
                        @endif
                    </td>
                    <td style="padding:12px 16px;color:var(--fg-2);">{{ $app->applicant_type_label }}</td>
                    <td style="padding:12px 16px;" class="mono">{{ $app->reference_code }}</td>
                    <td style="padding:12px 16px;color:var(--fg-2);">{{ $app->submitted_at?->diffForHumans() ?? '-' }}</td>
                    <td style="padding:12px 16px;">
                        <span style="display:inline-block;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;
                              background:{{ $app->status === 1 ? 'rgba(34,197,94,0.12)' : ($app->status === 2 ? 'rgba(239,68,68,0.12)' : 'rgba(245,158,11,0.12)') }};
                              color:{{ $app->status === 1 ? '#22C55E' : ($app->status === 2 ? '#EF4444' : '#F59E0B') }};">
                            {{ $app->status_label }}
                        </span>
                    </td>
                    <td style="padding:12px 16px;text-align:right;">
                        <a href="{{ route('admin.membership.show', $app->id) }}"
                           style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;">Review →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--fg-3);">No applications found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">{{ $applications->links() }}</div>

@endsection
