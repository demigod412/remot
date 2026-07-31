@extends('admin.layouts.app')
@section('title', $listing->title)
@section('page-title', 'Job Listing Review')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.jobs.listings.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Job Listings</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:300px;">{{ $listing->title }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;" class="listing-show-grid">

    {{-- Left: Listing Details --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        <div class="jobstation-card" style="overflow:hidden;">
            @if($listing->cover_image)
            <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image) }}"
                 style="width:100%;height:200px;object-fit:cover;display:block;" alt="">
            @endif
            <div style="padding:24px;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px;">
                    <h2 style="font-size:20px;font-weight:700;color:var(--fg);margin:0;flex:1;line-height:1.3;">{{ $listing->title }}</h2>
                    @php
                        $statusMap = [
                            0 => ['label'=>'Pending',  'bg'=>'rgba(245,158,11,0.12)', 'color'=>'#F59E0B'],
                            1 => ['label'=>'Active',   'bg'=>'rgba(34,197,94,0.12)',  'color'=>'#22C55E'],
                            2 => ['label'=>'Closed',   'bg'=>'var(--surface-2)',      'color'=>'var(--fg-3)'],
                            3 => ['label'=>'Rejected', 'bg'=>'rgba(239,68,68,0.12)',  'color'=>'#EF4444'],
                        ];
                        $sm = $statusMap[$listing->status] ?? $statusMap[0];
                    @endphp
                    <span style="display:inline-flex;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:500;background:{{ $sm['bg'] }};color:{{ $sm['color'] }};flex-shrink:0;">
                        {{ $sm['label'] }}
                    </span>
                </div>

                @if($listing->rejection_reason)
                <div style="padding:12px 14px;background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.2);border-radius:8px;font-size:13px;color:#EF4444;margin-bottom:16px;">
                    <strong>Rejection reason:</strong> {{ $listing->rejection_reason }}
                </div>
                @endif

                <div style="font-size:13.5px;color:var(--fg-2);line-height:1.75;margin-bottom:16px;">
                    {!! richBody($listing->description) !!}
                </div>

                @if($listing->requirements)
                <h4 style="font-size:13px;font-weight:600;color:var(--fg);margin:16px 0 8px;">Requirements</h4>
                <div style="font-size:13px;color:var(--fg-2);white-space:pre-line;line-height:1.7;">{{ $listing->requirements }}</div>
                @endif

                @if($listing->benefits)
                <h4 style="font-size:13px;font-weight:600;color:var(--fg);margin:16px 0 8px;">Benefits</h4>
                <div style="font-size:13px;color:var(--fg-2);white-space:pre-line;line-height:1.7;">{{ $listing->benefits }}</div>
                @endif
            </div>
        </div>

        {{-- Applications --}}
        <div class="jobstation-card" style="padding:24px;">
            <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 16px;">Applications ({{ $appStats['total'] }})</h3>
            @if($applications->count())
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th style="text-align:left;padding:8px 0;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Applicant</th>
                            <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Status</th>
                            <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Salary</th>
                            <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Applied</th>
                            <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.04em;">Resume</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($applications as $app)
                        @php
                            $appMap = [
                                0 => ['label'=>'Pending',     'bg'=>'rgba(245,158,11,0.12)', 'color'=>'#F59E0B'],
                                1 => ['label'=>'Reviewed',    'bg'=>'rgba(96,165,250,0.12)',  'color'=>'#60A5FA'],
                                2 => ['label'=>'Shortlisted', 'bg'=>'rgba(47,84,235,0.12)',  'color'=>'var(--accent)'],
                                3 => ['label'=>'Accepted',    'bg'=>'rgba(34,197,94,0.12)',   'color'=>'#22C55E'],
                                4 => ['label'=>'Rejected',    'bg'=>'rgba(239,68,68,0.12)',   'color'=>'#EF4444'],
                            ];
                            $am = $appMap[$app->status] ?? $appMap[0];
                        @endphp
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:10px 0;">
                                <div style="font-size:13px;color:var(--fg);">{{ $app->applicant->fullname ?? '-' }}</div>
                                <div style="font-size:11.5px;color:var(--fg-3);">{{ $app->applicant->email ?? '' }}</div>
                            </td>
                            <td style="padding:10px 12px;">
                                <span style="display:inline-flex;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:500;background:{{ $am['bg'] }};color:{{ $am['color'] }};">{{ $am['label'] }}</span>
                            </td>
                            <td style="padding:10px 12px;font-size:12px;color:var(--fg-3);">
                                {{ $app->expected_salary ? ($app->expected_salary_currency . ' ' . number_format($app->expected_salary)) : '—' }}
                            </td>
                            <td style="padding:10px 12px;font-size:12px;color:var(--fg-3);">{{ $app->created_at->format('M d, Y') }}</td>
                            <td style="padding:10px 12px;">
                                @if($app->resume)
                                <a href="{{ route('secure.resume', $app->id) }}" target="_blank"
                                   style="font-size:12px;color:var(--accent);text-decoration:none;"
                                   onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Download</a>
                                @else
                                <span style="font-size:12px;color:var(--fg-4);">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $applications->links() }}
            @else
            <p style="font-size:13px;color:var(--fg-3);">No applications yet.</p>
            @endif
        </div>
    </div>

    {{-- Right: Admin Actions --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Employer Info --}}
        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">Employer</div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div style="width:38px;height:38px;border-radius:50%;background:rgba(47,84,235,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="user" style="width:16px;height:16px;color:var(--accent);opacity:.6;"></i>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:13px;font-weight:500;color:var(--fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $listing->employer->fullname ?? '-' }}</div>
                    <div style="font-size:11.5px;color:var(--fg-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $listing->employer->email ?? '' }}</div>
                    @if(($listing->employer->kyc_status ?? 0) === 1)
                    <div style="font-size:11px;color:#22C55E;display:flex;align-items:center;gap:3px;margin-top:2px;">
                        <i data-lucide="shield-check" style="width:11px;height:11px;"></i> Verified
                    </div>
                    @endif
                </div>
            </div>
            @if($listing->employer)
            <a href="{{ route('admin.users.show', $listing->employer_id) }}"
               style="font-size:12.5px;color:var(--accent);text-decoration:none;"
               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">View user profile →</a>
            @endif
        </div>

        {{-- Listing Details --}}
        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">Details</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                @foreach([
                    ['Category',     $listing->category->name ?? '—'],
                    ['Type',         $listing->employmentTypeLabel],
                    ['Location',     $listing->locationTypeLabel],
                    ['Salary',       $listing->salaryRange],
                    ['Applications', $appStats['total']],
                ] as [$lbl, $val])
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:var(--fg-3);">{{ $lbl }}</span>
                    <span style="color:var(--fg-2);text-align:right;">{{ $val }}</span>
                </div>
                @endforeach
                @if($listing->closes_at)
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <span style="color:var(--fg-3);">Deadline</span>
                    <span style="color:var(--fg-2);">{{ $listing->closes_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Approve / Reject --}}
        @if(in_array($listing->status, [0, 1, 3]))
        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">Actions</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @if($listing->status !== 1)
                <form method="POST" action="{{ route('admin.jobs.listings.approve', $listing->id) }}">
                    @csrf
                    <button type="submit"
                            style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;border-radius:9px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#22C55E;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:.12s;"
                            onmouseover="this.style.background='rgba(34,197,94,0.18)'"
                            onmouseout="this.style.background='rgba(34,197,94,0.1)'">
                        <i data-lucide="check-circle" style="width:14px;height:14px;"></i> Approve & Publish
                    </button>
                </form>
                @endif

                <div x-data="{ open: false }">
                    <button @click="open = !open"
                            style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;border-radius:9px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#EF4444;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:.12s;"
                            onmouseover="this.style.background='rgba(239,68,68,0.15)'"
                            onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                        <i data-lucide="x-circle" style="width:14px;height:14px;"></i> Reject
                    </button>
                    <div x-show="open" x-cloak x-transition style="margin-top:10px;">
                        <form method="POST" action="{{ route('admin.jobs.listings.reject', $listing->id) }}">
                            @csrf
                            <textarea name="rejection_reason" rows="3" required
                                      placeholder="Reason for rejection…"
                                      style="width:100%;font-size:13px;resize:none;margin-bottom:8px;"></textarea>
                            @error('rejection_reason') <div style="font-size:12px;color:#EF4444;margin-bottom:6px;">{{ $message }}</div> @enderror
                            <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;padding:8px;font-size:13px;">
                                Confirm Reject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Feature toggle --}}
        @if($listing->status === 1)
        <form method="POST" action="{{ route('admin.jobs.listings.feature', $listing->id) }}">
            @csrf
            @php $isFeatured = $listing->is_featured && $listing->featured_until?->isFuture(); @endphp
            <button type="submit"
                    style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;border-radius:9px;background:{{ $isFeatured ? 'rgba(245,158,11,0.1)' : 'var(--surface-2)' }};border:1px solid {{ $isFeatured ? 'rgba(245,158,11,0.25)' : 'var(--border)' }};color:#F59E0B;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:.12s;"
                    onmouseover="this.style.background='rgba(245,158,11,0.15)'"
                    onmouseout="this.style.background='{{ $isFeatured ? 'rgba(245,158,11,0.1)' : 'var(--surface-2)' }}'">
                <i data-lucide="zap" style="width:14px;height:14px;"></i>
                {{ $isFeatured ? 'Unfeature Listing' : 'Feature Listing' }}
            </button>
        </form>
        @endif

        {{-- KYC toggle --}}
        <form method="POST" action="{{ route('admin.jobs.listings.toggle-kyc', $listing->id) }}">
            @csrf
            <button type="submit"
                    style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;border-radius:9px;background:{{ $listing->requires_kyc ? 'rgba(245,158,11,0.08)' : 'var(--surface-2)' }};border:1px solid {{ $listing->requires_kyc ? 'rgba(245,158,11,0.2)' : 'var(--border)' }};color:{{ $listing->requires_kyc ? '#F59E0B' : 'var(--fg-3)' }};font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:.12s;"
                    onmouseover="this.style.background='rgba(245,158,11,0.12)';this.style.color='#F59E0B'"
                    onmouseout="this.style.background='{{ $listing->requires_kyc ? 'rgba(245,158,11,0.08)' : 'var(--surface-2)' }}';this.style.color='{{ $listing->requires_kyc ? '#F59E0B' : 'var(--fg-3)' }}'">
                <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                {{ $listing->requires_kyc ? 'Remove KYC Requirement' : 'Require KYC' }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.jobs.listings.delete', $listing->id) }}">
            @csrf @method('DELETE')
            <button type="submit"
                    style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;border-radius:9px;background:transparent;border:1px solid var(--border);color:var(--fg-3);font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:.12s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.07)';this.style.color='#EF4444';this.style.borderColor='rgba(239,68,68,0.2)'"
                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)';this.style.borderColor='var(--border)'"
                    onclick="return confirm('Delete this listing permanently?')">
                <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Delete Listing
            </button>
        </form>
    </div>

</div>

<style>
@media (max-width: 900px) { .listing-show-grid { grid-template-columns: 1fr !important; } }
</style>

@endsection
