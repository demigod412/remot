@extends('admin.layouts.app')
@section('title', 'Review Application')
@section('page-title', 'Review Application')

@section('content')

<a href="{{ route('admin.membership.index') }}"
   style="display:inline-block;margin-bottom:16px;font-size:12.5px;color:var(--fg-3);text-decoration:none;">← Back to queue</a>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:16px;">

    {{-- Details --}}
    <div class="jobstation-card" style="padding:22px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;">
            <div>
                <div style="font-size:22px;font-weight:600;letter-spacing:-0.4px;">{{ $application->full_name }}</div>
                <div class="mono" style="font-size:12.5px;color:var(--fg-3);margin-top:2px;">{{ $application->reference_code }}</div>
            </div>
            <span style="display:inline-block;padding:4px 12px;border-radius:99px;font-size:12px;font-weight:600;
                  background:{{ $application->status === 1 ? 'rgba(34,197,94,0.12)' : ($application->status === 2 ? 'rgba(239,68,68,0.12)' : 'rgba(245,158,11,0.12)') }};
                  color:{{ $application->status === 1 ? '#22C55E' : ($application->status === 2 ? '#EF4444' : '#F59E0B') }};">
                {{ $application->status_label }}
            </span>
        </div>

        @php
            $rows = [
                'Applicant type' => $application->applicant_type_label,
                'Email'          => $application->email,
                'Phone'          => $application->phone ?: '-',
                'Country'        => $application->country ?: '-',
                'Submitted'      => $application->submitted_at?->toDayDateTimeString() ?? '-',
                'IP address'     => $application->ip_address ?: '-',
            ];

            if ($application->is_business) {
                $rows += [
                    'Business name'    => $application->business_name ?: '-',
                    'Business email'   => $application->business_email ?: '-',
                    'Business country' => $application->business_country ?: '-',
                ];
            }
        @endphp

        <table style="width:100%;font-size:13.5px;border-collapse:collapse;">
            @foreach ($rows as $label => $value)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:10px 0;color:var(--fg-3);width:170px;">{{ $label }}</td>
                    <td style="padding:10px 0;color:var(--fg);">{{ $value }}</td>
                </tr>
            @endforeach
        </table>

        {{-- Documents. Private disk, so they are streamed through the app. --}}
        <div style="margin-top:22px;">
            <div class="label" style="margin-bottom:10px;">Documents</div>
            @php
                // Streamed through SecureFileController: private disk, admin only.
                $docs = array_filter([
                    'resume'       => ['CV / Resume', $application->resume_path],
                    'cover'        => ['Cover letter', $application->cover_letter_path],
                    'registration' => ['Registration document', $application->business_registration_doc],
                ], fn ($d) => ! empty($d[1]));
            @endphp

            @forelse ($docs as $kind => $doc)
                <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px;">
                    <i data-lucide="file-text" style="width:15px;height:15px;color:var(--fg-3);"></i>
                    <span style="color:var(--fg-2);">{{ $doc[0] }}</span>
                    <a href="{{ route('secure.membershipDoc', ['application' => $application->id, 'kind' => $kind]) }}"
                       style="margin-left:auto;font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;">Download</a>
                </div>
            @empty
                <div style="font-size:13px;color:var(--fg-3);">No documents attached.</div>
            @endforelse
        </div>

        @if ($application->status === 2 && $application->rejection_reason)
            <div style="margin-top:20px;padding:12px 14px;border-radius:8px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);font-size:13px;">
                <strong style="color:#EF4444;">Rejection reason:</strong>
                <span style="color:var(--fg-2);">{{ $application->rejection_reason }}</span>
            </div>
        @endif

        @if ($application->reviewed_at)
            <div style="margin-top:16px;font-size:12px;color:var(--fg-3);">
                Reviewed {{ $application->reviewed_at->toDayDateTimeString() }}
                @if ($application->reviewer) by {{ $application->reviewer->username ?? 'admin' }} @endif
            </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="jobstation-card" style="padding:22px;height:fit-content;">
        <div class="label" style="margin-bottom:14px;">Decision</div>

        @if ($application->is_pending)
            <div style="font-size:12.5px;color:var(--fg-3);line-height:1.6;margin-bottom:16px;">
                Approving creates the user account immediately, generates a temporary
                password and emails the login details. The account is forced to change
                that password on first login.
            </div>

            <form method="POST" action="{{ route('admin.membership.approve', $application->id) }}"
                  onsubmit="return confirm('Approve this application and create the account? This cannot be undone.');"
                  style="margin-bottom:20px;">
                @csrf
                <button type="submit"
                        style="width:100%;padding:11px;border:0;border-radius:8px;background:#22C55E;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                    Approve &amp; create account
                </button>
            </form>

            <div style="height:1px;background:var(--border);margin:20px 0;"></div>

            <form method="POST" action="{{ route('admin.membership.reject', $application->id) }}"
                  onsubmit="return confirm('Reject this application?');">
                @csrf
                <label for="rejection_reason" style="display:block;font-size:12.5px;margin-bottom:6px;color:var(--fg-2);">
                    Reason (sent to the applicant)
                </label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4" required minlength="5" maxlength="500"
                          style="width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;"></textarea>
                <button type="submit"
                        style="width:100%;margin-top:10px;padding:11px;border:0;border-radius:8px;background:#EF4444;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                    Reject application
                </button>
            </form>
        @else
            <div style="font-size:13px;color:var(--fg-3);line-height:1.6;">
                This application has already been {{ strtolower($application->status_label) }}
                and cannot be actioned again.
            </div>
        @endif
    </div>
</div>

@endsection
