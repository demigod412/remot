@extends('admin.layouts.app')
@section('title', 'Notification Templates')
@section('page-title', 'Notification Templates')

@section('content')

<div class="jobstation-card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid var(--border);">
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Template</th>
                    <th style="text-align:left;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Action Key</th>
                    <th style="text-align:center;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Email</th>
                    <th style="text-align:center;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">SMS</th>
                    <th style="text-align:right;padding:12px 20px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tpl)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:14px 20px;">
                        <div style="font-size:13px;font-weight:500;color:var(--fg);">{{ $tpl->name }}</div>
                        <div style="font-size:11.5px;color:var(--fg-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px;">{{ $tpl->subj }}</div>
                    </td>
                    <td style="padding:14px 20px;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-3);">{{ $tpl->act }}</td>
                    <td style="padding:14px 20px;text-align:center;">
                        @if($tpl->email_status)
                        <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:rgba(34,197,94,0.12);color:#22C55E;">On</span>
                        @else
                        <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:var(--surface-2);color:var(--fg-3);">Off</span>
                        @endif
                    </td>
                    <td style="padding:14px 20px;text-align:center;">
                        @if($tpl->sms_status)
                        <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:rgba(34,197,94,0.12);color:#22C55E;">On</span>
                        @else
                        <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:var(--surface-2);color:var(--fg-3);">Off</span>
                        @endif
                    </td>
                    <td style="padding:14px 20px;text-align:right;">
                        <a href="{{ route('admin.notif-templates.edit', $tpl->id) }}"
                           style="padding:6px;border-radius:6px;color:var(--fg-3);display:inline-flex;align-items:center;transition:.12s;"
                           onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                           onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'"
                           title="Edit">
                            <i data-lucide="edit-3" style="width:15px;height:15px;"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:48px;text-align:center;color:var(--fg-3);font-size:13px;">
                        No templates found. Run the seeder to create defaults.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($templates->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:between;gap:8px;">
        {{ $templates->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
