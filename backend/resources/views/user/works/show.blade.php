@extends('user.layouts.app')
@section('title', $work->title)
@section('page-title', __('Work Details'))

@section('content')

{{-- Breadcrumb --}}
<div style="font-size:12.5px; color:var(--fg-3); margin-bottom:24px; display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
    <a href="{{ route('user.works.index') }}" style="color:var(--fg-3); text-decoration:none; transition:color .15s;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">{{ __('My Works') }}</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-2);">{{ Str::limit($work->title, 50) }}</span>
</div>

@php
    $approvalBg    = [0 => 'rgba(245,158,11,0.1)', 1 => 'rgba(34,197,94,0.1)', 2 => 'rgba(239,68,68,0.1)'];
    $approvalColor = [0 => '#F59E0B', 1 => '#22C55E', 2 => '#EF4444'];
    $approvalLabel = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected'];
    $subBg    = [0 => 'rgba(100,116,139,0.1)', 1 => 'rgba(245,158,11,0.1)', 2 => 'rgba(34,197,94,0.1)', 3 => 'rgba(239,68,68,0.1)'];
    $subColor = [0 => 'var(--fg-3)', 1 => '#F59E0B', 2 => '#22C55E', 3 => '#EF4444'];
    $subLabel = [0 => 'Applied', 1 => 'Under Review', 2 => 'Approved', 3 => 'Rejected'];
    $filled   = $submissionStats['approved'];
    $pct      = $work->worker_slots > 0 ? round($filled / $work->worker_slots * 100) : 0;
@endphp

<div style="display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start;" class="work-detail-grid">

    {{-- ── LEFT ────────────────────────────────────────────────────── --}}
    <div style="display:flex; flex-direction:column; gap:20px;">

        {{-- Main info --}}
        <div class="card" style="padding:0; overflow:hidden;">
            @if($work->cover_image)
            <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $work->cover_image) }}"
                 alt="{{ $work->title }}" style="width:100%; height:180px; object-fit:cover; display:block;">
            @endif
            <div style="padding:22px;">
                <div style="display:flex; align-items:flex-start; gap:12px; margin-bottom:14px; flex-wrap:wrap;">
                    <h1 style="font-size:20px; font-weight:700; color:var(--fg); margin:0; flex:1; line-height:1.25;">{{ $work->title }}</h1>
                    <span style="font-size:11px; font-weight:600; padding:3px 10px; border-radius:999px; flex-shrink:0;
                                 background:{{ $approvalBg[$work->approval_status] ?? 'var(--surface-2)' }};
                                 color:{{ $approvalColor[$work->approval_status] ?? 'var(--fg-3)' }};">
                        {{ $approvalLabel[$work->approval_status] ?? '' }}
                    </span>
                </div>

                @if($work->rejection_reason)
                <div style="padding:12px 14px; border-radius:8px; background:rgba(239,68,68,0.07); border:1px solid rgba(239,68,68,0.18); font-size:13px; color:#EF4444; margin-bottom:16px;">
                    <strong>{{ __('Rejection reason') }}:</strong> {{ $work->rejection_reason }}
                </div>
                @endif

                <div class="work-prose" style="font-size:14px; color:var(--fg-2); line-height:1.7;">
                    {!! richBody($work->description) !!}
                </div>
            </div>
        </div>

        {{-- Submissions --}}
        <div class="card" style="padding:0; overflow:hidden;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border);">
                <div style="font-size:13.5px; font-weight:600; color:var(--fg);">{{ __('Recent Submissions') }}</div>
                <a href="{{ route('user.works.submissions', $work->id) }}"
                   style="font-size:12.5px; color:var(--accent); text-decoration:none;"
                   onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                    {{ __('View all') }} ({{ $work->submissions->count() }}) →
                </a>
            </div>

            @forelse($work->submissions->take(5) as $sub)
            <div style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid var(--border);">
                <div style="width:32px; height:32px; border-radius:50%; background:rgba(47,84,235,0.1); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:var(--accent); flex-shrink:0;">
                    {{ strtoupper(substr($sub->worker->firstname ?? 'U', 0, 1)) }}
                </div>
                <div style="flex:1; font-size:13.5px; color:var(--fg-2);">{{ $sub->worker->fullname ?? 'Unknown' }}</div>
                <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:999px;
                             background:{{ $subBg[$sub->status] ?? 'var(--surface-2)' }};
                             color:{{ $subColor[$sub->status] ?? 'var(--fg-3)' }};">
                    {{ $subLabel[$sub->status] ?? '' }}
                </span>
                <a href="{{ route('user.works.submissions.review', [$work->id, $sub->id]) }}"
                   style="font-size:12px; padding:5px 12px; border-radius:7px; background:rgba(47,84,235,0.08); color:var(--accent); text-decoration:none; border:1px solid rgba(47,84,235,0.15); white-space:nowrap; transition:background .15s;"
                   onmouseover="this.style.background='rgba(47,84,235,0.16)'" onmouseout="this.style.background='rgba(47,84,235,0.08)'">
                    {{ __('Review') }}
                </a>
            </div>
            @empty
            <div style="padding:40px 18px; text-align:center; color:var(--fg-3); font-size:13.5px;">
                {{ __('No submissions yet.') }}
            </div>
            @endforelse
        </div>

    </div>

    {{-- ── SIDEBAR ──────────────────────────────────────────────────── --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Stats --}}
        <div class="card" style="padding:18px;">
            <div style="font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em; margin-bottom:14px;">{{ __('Submission Stats') }}</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach([
                    ['Total',         $submissionStats['total'],    'var(--fg)'],
                    ['Pending Review',$submissionStats['pending'],   '#F59E0B'],
                    ['Approved',      $submissionStats['approved'],  '#22C55E'],
                    ['Rejected',      $submissionStats['rejected'],  '#EF4444'],
                ] as [$label, $val, $color])
                <div style="display:flex; align-items:center; justify-content:space-between; font-size:13px;">
                    <span style="color:var(--fg-3);">{{ $label }}</span>
                    <span class="mono" style="font-weight:600; color:{{ $color }};">{{ $val }}</span>
                </div>
                @endforeach
            </div>
            <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border);">
                <div style="display:flex; justify-content:space-between; font-size:11.5px; color:var(--fg-3); margin-bottom:6px;">
                    <span>{{ __('Slots') }}: {{ $filled }}/{{ $work->worker_slots }}</span>
                    <span>{{ $pct }}%</span>
                </div>
                <div style="height:5px; border-radius:999px; background:var(--surface-3); overflow:hidden;">
                    <div style="height:100%; border-radius:999px; background:var(--accent); width:{{ $pct }}%; transition:width .3s;"></div>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="card" style="padding:18px;">
            <div style="font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em; margin-bottom:14px;">{{ __('Work Details') }}</div>
            <div style="display:flex; flex-direction:column; gap:10px; font-size:13px;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--fg-3);">{{ __('Coins/Worker') }}</span>
                    <span class="mono" style="font-weight:600; color:#E6C400;">{{ formatCoins($work->coins_per_worker) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--fg-3);">{{ __('Total Budget') }}</span>
                    <span class="mono" style="color:var(--fg-2);">{{ formatCoins($work->total_coins) }}</span>
                </div>
                @if($work->avg_minutes)
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--fg-3);">{{ __('Est. Time') }}</span>
                    <span style="color:var(--fg-2);">{{ $work->avg_minutes }} min</span>
                </div>
                @endif
                @if($work->expires_at)
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--fg-3);">{{ __('Expires') }}</span>
                    <span style="color:var(--fg-2);">{{ $work->expires_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Boost --}}
        @if($work->approval_status === 1)
        <div class="card" style="padding:18px;" x-data="{ days: 1, rate: {{ (float) gs()->boost_cost_work }}, max: {{ max(1, (int) gs()->boost_days_work) }} }">
            @if($work->is_featured && $work->featured_until?->isFuture())
            <div style="display:flex; align-items:center; gap:6px; font-size:13.5px; font-weight:600; color:#F59E0B; margin-bottom:4px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                {{ __('Featured') }}
            </div>
            <div style="font-size:12px; color:var(--fg-4); margin-bottom:14px;">{{ __('Until') }} {{ $work->featured_until->format('M d, Y') }}</div>
            @else
            <div style="font-size:13.5px; font-weight:600; color:var(--fg); margin-bottom:4px;">{{ __('Boost This Work') }}</div>
            @endif
            <div style="font-size:12px; color:var(--fg-4); margin-bottom:14px;">{{ formatCoins(gs()->boost_cost_work) }} {{ __('coins/day · up to') }} {{ gs()->boost_days_work }} {{ __('days') }}</div>

            @if($pendingBoost)
            <div style="padding:10px 12px; border-radius:8px; background:rgba(245,158,11,0.07); border:1px solid rgba(245,158,11,0.2); font-size:12.5px; color:#F59E0B; display:flex; align-items:center; gap:6px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ __('Boost pending approval') }} ({{ $pendingBoost->days }} {{ __('day(s)') }} · {{ formatCoins($pendingBoost->coins_paid) }} {{ __('held') }})
            </div>
            @else
            <form method="POST" action="{{ route('user.works.boost', $work->id) }}" style="display:flex; flex-direction:column; gap:10px;">
                @csrf
                <div>
                    <label style="font-size:11.5px; color:var(--fg-4); display:block; margin-bottom:5px;">{{ __('Duration (days)') }}</label>
                    <input type="number" name="days" x-model.number="days" min="1" :max="max" step="1"
                           style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box;">
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; font-size:12.5px;">
                    <span style="color:var(--fg-4);">{{ __('Total cost') }}:</span>
                    <span class="mono" style="color:#F59E0B; font-weight:600;" x-text="(days * rate).toFixed(0) + ' coins'"></span>
                </div>
                <button type="submit"
                        style="display:flex; align-items:center; justify-content:center; gap:7px; width:100%; padding:10px; border-radius:8px; background:rgba(245,158,11,0.08); color:#F59E0B; border:1px solid rgba(245,158,11,0.2); font-size:13px; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='rgba(245,158,11,0.15)'" onmouseout="this.style.background='rgba(245,158,11,0.08)'"
                        onclick="return confirm('{{ __('Request boost?') }} ' + (days * rate).toFixed(0) + ' {{ __('coins will be held.') }}')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    {{ __('Request Boost') }}
                </button>
            </form>
            @endif
        </div>
        @elseif($work->approval_status === 0)
        <div class="card" style="padding:16px; display:flex; align-items:flex-start; gap:10px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>
                <div style="font-size:13px; font-weight:600; color:#F59E0B; margin-bottom:3px;">{{ __('Boost unavailable') }}</div>
                <div style="font-size:12.5px; color:var(--fg-3);">{{ __('Your work must be approved by admin before you can request a boost.') }}</div>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        @if($work->approval_status !== 1 || $work->work_status === 0)
        <div class="card" style="padding:18px; display:flex; flex-direction:column; gap:8px;">
            <div style="font-size:11px; color:var(--fg-4); font-weight:500; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">{{ __('Actions') }}</div>
            <form method="POST" action="{{ route('user.works.delete', $work->id) }}"
                  onsubmit="return confirm('{{ __('Delete this work? Remaining budget will be refunded.') }}')">
                @csrf @method('DELETE')
                <button type="submit"
                        style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; border-radius:8px; background:rgba(239,68,68,0.07); color:#EF4444; border:1px solid rgba(239,68,68,0.18); font-size:13px; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='rgba(239,68,68,0.14)'" onmouseout="this.style.background='rgba(239,68,68,0.07)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    {{ __('Delete Work') }}
                </button>
            </form>
        </div>
        @endif

    </div>
</div>

<style>
.work-detail-grid { grid-template-columns: 1fr 300px; }
@media (max-width: 1100px) { .work-detail-grid { grid-template-columns: 1fr !important; } }
.work-prose p { margin: 0 0 10px; }
.work-prose p:last-child { margin-bottom: 0; }
input:focus { border-color: var(--accent) !important; box-shadow: 0 0 0 3px rgba(47,84,235,0.12); }
</style>
@endsection
