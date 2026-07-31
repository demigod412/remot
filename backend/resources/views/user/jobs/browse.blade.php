@extends('user.layouts.app')
@section('title', 'Browse Jobs')
@section('page-title', 'Find Jobs')

@section('content')

{{-- Filter bar --}}
<form method="GET" action="{{ route('user.jobs.browse') }}"
      class="card" style="padding:16px 18px; margin-bottom:18px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
    <div style="flex:1; min-width:180px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Search</label>
        <div style="position:relative;">
            <i data-lucide="search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); width:14px; height:14px; color:var(--fg-4); pointer-events:none;"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Job title, keyword…"
                   style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 12px 8px 32px; color:var(--fg); font-size:13px; outline:none;">
        </div>
    </div>
    <div style="width:145px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Category</label>
        <select name="category" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none;">
            <option value="">All categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="width:125px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Work type</label>
        <select name="location_type" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none;">
            <option value="">Any</option>
            <option value="1" {{ request('location_type') == '1' ? 'selected' : '' }}>Remote</option>
            <option value="2" {{ request('location_type') == '2' ? 'selected' : '' }}>On-site</option>
            <option value="3" {{ request('location_type') == '3' ? 'selected' : '' }}>Hybrid</option>
        </select>
    </div>
    <div style="width:130px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Employment</label>
        <select name="employment_type" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none;">
            <option value="">Any</option>
            @foreach(['full_time'=>'Full-time','part_time'=>'Part-time','contract'=>'Contract','freelance'=>'Freelance'] as $val => $label)
            <option value="{{ $val }}" {{ request('employment_type') == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @if($skills->isNotEmpty())
    <div style="width:115px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Skill</label>
        <select name="skill" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none;">
            <option value="">Any skill</option>
            @foreach($skills as $skill)
            <option value="{{ $skill->id }}" {{ request('skill') == $skill->id ? 'selected' : '' }}>{{ $skill->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div style="display:flex; gap:8px;">
        <button type="submit" class="btn btn-primary" style="font-size:13px; padding:8px 18px;">Filter</button>
        @if(request()->hasAny(['search','category','location_type','employment_type','skill']))
        <a href="{{ route('user.jobs.browse') }}" class="btn" style="font-size:13px; padding:8px 14px;">Clear</a>
        @endif
    </div>
</form>

{{-- Result count --}}
<div style="font-size:12.5px; color:var(--fg-3); margin-bottom:14px;">
    <span class="mono" style="color:var(--fg); font-weight:600;">{{ $listings->total() }}</span>
    job{{ $listings->total() !== 1 ? 's' : '' }} found
</div>

{{-- Job cards --}}
<div style="display:flex; flex-direction:column; gap:10px;">
@forelse($listings as $listing)
<div class="card jobs-card" style="padding:18px; transition:border-color .15s;">
    <div style="display:flex; align-items:flex-start; gap:14px;">
        {{-- Thumbnail --}}
        @if($listing->cover_image)
        <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image) }}"
             alt="{{ $listing->title }}" style="width:52px; height:52px; border-radius:10px; object-fit:cover; flex-shrink:0;">
        @else
        <div style="width:52px; height:52px; border-radius:10px; background:rgba(47,84,235,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="briefcase" style="width:22px; height:22px; color:var(--accent); opacity:.5;"></i>
        </div>
        @endif

        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:5px; flex-wrap:wrap;">
                <h3 style="font-size:14.5px; font-weight:600; color:var(--fg); margin:0;">{{ $listing->title }}</h3>
                <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                    @if($appliedIds->has($listing->id))
                    <span style="font-size:11px; padding:2px 8px; border-radius:999px; background:rgba(34,197,94,0.1); color:#22C55E; border:1px solid rgba(34,197,94,0.2); font-weight:500;">Applied</span>
                    @endif
                    {{-- Bookmark toggle --}}
                    <div x-data="{ bm: {{ $bookmarkedIds->has($listing->id) ? 'true' : 'false' }} }">
                        <button type="button" @click="
                            bm = !bm;
                            fetch('{{ route('user.jobs.bookmark', $listing->id) }}', {
                                method:'POST',
                                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
                            });"
                            :title="bm ? '{{ __('Remove from saved') }}' : '{{ __('Save job') }}'"
                            style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); cursor:pointer; transition:all .15s;"
                            :style="bm ? 'border-color:var(--accent); background:rgba(47,84,235,0.08); color:var(--accent);' : 'color:var(--fg-3);'">
                            <svg width="14" height="14" viewBox="0 0 24 24" :fill="bm ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        </button>
                    </div>
                    <a href="{{ route('user.jobs.show', $listing->id) }}" class="btn" style="font-size:12px; padding:5px 14px;">View</a>
                </div>
            </div>

            <div style="font-size:12px; color:var(--fg-3); margin-bottom:10px; display:flex; align-items:center; gap:5px;">
                {{ $listing->employer->fullname ?? $listing->employer->username ?? 'Employer' }}
                @if($listing->employer->kyc_status === 1)
                <i data-lucide="shield-check" style="width:12px; height:12px; color:#22C55E;"></i>
                @endif
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px;">
                @if($listing->category)
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3); display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="tag" style="width:10px; height:10px;"></i> {{ $listing->category->name }}
                </span>
                @endif
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3); display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="map-pin" style="width:10px; height:10px;"></i> {{ $listing->locationTypeLabel }}{{ $listing->location ? ' · '.$listing->location : '' }}
                </span>
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3); display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="clock" style="width:10px; height:10px;"></i> {{ $listing->employmentTypeLabel }}
                </span>
                @if($listing->salaryRange)
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:rgba(47,84,235,0.08); color:var(--accent); display:inline-flex; align-items:center; gap:4px; font-weight:500;">
                    <i data-lucide="dollar-sign" style="width:10px; height:10px;"></i> {{ $listing->salaryRange }}
                </span>
                @endif
                @if($listing->closes_at)
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3); display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="calendar" style="width:10px; height:10px;"></i> Closes {{ $listing->closes_at->format('M d') }}
                </span>
                @endif
            </div>

            <p style="font-size:12.5px; color:var(--fg-3); margin:0; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                {{ Str::limit(strip_tags($listing->description), 160) }}
            </p>
        </div>
    </div>
</div>
@empty
<div class="card" style="padding:60px 24px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">🔍</div>
    <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:8px;">No jobs found</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;">Try adjusting your filters or check back later for new listings.</p>
    @if(request()->hasAny(['search','category','location_type','employment_type','skill']))
    <a href="{{ route('user.jobs.browse') }}" class="btn btn-primary" style="font-size:13px;">Clear filters</a>
    @endif
</div>
@endforelse
</div>

<div style="margin-top:20px;">{{ $listings->withQueryString()->links() }}</div>

<style>
.jobs-card:hover { border-color: rgba(47,84,235,0.25) !important; }
</style>
@endsection
