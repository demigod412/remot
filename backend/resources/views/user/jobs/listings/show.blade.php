@extends('user.layouts.app')
@section('title', $listing->title)
@section('page-title', __('Job Listing'))

@section('content')

{{-- Breadcrumb --}}
<div style="font-size:12.5px; color:var(--fg-3); margin-bottom:24px; display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
    <a href="{{ route('user.jobs.listings.index') }}" style="color:var(--fg-3); text-decoration:none; transition:color .15s;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">{{ __('My Listings') }}</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-2);">{{ Str::limit($listing->title, 45) }}</span>
</div>

@php
    $statusBg    = ['rgba(245,158,11,0.1)', 'rgba(34,197,94,0.1)',  'rgba(100,116,139,0.1)', 'rgba(239,68,68,0.1)'];
    $statusColor = ['#F59E0B',              '#22C55E',               'var(--fg-3)',            '#EF4444'];
    $statusLabel = ['Pending',              'Active',                'Closed',                'Rejected'];
    $appBg       = ['rgba(245,158,11,0.1)', 'rgba(59,130,246,0.1)', 'rgba(168,85,247,0.1)', 'rgba(34,197,94,0.1)', 'rgba(239,68,68,0.1)'];
    $appColor    = ['#F59E0B',              '#3B82F6',               '#A855F7',              '#22C55E',              '#EF4444'];
    $appLabel    = ['Pending',              'Reviewed',              'Shortlisted',          'Accepted',             'Rejected'];
@endphp

<div style="display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start;" class="listing-grid">

    {{-- ── LEFT COLUMN ─────────────────────────────────────────── --}}
    <div style="display:flex; flex-direction:column; gap:20px;">

        {{-- Main info card --}}
        <div class="card" style="padding:0; overflow:hidden;">
            @if($listing->cover_image)
            <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image) }}"
                 alt="{{ $listing->title }}" style="width:100%; height:180px; object-fit:cover; display:block;">
            @endif
            <div style="padding:22px;">
                <div style="display:flex; align-items:flex-start; gap:12px; margin-bottom:14px; flex-wrap:wrap;">
                    <h2 style="font-size:20px; font-weight:700; color:var(--fg); margin:0; flex:1; line-height:1.25;">{{ $listing->title }}</h2>
                    <span style="font-size:11px; font-weight:600; padding:3px 10px; border-radius:999px; flex-shrink:0;
                                 background:{{ $statusBg[$listing->status] ?? 'var(--surface-2)' }};
                                 color:{{ $statusColor[$listing->status] ?? 'var(--fg-3)' }};">
                        {{ $statusLabel[$listing->status] ?? 'Unknown' }}
                    </span>
                </div>

                @if($listing->status === 3 && $listing->rejection_reason)
                <div style="padding:12px 14px; border-radius:8px; background:rgba(239,68,68,0.07); border:1px solid rgba(239,68,68,0.18); font-size:13px; color:#EF4444; margin-bottom:16px;">
                    <strong>{{ __('Rejection reason') }}:</strong> {{ $listing->rejection_reason }}
                </div>
                @endif

                <div class="job-prose" style="font-size:14px; color:var(--fg-2); line-height:1.7; margin-bottom:8px;">
                    {!! richBody($listing->description) !!}
                </div>

                @if($listing->requirements)
                <h4 style="font-size:13.5px; font-weight:600; color:var(--fg); margin:20px 0 8px;">{{ __('Requirements') }}</h4>
                <div style="font-size:13.5px; color:var(--fg-2); line-height:1.7;">{!! richBody($listing->requirements) !!}</div>
                @endif

                @if($listing->benefits)
                <h4 style="font-size:13.5px; font-weight:600; color:var(--fg); margin:20px 0 8px;">{{ __('Benefits') }}</h4>
                <div style="font-size:13.5px; color:var(--fg-2); line-height:1.7;">{!! richBody($listing->benefits) !!}</div>
                @endif
            </div>
        </div>

        {{-- Applications --}}
        <div class="card" style="padding:22px;">
            <h3 style="font-size:14.5px; font-weight:600; color:var(--fg); margin:0 0 18px;">
                {{ __('Applications') }} <span class="mono" style="color:var(--fg-3); font-weight:400;">({{ $appStats['total'] }})</span>
            </h3>

            @forelse($applications as $app)
            <div style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border);">
                {{-- Avatar --}}
                @php $avatarField = $app->applicant->avatar ?? $app->applicant->image ?? null; @endphp
                @if($avatarField)
                <img src="{{ fileUrl(config('jobstation.upload_paths.avatars'), $avatarField) }}"
                     style="width:40px; height:40px; border-radius:50%; object-fit:cover; flex-shrink:0;" alt="">
                @else
                <div style="width:40px; height:40px; border-radius:50%; background:rgba(47,84,235,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="opacity:.5;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                @endif

                <div style="flex:1; min-width:0;">
                    <div style="font-size:13.5px; font-weight:500; color:var(--fg); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $app->applicant->fullname }}</div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <div style="font-size:11.5px; color:var(--fg-4);">{{ $app->created_at->diffForHumans() }}</div>
                        @include('user.partials.rating-stars', ['user' => $app->applicant])
                    </div>
                </div>

                @if(!$app->is_read)
                <div style="width:7px; height:7px; border-radius:50%; background:var(--accent); flex-shrink:0;"></div>
                @endif

                <span style="font-size:11px; font-weight:600; padding:2px 9px; border-radius:999px; flex-shrink:0; white-space:nowrap;
                             background:{{ $appBg[$app->status] ?? 'var(--surface-2)' }};
                             color:{{ $appColor[$app->status] ?? 'var(--fg-3)' }};">
                    {{ $appLabel[$app->status] ?? 'Unknown' }}
                </span>

                <a href="{{ route('user.contracts.create', ['worker' => $app->applicant->username, 'title' => 'Contract: ' . $listing->title]) }}"
                   style="font-size:12px; padding:5px 12px; border-radius:8px; background:rgba(47,84,235,0.06); color:var(--accent); text-decoration:none; white-space:nowrap; flex-shrink:0; transition:background .15s; border:1px solid rgba(47,84,235,0.15); display:flex; align-items:center; gap:5px;"
                   onmouseover="this.style.background='rgba(47,84,235,0.14)'" onmouseout="this.style.background='rgba(47,84,235,0.06)'"
                   title="{{ __('Offer Contract') }}">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    {{ __('Offer') }}
                </a>
                <a href="{{ route('user.jobs.listings.applications.review', [$listing->id, $app->id]) }}"
                   style="font-size:12px; padding:5px 14px; border-radius:8px; background:rgba(47,84,235,0.08); color:var(--accent); text-decoration:none; white-space:nowrap; flex-shrink:0; transition:background .15s; border:1px solid rgba(47,84,235,0.15);"
                   onmouseover="this.style.background='rgba(47,84,235,0.16)'" onmouseout="this.style.background='rgba(47,84,235,0.08)'">
                    {{ __('Review') }}
                </a>
            </div>
            @empty
            <div style="padding:40px 0; text-align:center; color:var(--fg-3); font-size:13.5px;">
                {{ __('No applications yet.') }}
            </div>
            @endforelse

            <div style="margin-top:16px;">{{ $applications->links() }}</div>
        </div>
    </div>

    {{-- ── RIGHT SIDEBAR ────────────────────────────────────────── --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Stats --}}
        <div class="card" style="padding:18px;">
            <div style="font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em; margin-bottom:14px;">{{ __('Application Stats') }}</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach([
                    ['label' => 'Total',       'value' => $appStats['total'],       'color' => 'var(--fg)'],
                    ['label' => 'Pending',      'value' => $appStats['pending'],     'color' => '#F59E0B'],
                    ['label' => 'Shortlisted',  'value' => $appStats['shortlisted'], 'color' => '#A855F7'],
                    ['label' => 'Accepted',     'value' => $appStats['accepted'],    'color' => '#22C55E'],
                ] as $stat)
                <div style="display:flex; align-items:center; justify-content:space-between; font-size:13px;">
                    <span style="color:var(--fg-3);">{{ $stat['label'] }}</span>
                    <span class="mono" style="font-weight:600; color:{{ $stat['color'] }};">{{ $stat['value'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Listing details --}}
        <div class="card" style="padding:18px;">
            <div style="font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em; margin-bottom:14px;">{{ __('Listing Details') }}</div>
            <div style="display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--fg-3);">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fg-4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ $listing->locationTypeLabel }}{{ $listing->location ? ' · ' . $listing->location : '' }}
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fg-4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    {{ $listing->employmentTypeLabel }}
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fg-4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    {{ $listing->salaryRange }}
                </div>
                @if($listing->closes_at)
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fg-4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ __('Closes') }} {{ $listing->closes_at->format('M d, Y') }}
                </div>
                @endif
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fg-4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    {{ __('Posted') }} {{ $listing->created_at->format('M d, Y') }}
                </div>
            </div>
        </div>

        {{-- Boost --}}
        @if($listing->status === 1)
        <div class="card" style="padding:18px;" x-data="{ days: 1, rate: {{ (float) gs()->boost_cost_job }}, max: {{ max(1, (int) gs()->boost_days_job) }} }">
            @if($listing->is_featured && $listing->featured_until?->isFuture())
            <div style="display:flex; align-items:center; gap:6px; font-size:13.5px; font-weight:600; color:#F59E0B; margin-bottom:4px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                {{ __('Featured') }}
            </div>
            <div style="font-size:12px; color:var(--fg-4); margin-bottom:14px;">{{ __('Until') }} {{ $listing->featured_until->format('M d, Y') }}</div>
            @else
            <div style="font-size:13.5px; font-weight:600; color:var(--fg); margin-bottom:4px;">{{ __('Boost This Listing') }}</div>
            @endif
            <div style="font-size:12px; color:var(--fg-4); margin-bottom:14px;">{{ formatCoins(gs()->boost_cost_job) }} {{ __('coins/day · up to') }} {{ gs()->boost_days_job }} {{ __('days') }}</div>

            @if($pendingBoost)
            <div style="padding:10px 12px; border-radius:8px; background:rgba(245,158,11,0.07); border:1px solid rgba(245,158,11,0.2); font-size:12.5px; color:#F59E0B; display:flex; align-items:center; gap:6px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ __('Boost request pending admin approval') }} ({{ $pendingBoost->days }} {{ __('day(s)') }} · {{ formatCoins($pendingBoost->coins_paid) }} {{ __('held') }})
            </div>
            @else
            <form method="POST" action="{{ route('user.jobs.listings.boost', $listing->id) }}" style="display:flex; flex-direction:column; gap:10px;">
                @csrf
                <div>
                    <label style="font-size:11.5px; color:var(--fg-4); display:block; margin-bottom:5px;">{{ __('Duration (days)') }}</label>
                    <input type="number" name="days" x-model.number="days"
                           min="1" :max="max" step="1"
                           style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box;">
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; font-size:12.5px;">
                    <span style="color:var(--fg-4);">{{ __('Total cost') }}:</span>
                    <span class="mono" style="color:#F59E0B; font-weight:600;" x-text="(days * rate).toFixed(0) + ' coins'"></span>
                </div>
                <button type="submit"
                        style="display:flex; align-items:center; justify-content:center; gap:7px; width:100%; padding:10px; border-radius:8px; background:rgba(245,158,11,0.08); color:#F59E0B; border:1px solid rgba(245,158,11,0.2); font-size:13px; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='rgba(245,158,11,0.15)'" onmouseout="this.style.background='rgba(245,158,11,0.08)'"
                        onclick="return confirm('Request boost for ' + document.querySelector('[name=days]').value + ' day(s)?')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    {{ __('Request Boost') }}
                </button>
            </form>
            @endif
        </div>
        @endif

        {{-- Actions --}}
        <div class="card" style="padding:18px; display:flex; flex-direction:column; gap:8px;">
            @if(in_array($listing->status, [0, 3]))
            <a href="{{ route('user.jobs.listings.edit', $listing->id) }}"
               style="display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:8px; background:rgba(47,84,235,0.08); color:var(--accent); text-decoration:none; font-size:13px; border:1px solid rgba(47,84,235,0.15); transition:background .15s;"
               onmouseover="this.style.background='rgba(47,84,235,0.16)'" onmouseout="this.style.background='rgba(47,84,235,0.08)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                {{ __('Edit Listing') }}
            </a>
            @endif
            @if($listing->status === 1)
            <form method="POST" action="{{ route('user.jobs.listings.close', $listing->id) }}">
                @csrf
                <button type="submit"
                        style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; border-radius:8px; background:rgba(245,158,11,0.07); color:#F59E0B; border:1px solid rgba(245,158,11,0.18); font-size:13px; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='rgba(245,158,11,0.14)'" onmouseout="this.style.background='rgba(245,158,11,0.07)'"
                        onclick="return confirm('{{ __('Close this listing?') }}')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ __('Close Listing') }}
                </button>
            </form>
            @endif
            <form method="POST" action="{{ route('user.jobs.listings.delete', $listing->id) }}">
                @csrf @method('DELETE')
                <button type="submit"
                        style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; border-radius:8px; background:rgba(239,68,68,0.07); color:#EF4444; border:1px solid rgba(239,68,68,0.18); font-size:13px; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='rgba(239,68,68,0.14)'" onmouseout="this.style.background='rgba(239,68,68,0.07)'"
                        onclick="return confirm('{{ __('Delete this listing permanently?') }}')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    {{ __('Delete Listing') }}
                </button>
            </form>
        </div>

    </div>
</div>

<style>
.listing-grid { grid-template-columns: 1fr 300px; }
@media (max-width: 1100px) { .listing-grid { grid-template-columns: 1fr !important; } }
.job-prose p { margin: 0 0 10px; }
.job-prose p:last-child { margin-bottom: 0; }
input:focus { border-color: var(--accent) !important; box-shadow: 0 0 0 3px rgba(47,84,235,0.12); }
</style>
@endsection
