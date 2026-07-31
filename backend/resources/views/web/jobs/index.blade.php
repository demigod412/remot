@extends('web.layouts.app')

@section('title', __('Browse Jobs') . ' — ' . (gs()->site_name ?? config('app.name')))
@section('meta_description', 'Browse open job listings on ' . (gs()->site_name ?? config('app.name')) . '.')

@section('content')

<section style="padding:48px 24px 64px;">
    <div class="container">

        {{-- Header --}}
        <div style="margin-bottom:32px;">
            <h1 style="font-size:clamp(24px,4vw,36px);font-weight:900;margin-bottom:8px;color:var(--text);">{{ __('Browse Jobs') }}</h1>
            <p style="color:var(--muted);">{{ __('Discover job opportunities posted by companies and freelancers.') }}</p>
        </div>

        <div style="display:grid;grid-template-columns:260px 1fr;gap:32px;align-items:start;">

            {{-- ── Sidebar Filters ────────────────────────────────── --}}
            <aside>
                <form method="GET" action="{{ route('jobs.index') }}" id="filter-form">
                    <div class="card" style="display:flex;flex-direction:column;gap:20px;">

                        {{-- Search --}}
                        <div>
                            <label style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);display:block;margin-bottom:8px;">
                                {{ __('Search') }}
                            </label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="{{ __('Job title, keyword…') }}"
                                   style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;
                                          padding:10px 14px;color:var(--text);font-size:14px;outline:none;">
                        </div>

                        {{-- Category --}}
                        <div>
                            <label style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);display:block;margin-bottom:8px;">
                                {{ __('Category') }}
                            </label>
                            <select name="category"
                                    style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;
                                           padding:10px 14px;color:var(--text);font-size:14px;outline:none;">
                                <option value="">{{ __('All Categories') }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Work Type --}}
                        <div>
                            <label style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);display:block;margin-bottom:8px;">
                                {{ __('Work Type') }}
                            </label>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                @foreach([''=>'Any','1'=>'Remote','2'=>'On-site','3'=>'Hybrid'] as $val => $label)
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:var(--text);">
                                    <input type="radio" name="location_type" value="{{ $val }}"
                                           {{ request('location_type', '') === $val ? 'checked' : '' }}>
                                    {{ __($label) }}
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Employment Type --}}
                        <div>
                            <label style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);display:block;margin-bottom:8px;">
                                {{ __('Employment') }}
                            </label>
                            <select name="employment_type"
                                    style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;
                                           padding:10px 14px;color:var(--text);font-size:14px;outline:none;">
                                <option value="">{{ __('Any') }}</option>
                                @foreach(['full_time'=>'Full-time','part_time'=>'Part-time','contract'=>'Contract','freelance'=>'Freelance'] as $val => $label)
                                <option value="{{ $val }}" {{ request('employment_type') == $val ? 'selected' : '' }}>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($skills->isNotEmpty())
                        {{-- Skill --}}
                        <div>
                            <label style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);display:block;margin-bottom:8px;">
                                {{ __('Skill') }}
                            </label>
                            <select name="skill"
                                    style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;
                                           padding:10px 14px;color:var(--text);font-size:14px;outline:none;">
                                <option value="">{{ __('Any Skill') }}</option>
                                @foreach($skills as $skill)
                                <option value="{{ $skill->id }}" {{ request('skill') == $skill->id ? 'selected' : '' }}>{{ $skill->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            {{ __('Apply Filters') }}
                        </button>

                        @if(request()->hasAny(['search','category','location_type','employment_type','skill']))
                        <a href="{{ route('jobs.index') }}" style="display:block;text-align:center;font-size:13px;color:var(--muted);">
                            {{ __('Clear all filters') }}
                        </a>
                        @endif
                    </div>
                </form>
            </aside>

            {{-- ── Job Listings ────────────────────────────────────── --}}
            <div>
                <div style="font-size:13px;color:var(--muted);margin-bottom:16px;">
                    {{ $listings->total() }} {{ __('job(s) found') }}
                </div>

                <div style="display:flex;flex-direction:column;gap:16px;">
                @forelse($listings as $listing)
                @php
                    $locTypes = [1=>'Remote',2=>'On-site',3=>'Hybrid'];
                    $empTypes = ['full_time'=>'Full-time','part_time'=>'Part-time','contract'=>'Contract','freelance'=>'Freelance'];
                @endphp
                <div class="card" style="display:flex;align-items:flex-start;gap:20px;padding:24px;transition:box-shadow .15s;position:relative;">
                    @if($listing->is_featured)
                    <div style="position:absolute;top:16px;right:16px;background:rgba(234,179,8,0.12);color:#ca8a04;
                                border:1px solid rgba(234,179,8,0.25);border-radius:20px;padding:3px 10px;
                                font-size:11px;font-weight:700;display:flex;align-items:center;gap:4px;">
                        ★ Featured
                    </div>
                    @endif

                    {{-- Logo / Icon --}}
                    @if($listing->cover_image)
                    <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image) }}"
                         alt="{{ $listing->title }}"
                         style="width:56px;height:56px;border-radius:12px;object-fit:cover;flex-shrink:0;">
                    @else
                    <div style="width:56px;height:56px;border-radius:12px;background:var(--purple-bg);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.5">
                            <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                        </svg>
                    </div>
                    @endif

                    {{-- Content --}}
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:17px;font-weight:700;color:var(--text);margin-bottom:4px;line-height:1.3;">
                            {{ $listing->title }}
                        </h3>

                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;align-items:center;">
                            @if($listing->employer)
                            <span style="font-size:13px;color:var(--muted);">{{ $listing->employer->fullname }}</span>
                            <span style="color:var(--border);">·</span>
                            @endif
                            @if($listing->location && $listing->location_type != 1)
                            <span style="font-size:13px;color:var(--muted);">{{ $listing->location }}</span>
                            <span style="color:var(--border);">·</span>
                            @endif
                            @if($listing->location_type)
                            <span style="font-size:12px;background:var(--purple-bg);color:var(--purple);
                                         border-radius:20px;padding:3px 10px;font-weight:600;">
                                {{ $locTypes[$listing->location_type] ?? '' }}
                            </span>
                            @endif
                            @if($listing->employment_type)
                            <span style="font-size:12px;background:rgba(15,23,42,0.05);color:var(--muted);
                                         border-radius:20px;padding:3px 10px;font-weight:600;">
                                {{ $empTypes[$listing->employment_type] ?? '' }}
                            </span>
                            @endif
                        </div>

                        <p style="font-size:14px;color:var(--muted);line-height:1.6;margin-bottom:14px;
                                  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            {{ strip_tags($listing->description) }}
                        </p>

                        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
                            <div style="display:flex;align-items:center;gap:16px;">
                                @if($listing->salary_visible && ($listing->salary_min || $listing->salary_max))
                                <span style="font-size:14px;font-weight:700;color:var(--purple);">
                                    {{ $listing->salary_currency }}
                                    @if($listing->salary_min && $listing->salary_max)
                                        {{ number_format($listing->salary_min) }} – {{ number_format($listing->salary_max) }}
                                    @elseif($listing->salary_min)
                                        {{ number_format($listing->salary_min) }}+
                                    @else
                                        up to {{ number_format($listing->salary_max) }}
                                    @endif
                                </span>
                                @endif
                                @if($listing->closes_at)
                                <span style="font-size:12px;color:var(--muted);">
                                    Closes {{ $listing->closes_at->diffForHumans() }}
                                </span>
                                @endif
                                <span style="font-size:12px;color:var(--muted);">
                                    {{ $listing->application_count ?? 0 }} applicant(s)
                                </span>
                            </div>

                            @auth('web')
                                <a href="{{ route('user.jobs.show', $listing->id) }}"
                                   class="btn btn-primary btn-sm">
                                    {{ __('View & Apply') }}
                                </a>
                            @else
                                <a href="{{ route('user.login') }}"
                                   class="btn btn-primary btn-sm">
                                    {{ __('Login to Apply') }}
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
                @empty
                <div class="card" style="padding:64px;text-align:center;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"
                         style="margin:0 auto 16px;">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        <path d="M8 11h6m-3-3v6"/>
                    </svg>
                    <p style="color:var(--muted);font-size:15px;">{{ __('No jobs match your filters.') }}</p>
                    <a href="{{ route('jobs.index') }}" style="margin-top:12px;display:inline-block;color:var(--purple);font-size:13px;">
                        {{ __('Clear filters') }}
                    </a>
                </div>
                @endforelse
                </div>

                {{-- Pagination --}}
                @if($listings->hasPages())
                <div style="margin-top:32px;">
                    {{ $listings->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection
