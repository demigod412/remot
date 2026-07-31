@extends('user.layouts.app')
@section('title', 'My Works')
@section('page-title', 'My Posted Works')

@section('content')

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
        @foreach(['' => 'All', '0' => 'Holding', '1' => 'Active', '2' => 'Finished'] as $val => $label)
        <a href="{{ route('user.works.index', $val !== '' ? ['status' => $val] : []) }}"
           style="font-size:12px; font-weight:500; padding:6px 12px; border-radius:7px; text-decoration:none; transition:all .14s;
                  {{ request('status') == $val ? 'background:var(--accent); color:white; border:1px solid var(--accent);' : 'background:var(--surface); color:var(--fg-2); border:1px solid var(--border);' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
    <a href="{{ route('user.works.create') }}" class="btn btn-primary" style="font-size:13px; padding:7px 14px; text-decoration:none;">
        <i data-lucide="plus" style="width:14px; height:14px;"></i> Post New Work
    </a>
</div>

@forelse($works as $work)
@php
    $approvalStyle = match($work->approval_status) {
        0 => 'background:rgba(245,158,11,0.1); color:#F59E0B;',
        1 => 'background:rgba(34,197,94,0.1); color:#22C55E;',
        2 => 'background:rgba(239,68,68,0.1); color:var(--danger);',
        default => 'background:var(--surface-2); color:var(--fg-3);',
    };
    $approvalLabel = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected'][$work->approval_status] ?? '';
    $statusLabel   = [0 => 'Holding', 1 => 'Active', 2 => 'Finished'][$work->work_status] ?? '';
    $statusColor   = match($work->work_status) { 0 => 'var(--fg-3)', 1 => 'var(--accent)', 2 => '#60A5FA', default => 'var(--fg-3)' };
    $filled        = $work->submissions->where('status', 2)->count();
    $pct           = $work->worker_slots > 0 ? round($filled / $work->worker_slots * 100) : 0;
@endphp
<div class="card" style="padding:16px 18px; margin-bottom:10px;">
    <div style="display:flex; align-items:flex-start; gap:14px;">

        @if($work->cover_image)
        <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $work->cover_image) }}"
             alt="{{ $work->title }}" style="width:58px; height:58px; border-radius:10px; object-fit:cover; flex-shrink:0;">
        @else
        <div style="width:58px; height:58px; border-radius:10px; background:var(--accent-soft); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="briefcase" style="width:22px; height:22px; color:var(--accent);"></i>
        </div>
        @endif

        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:8px;">
                <div>
                    <div style="font-size:14px; font-weight:600; color:var(--fg);">{{ $work->title }}</div>
                    <div style="font-size:12px; color:var(--fg-3); margin-top:2px;">{{ $work->category->name ?? '—' }}</div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                    <span style="font-size:11px; font-weight:500; padding:3px 8px; border-radius:20px; {{ $approvalStyle }}">
                        {{ $approvalLabel }}
                    </span>
                    <span style="font-size:11px; font-weight:500; color:{{ $statusColor }};">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            <div style="margin-bottom:10px;">
                <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--fg-3); margin-bottom:4px;">
                    <span>{{ $filled }}/{{ $work->worker_slots }} slots filled</span>
                    <span class="mono">{{ coinSymbol() }}{{ number_format($work->coins_per_worker) }} each</span>
                </div>
                <div style="height:4px; border-radius:999px; background:var(--surface-3); overflow:hidden;">
                    <div style="height:100%; border-radius:999px; background:var(--accent); width:{{ $pct }}%; transition:width .3s;"></div>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <a href="{{ route('user.works.show', $work->id) }}"
                   style="font-size:12px; color:var(--accent); text-decoration:none; display:inline-flex; align-items:center; gap:4px;"
                   onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                    <i data-lucide="eye" style="width:12px; height:12px;"></i> View Details
                </a>
                <a href="{{ route('user.works.submissions', $work->id) }}"
                   style="font-size:12px; color:var(--fg-3); text-decoration:none; display:inline-flex; align-items:center; gap:4px;"
                   onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">
                    <i data-lucide="file-check" style="width:12px; height:12px;"></i>
                    {{ $work->submissions->count() }} submissions
                </a>
                <span style="font-size:11px; color:var(--fg-4);">Posted {{ $work->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>
</div>
@empty
<div class="card" style="padding:56px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">💼</div>
    <div style="font-size:15px; font-weight:500; color:var(--fg); margin-bottom:6px;">No works posted yet</div>
    <div style="font-size:13px; color:var(--fg-3); margin-bottom:20px;">Post a work and let others complete it for you</div>
    <a href="{{ route('user.works.create') }}" class="btn btn-primary" style="font-size:13px; text-decoration:none;">
        <i data-lucide="plus" style="width:14px; height:14px;"></i> Post Your First Work
    </a>
</div>
@endforelse

{{ $works->withQueryString()->links() }}
@endsection
