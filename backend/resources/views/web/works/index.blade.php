@extends('web.layouts.app')

@section('title', __('Find work') . ' — ' . (gs()->site_name ?? config('app.name')))

@section('content')

<div x-data="{ tab: '{{ request('tab', 'instant') }}' }" x-init="$watch('tab', v => history.replaceState(null, '', '?tab=' + v))">

{{-- ── PAGE HEADER ─────────────────────────────────────────────── --}}
<div style="border-bottom:1px solid var(--border); background:#fff; padding:40px 40px 0;">
    <div style="max-width:1280px; margin:0 auto;">
        <h1 class="fw-h1" style="font-size:40px; font-weight:600; margin:0 0 8px; letter-spacing:-1px; color:var(--text);">{{ __('Find work') }}</h1>
        <p class="fw-sub" style="font-size:14.5px; color:var(--muted); margin:0 0 28px;">
            <span class="fw-count">{{ number_format($totalWorks + $totalJobs) }}</span> {{ __('active jobs · available today') }}
        </p>

        {{-- Tabs --}}
        <div class="fw-tabs" style="display:flex; gap:24px; border-bottom:1px solid var(--border); margin-bottom:-1px;">
            <button @click="tab='instant'"
                    :style="tab==='instant' ? 'color:var(--text);font-weight:600;border-bottom:2px solid var(--accent);' : 'color:var(--muted);border-bottom:2px solid transparent;'"
                    style="padding:12px 18px; border:none; background:transparent; cursor:pointer; font-size:13.5px; display:flex; align-items:center; gap:8px; font-family:inherit; margin-bottom:-1px; transition:color .18s;">
                <span style="display:inline-flex;align-items:center;gap:7px;">
                    <span class="fw-live-dot"></span>⚡ {{ __('Instant Jobs') }}
                </span>
                <span class="mono" style="font-size:11px; color:#A1A1AA; margin-left:2px;">{{ number_format($totalWorks) }}</span>
            </button>
            <button @click="tab='hiring'"
                    :style="tab==='hiring' ? 'color:var(--text);font-weight:600;border-bottom:2px solid var(--accent);' : 'color:var(--muted);border-bottom:2px solid transparent;'"
                    style="padding:12px 18px; border:none; background:transparent; cursor:pointer; font-size:13.5px; display:flex; align-items:center; gap:8px; font-family:inherit; margin-bottom:-1px; transition:color .18s;">
                💼 {{ __('Hiring Jobs') }}
                <span class="mono" style="font-size:11px; color:#A1A1AA; margin-left:2px;">{{ number_format($totalJobs) }}</span>
            </button>
            <button @click="tab='contract'"
                    :style="tab==='contract' ? 'color:var(--text);font-weight:600;border-bottom:2px solid var(--accent);' : 'color:var(--muted);border-bottom:2px solid transparent;'"
                    style="padding:12px 18px; border:none; background:transparent; cursor:pointer; font-size:13.5px; display:flex; align-items:center; gap:8px; font-family:inherit; margin-bottom:-1px; transition:color .18s;">
                📄 {{ __('Contracts') }}
            </button>
        </div>
    </div>
</div>

{{-- ── MAIN CONTENT ────────────────────────────────────────────── --}}
<div style="max-width:1280px; margin:0 auto; padding:32px 40px 80px; display:grid; grid-template-columns:240px 1fr; gap:32px; align-items:start;" class="listings-grid">

    {{-- ── SIDEBAR FILTERS ──────────────────────────────────────── --}}
    <aside class="fw-sidebar" style="font-size:13px; position:sticky; top:80px;">
        <form method="GET" action="{{ route('works.index') }}" id="filter-form">
            <input type="hidden" name="tab" :value="tab">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
                <svg width="15" height="15" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M2 4h14M4 9h10M7 14h4"/></svg>
                <span style="font-weight:600; font-size:13px; color:var(--text);">{{ __('Filters') }}</span>
                <a href="{{ route('works.index') }}" style="margin-left:auto; color:var(--accent); font-size:12px; text-decoration:none;">{{ __('Clear') }}</a>
            </div>

            {{-- Search --}}
            <div style="margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:10px;">{{ __('Search') }}</div>
                <div style="position:relative;">
                    <span style="position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--muted);">
                        <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="8" cy="8" r="5"/><path d="M13 13l3 3"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search tasks…') }}" style="padding-left:34px;">
                </div>
            </div>

            {{-- Category --}}
            <div style="margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:10px;">{{ __('Category') }}</div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($categories->take(7) as $cat)
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:var(--muted); font-size:13px;">
                        <span style="width:14px; height:14px; border:1.5px solid {{ request('category') == $cat->id ? 'var(--accent)' : 'var(--border-strong)' }}; border-radius:3px; background:{{ request('category') == $cat->id ? 'var(--accent)' : 'transparent' }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            @if(request('category') == $cat->id)
                            <svg width="9" height="9" viewBox="0 0 9 9"><path d="M1 4.5L3.5 7 8 2" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round"/></svg>
                            @endif
                        </span>
                        <input type="radio" name="category" value="{{ $cat->id }}" style="display:none;" {{ request('category') == $cat->id ? 'checked' : '' }} onchange="this.closest('form').submit()">
                        {{ $cat->name }}
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Sort --}}
            <div style="margin-bottom:20px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:10px;">{{ __('Sort by') }}</div>
                <select name="sort" onchange="this.closest('form').submit()" style="font-size:13px;">
                    <option value="latest" {{ request('sort','latest')==='latest' ? 'selected':'' }}>{{ __('Latest') }}</option>
                    <option value="coins_high" {{ request('sort')==='coins_high' ? 'selected':'' }}>{{ __('Highest reward') }}</option>
                    <option value="coins_low" {{ request('sort')==='coins_low' ? 'selected':'' }}>{{ __('Lowest reward') }}</option>
                    <option value="slots" {{ request('sort')==='slots' ? 'selected':'' }}>{{ __('Most spots') }}</option>
                </select>
            </div>
        </form>
    </aside>

    {{-- ── LISTINGS ─────────────────────────────────────────────── --}}
    <div>

        {{-- Instant Jobs Tab --}}
        <div x-show="tab==='instant'" x-cloak
             x-transition:enter="fw-tab-enter"
             x-transition:enter-start="fw-tab-enter-from"
             x-transition:enter-end="fw-tab-enter-to">
            @if($works->isEmpty())
            <div class="fw-empty" style="text-align:center; padding:80px 24px; color:var(--muted);">
                <div style="font-size:32px; margin-bottom:12px;">🔍</div>
                <div style="font-size:15px; font-weight:500; color:var(--text); margin-bottom:8px;">{{ __('No tasks found') }}</div>
                <p style="font-size:13px;">{{ __('Try adjusting your filters or search term.') }}</p>
                <a href="{{ route('works.index') }}" class="btn btn-secondary btn-sm" style="margin-top:16px;">{{ __('Clear filters') }}</a>
            </div>
            @else
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($works as $i => $work)
                <a href="{{ route('works.show', $work->slug) }}" class="fw-card" style="text-decoration:none;display:block;--i:{{ $i }}">
                    <div class="card fw-card-inner" style="padding:14px 18px; display:flex; align-items:center; gap:16px;">
                        <div style="width:32px; height:32px; border-radius:8px; background:rgba(255,122,89,0.14); color:#FF7A59; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:14px;">⚡</div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:14px; font-weight:500; color:var(--text); margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $work->title }}</div>
                            <div style="display:flex; gap:10px; font-size:11.5px; color:var(--muted); align-items:center; flex-wrap:wrap;">
                                <span>{{ $work->category?->name }}</span>
                                @if($work->time_limit)
                                <span style="color:#A1A1AA;">·</span>
                                <span>~{{ $work->time_limit }} {{ __('min') }}</span>
                                @endif
                                <span style="color:#A1A1AA;">·</span>
                                <span>{{ $work->slots_remaining }} {{ __('spots left') }}</span>
                                @if($work->expires_at && $work->expires_at->isFuture() && $work->expires_at->diffInHours(now()) <= 72)
                                <span style="color:#A1A1AA;">·</span>
                                <span style="color:{{ $work->expires_at->diffInHours(now()) <= 6 ? 'var(--red)' : 'var(--yellow)' }}; font-weight:600;">
                                    ⏳ <span class="work-countdown" data-expires="{{ $work->expires_at->timestamp }}">{{ $work->expires_at->diffForHumans() }}</span>
                                </span>
                                @endif
                            </div>
                        </div>
                        <span class="mono" style="font-size:14px; font-weight:600; color:#F5D547; flex-shrink:0;">{{ coinSymbol() }}{{ $work->coins_per_worker }}</span>
                        <span class="btn btn-sm fw-start-btn" style="padding:5px 12px; font-size:12px; flex-shrink:0; background:var(--accent); border-color:var(--accent); color:white;">{{ __('Start') }}</span>
                    </div>
                </a>
                @endforeach
            </div>
            @if($works->hasPages())
            @php
                $curW  = $works->currentPage();
                $lastW = $works->lastPage();
                $sW    = max(1, $curW - 2);
                $eW    = min($lastW, $curW + 2);
            @endphp
            <div class="fw-pagination" style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:24px;flex-wrap:wrap;">
                @if($works->onFirstPage())
                <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);opacity:.4;cursor:not-allowed;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14L6 9l5-5"/></svg>
                </span>
                @else
                <a href="{{ $works->previousPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14L6 9l5-5"/></svg>
                </a>
                @endif
                @if($sW > 1)
                <a href="{{ $works->url(1) }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">1</a>
                @if($sW > 2)<span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;color:var(--muted);font-size:13px;">···</span>@endif
                @endif
                @for($p = $sW; $p <= $eW; $p++)
                @if($p === $curW)
                <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--accent);background:var(--accent);color:#fff;font-size:13px;font-weight:600;">{{ $p }}</span>
                @else
                <a href="{{ $works->url($p) }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">{{ $p }}</a>
                @endif
                @endfor
                @if($eW < $lastW)
                @if($eW < $lastW - 1)<span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;color:var(--muted);font-size:13px;">···</span>@endif
                <a href="{{ $works->url($lastW) }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">{{ $lastW }}</a>
                @endif
                @if($works->hasMorePages())
                <a href="{{ $works->nextPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 14l5-5-5-5"/></svg>
                </a>
                @else
                <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);opacity:.4;cursor:not-allowed;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 14l5-5-5-5"/></svg>
                </span>
                @endif
            </div>
            @endif
            @endif
        </div>

        {{-- Hiring Jobs Tab --}}
        <div x-show="tab==='hiring'" x-cloak
             x-transition:enter="fw-tab-enter"
             x-transition:enter-start="fw-tab-enter-from"
             x-transition:enter-end="fw-tab-enter-to">
            @if($jobs->isEmpty())
            <div class="fw-empty" style="text-align:center; padding:80px 24px; color:var(--muted);">
                <div style="font-size:32px; margin-bottom:12px;">💼</div>
                <div style="font-size:15px; font-weight:500; color:var(--text); margin-bottom:8px;">{{ __('No hiring jobs yet') }}</div>
                <p style="font-size:13px;">{{ __('Check back soon or post your own listing.') }}</p>
            </div>
            @else
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($jobs as $i => $job)
                <a href="{{ route('jobs.show', $job->slug) }}" class="fw-card" style="text-decoration:none;display:block;--i:{{ $i }}">
                    <div class="card fw-card-inner" style="padding:20px; display:flex; align-items:center; gap:20px;">
                        <div style="width:48px; height:48px; border-radius:10px; background:rgba(96,165,250,0.12); color:#60A5FA; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">💼</div>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                                <span class="chip chip-hiring" style="font-size:10px; text-transform:uppercase;">{{ __('Hiring') }}</span>
                                @if($job->employer)
                                <span style="font-size:12px; color:var(--muted);">{{ $job->employer->name ?? $job->employer->username }}</span>
                                @endif
                                @if($job->location)
                                <span style="color:#A1A1AA; font-size:11px;">·</span>
                                <span style="font-size:12px; color:var(--muted);">{{ $job->location }}</span>
                                @endif
                            </div>
                            <div style="font-size:15px; font-weight:500; color:var(--text); margin-bottom:8px;">{{ $job->title }}</div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                @if($job->employment_type)
                                <span class="chip" style="font-size:11px;">{{ $job->employment_type }}</span>
                                @endif
                                @if($job->category)
                                <span class="chip" style="font-size:11px;">{{ $job->category->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div style="text-align:right; flex-shrink:0;">
                            @if($job->salary_min || $job->salary_max)
                            <div class="mono" style="font-size:18px; font-weight:600; color:var(--text); letter-spacing:-0.5px;">
                                {{ coinSymbol() }}{{ number_format($job->salary_min) }}{{ $job->salary_max ? '–'.number_format($job->salary_max) : '+' }}
                            </div>
                            @endif
                            <span class="btn btn-primary btn-sm" style="margin-top:8px; display:inline-flex;">View →</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @if($jobs->hasPages())
            @php
                $curJ  = $jobs->currentPage();
                $lastJ = $jobs->lastPage();
                $sJ    = max(1, $curJ - 2);
                $eJ    = min($lastJ, $curJ + 2);
            @endphp
            <div class="fw-pagination" style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:24px;flex-wrap:wrap;">
                @if($jobs->onFirstPage())
                <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);opacity:.4;cursor:not-allowed;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14L6 9l5-5"/></svg>
                </span>
                @else
                <a href="{{ $jobs->previousPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14L6 9l5-5"/></svg>
                </a>
                @endif
                @if($sJ > 1)
                <a href="{{ $jobs->url(1) }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">1</a>
                @if($sJ > 2)<span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;color:var(--muted);font-size:13px;">···</span>@endif
                @endif
                @for($p = $sJ; $p <= $eJ; $p++)
                @if($p === $curJ)
                <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--accent);background:var(--accent);color:#fff;font-size:13px;font-weight:600;">{{ $p }}</span>
                @else
                <a href="{{ $jobs->url($p) }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">{{ $p }}</a>
                @endif
                @endfor
                @if($eJ < $lastJ)
                @if($eJ < $lastJ - 1)<span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;color:var(--muted);font-size:13px;">···</span>@endif
                <a href="{{ $jobs->url($lastJ) }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">{{ $lastJ }}</a>
                @endif
                @if($jobs->hasMorePages())
                <a href="{{ $jobs->nextPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);text-decoration:none;transition:all .14s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 14l5-5-5-5"/></svg>
                </a>
                @else
                <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--muted);opacity:.4;cursor:not-allowed;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 14l5-5-5-5"/></svg>
                </span>
                @endif
            </div>
            @endif
            @endif
        </div>

        {{-- Contracts Tab --}}
        <div x-show="tab==='contract'" x-cloak
             x-transition:enter="fw-tab-enter"
             x-transition:enter-start="fw-tab-enter-from"
             x-transition:enter-end="fw-tab-enter-to">
            <div class="fw-empty" style="text-align:center; padding:80px 24px;">
                <div style="font-size:32px; margin-bottom:12px;">📄</div>
                <div style="font-size:15px; font-weight:500; color:var(--text); margin-bottom:8px;">{{ __('Contracts are private') }}</div>
                <p style="font-size:13.5px; color:var(--muted); max-width:400px; margin:0 auto 24px; line-height:1.6;">
                    {{ __("Contracts are invite-only engagements between two verified users. They don't appear in public search.") }}
                </p>
                @auth
                <a href="{{ route('user.contracts.sent') }}" class="btn btn-primary" style="font-size:13.5px;">{{ __('View my contracts') }}</a>
                @else
                <a href="{{ route('user.register') }}" class="btn btn-primary" style="font-size:13.5px;">{{ __('Sign up to get started') }}</a>
                @endauth
            </div>
        </div>

    </div>
</div>

</div>{{-- end x-data --}}

<style>
[x-cloak] { display: none !important; }

/* ── Header entrance ── */
.fw-h1  { animation: fw-fade-down .5s cubic-bezier(.22,1,.36,1) both; }
.fw-sub { animation: fw-fade-down .5s cubic-bezier(.22,1,.36,1) .09s both; }
.fw-tabs { animation: fw-fade-down .5s cubic-bezier(.22,1,.36,1) .16s both; }

/* ── Sidebar entrance ── */
.fw-sidebar { animation: fw-fade-right .55s cubic-bezier(.22,1,.36,1) .12s both; }

/* ── Live pulse dot ── */
.fw-live-dot {
    display: inline-block;
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--accent, #2f54eb);
    flex-shrink: 0;
    animation: fw-pulse 2s ease-in-out infinite;
}

/* ── Card stagger ── */
.fw-card {
    animation: fw-fade-up .45s cubic-bezier(.22,1,.36,1) both;
    animation-delay: calc(var(--i, 0) * 40ms + 60ms);
}
.fw-card-inner {
    transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .16s ease;
}
.fw-card:hover .fw-card-inner {
    transform: translateY(-2px) scale(1.005);
    box-shadow: 0 6px 20px rgba(10,10,11,0.09);
}
.fw-start-btn { transition: transform .14s, background .14s; }
.fw-card:hover .fw-start-btn { transform: scale(1.06); }

/* ── Tab content transition ── */
.fw-tab-enter         { transition: opacity .22s ease, transform .22s cubic-bezier(.22,1,.36,1); }
.fw-tab-enter-from    { opacity: 0; transform: translateY(10px); }
.fw-tab-enter-to      { opacity: 1; transform: translateY(0); }

/* ── Empty state ── */
.fw-empty { animation: fw-fade-up .4s cubic-bezier(.22,1,.36,1) .1s both; }

/* ── Pagination ── */
.fw-pagination { animation: fw-fade-up .4s cubic-bezier(.22,1,.36,1) .3s both; }

/* ── Keyframes ── */
@keyframes fw-fade-down {
    from { opacity:0; transform:translateY(-14px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes fw-fade-up {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes fw-fade-right {
    from { opacity:0; transform:translateX(-14px); }
    to   { opacity:1; transform:translateX(0); }
}
@keyframes fw-pulse {
    0%,100% { box-shadow:0 0 0 0 rgba(47,84,235,.6); }
    50%      { box-shadow:0 0 0 4px rgba(47,84,235,0); }
}

/* ── Reduced motion ── */
@media (prefers-reduced-motion: reduce) {
    .fw-h1,.fw-sub,.fw-tabs,.fw-sidebar,.fw-card,.fw-empty,.fw-pagination,
    .fw-tab-enter,.fw-live-dot { animation:none !important; transition:none !important; opacity:1 !important; transform:none !important; }
}

/* ── Responsive ── */
@media (max-width:900px) {
    .listings-grid { grid-template-columns:1fr !important; }
    .fw-sidebar { position:static !important; }
}
</style>

@endsection

@push('scripts')
<script>
(function() {
    /* Countdown timers */
    function pad(n) { return n < 10 ? '0' + n : n; }
    function tickAll() {
        document.querySelectorAll('.work-countdown[data-expires]').forEach(function(el) {
            var exp  = parseInt(el.dataset.expires) * 1000;
            var diff = Math.max(0, Math.floor((exp - Date.now()) / 1000));
            if (diff === 0) { el.textContent = 'Expired'; return; }
            var d = Math.floor(diff / 86400), h = Math.floor((diff % 86400) / 3600);
            var m = Math.floor((diff % 3600) / 60), s = diff % 60;
            el.textContent = d > 0
                ? d + 'd ' + pad(h) + 'h ' + pad(m) + 'm'
                : pad(h) + ':' + pad(m) + ':' + pad(s);
        });
    }
    tickAll();
    setInterval(tickAll, 1000);

    /* Re-run card stagger when tab changes */
    document.querySelectorAll('[x-data]').forEach(function(root) {
        root.addEventListener('tab-changed', function() {
            document.querySelectorAll('.fw-card').forEach(function(el, i) {
                el.style.animationName = 'none';
                void el.offsetHeight;
                el.style.setProperty('--i', i);
                el.style.animationName = '';
            });
        });
    });
})();
</script>
@endpush
