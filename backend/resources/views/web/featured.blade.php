@extends('web.layouts.app')
@section('title', __('Featured') . ' — ' . (gs()->site_name ?? config('app.name')))

@section('content')

<div x-data="{ tab: '{{ request('tab', 'instant') }}' }" x-init="$watch('tab', v => history.replaceState(null, '', '?tab=' + v))">

{{-- ── PAGE HEADER ─────────────────────────────────────────── --}}
<div style="background:#fff; border-bottom:1px solid var(--border); padding:60px 40px 48px;">
    <div style="max-width:680px; margin:0 auto; text-align:center;">

        {{-- Badge --}}
        <div style="display:inline-flex; align-items:center; gap:6px; padding:4px 12px 4px 8px; border-radius:999px; background:rgba(245,213,71,0.1); border:1px solid rgba(245,213,71,0.28); margin-bottom:20px;">
            <span style="font-size:14px; line-height:1;">⭐</span>
            <span style="font-size:11px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:#b45309;">{{ __('Featured') }}</span>
        </div>

        {{-- Heading --}}
        <h1 style="font-size:clamp(30px,4vw,48px); font-weight:600; margin:0 0 14px; letter-spacing:-1.5px; color:var(--text); line-height:1.08;">{{ __('Handpicked opportunities') }}</h1>
        <p style="font-size:15px; color:var(--muted); margin:0 0 36px; line-height:1.6;">
            {{ __('Boosted by clients — these jobs get more visibility, faster hiring, and often higher rewards.') }}
        </p>

        {{-- Segmented tabs --}}
        <div style="display:inline-flex; padding:5px; background:var(--border); border-radius:14px; gap:3px;">
            <button @click="tab='instant'"
                    :style="tab==='instant'
                        ? 'background:#fff; color:var(--text); box-shadow:0 2px 8px rgba(10,10,11,0.10); font-weight:600;'
                        : 'background:transparent; color:var(--muted); font-weight:500;'"
                    style="padding:11px 28px; border:none; border-radius:10px; cursor:pointer; font-size:14px; font-family:inherit; display:inline-flex; align-items:center; gap:8px; transition:all .2s cubic-bezier(.22,1,.36,1); white-space:nowrap;">
                <span style="font-size:15px; line-height:1;">⚡</span>
                {{ __('Instant Jobs') }}
                <span style="font-size:11px; font-family:ui-monospace,monospace; opacity:0.55; margin-left:2px;">{{ $featuredWorks->count() }}</span>
            </button>
            <button @click="tab='hiring'"
                    :style="tab==='hiring'
                        ? 'background:#fff; color:var(--text); box-shadow:0 2px 8px rgba(10,10,11,0.10); font-weight:600;'
                        : 'background:transparent; color:var(--muted); font-weight:500;'"
                    style="padding:11px 28px; border:none; border-radius:10px; cursor:pointer; font-size:14px; font-family:inherit; display:inline-flex; align-items:center; gap:8px; transition:all .2s cubic-bezier(.22,1,.36,1); white-space:nowrap;">
                <span style="font-size:15px; line-height:1;">💼</span>
                {{ __('Hiring Jobs') }}
                <span style="font-size:11px; font-family:ui-monospace,monospace; opacity:0.55; margin-left:2px;">{{ $featuredJobs->count() }}</span>
            </button>
        </div>

    </div>
</div>

{{-- ── MAIN CONTENT ────────────────────────────────────────── --}}
<div style="max-width:1200px; margin:0 auto; padding:36px 40px 80px;" class="featured-wrap">

    {{-- ── INSTANT JOBS TAB ────────────────────────────────── --}}
    <div x-show="tab==='instant'" x-cloak>
        @if($featuredWorks->isEmpty())
        <div style="text-align:center; padding:100px 40px;">
            <div style="width:56px; height:56px; border-radius:16px; background:rgba(255,122,89,0.1); display:flex; align-items:center; justify-content:center; font-size:24px; margin:0 auto 20px;">⚡</div>
            <div style="font-size:16px; font-weight:600; color:var(--text); margin-bottom:8px;">{{ __('No featured instant jobs right now') }}</div>
            <p style="font-size:14px; color:var(--muted); max-width:400px; margin:0 auto 24px; line-height:1.6;">{{ __('Post an instant job and boost it to appear here — or browse all available tasks.') }}</p>
            <a href="{{ route('works.index') }}" class="btn btn-primary" style="font-size:13.5px;">
                {{ __('Browse all instant jobs') }}
                <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
            </a>
        </div>
        @else
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:32px;" class="feat-instant-grid">
            @foreach($featuredWorks as $idx => $work)
            <a href="{{ route('works.show', $work->slug) }}" style="text-decoration:none; display:block;" data-reveal data-delay="{{ ($idx % 3) * 80 }}">
                <div class="card feat-work-card" style="padding:18px; position:relative; height:100%; box-sizing:border-box; display:flex; flex-direction:column; transition:transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s ease;"
                     onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 16px 40px rgba(10,10,11,0.1)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">

                    {{-- Featured badge --}}
                    <div style="position:absolute; top:14px; right:14px; background:rgba(245,213,71,0.12); border:1px solid rgba(245,213,71,0.3); border-radius:99px; padding:3px 9px; font-size:10.5px; font-weight:700; color:#ca8a04; letter-spacing:0.03em; white-space:nowrap;">⭐ {{ __('Featured') }}</div>

                    {{-- Top row --}}
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; padding-right:76px;">
                        <div style="width:34px; height:34px; border-radius:9px; background:rgba(255,122,89,0.12); color:#FF7A59; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:16px;">⚡</div>
                        <span style="font-size:11.5px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $work->category?->name }}</span>
                    </div>

                    {{-- Title --}}
                    <div style="font-size:15px; font-weight:600; line-height:1.4; color:var(--text); margin-bottom:10px; flex:1;">{{ Str::limit($work->title, 60) }}</div>

                    {{-- Meta --}}
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px;">
                        @if($work->avg_minutes)
                        <span style="font-size:11.5px; background:rgba(10,10,11,0.05); color:var(--muted); border-radius:99px; padding:3px 10px;">~{{ $work->avg_minutes }} {{ __('min') }}</span>
                        @endif
                        <span style="font-size:11.5px; background:rgba(10,10,11,0.05); color:var(--muted); border-radius:99px; padding:3px 10px;">{{ $work->slots_remaining }} {{ __('spots left') }}</span>
                    </div>

                    {{-- Footer --}}
                    <div style="display:flex; justify-content:space-between; align-items:center; padding-top:14px; border-top:1px solid var(--border);">
                        <div>
                            <div class="mono" style="font-size:17px; font-weight:700; color:#F5D547; line-height:1;">{{ formatCoins($work->coins_per_worker, 0) }}</div>
                            <div style="font-size:10.5px; color:var(--muted); margin-top:2px;">{{ __('per completion') }}</div>
                        </div>
                        <span style="font-size:12.5px; font-weight:600; color:var(--accent); display:flex; align-items:center; gap:5px;">
                            {{ __('Start task') }}
                            <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        {{-- Footer link --}}
        <div style="text-align:center; padding-top:8px; border-top:1px solid var(--border);">
            <a href="{{ route('works.index') }}" style="font-size:13.5px; color:var(--muted); text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:color .15s;" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
                {{ __('Browse all instant jobs') }}
                <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
            </a>
        </div>
        @endif
    </div>

    {{-- ── HIRING JOBS TAB ─────────────────────────────────── --}}
    <div x-show="tab==='hiring'" x-cloak>
        @if($featuredJobs->isEmpty())
        <div style="text-align:center; padding:100px 40px;">
            <div style="width:56px; height:56px; border-radius:16px; background:rgba(96,165,250,0.1); display:flex; align-items:center; justify-content:center; font-size:24px; margin:0 auto 20px;">💼</div>
            <div style="font-size:16px; font-weight:600; color:var(--text); margin-bottom:8px;">{{ __('No featured hiring jobs right now') }}</div>
            <p style="font-size:14px; color:var(--muted); max-width:400px; margin:0 auto 24px; line-height:1.6;">{{ __('Post a hiring job and boost it to appear here — or browse all open positions.') }}</p>
            <a href="{{ route('jobs.index') }}" class="btn btn-primary" style="font-size:13.5px;">
                {{ __('Browse all hiring jobs') }}
                <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
            </a>
        </div>
        @else
        <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:32px;">
            @foreach($featuredJobs as $idx => $listing)
            @php
                $locLabel = match($listing->location_type ?? 0) { 1 => __('Remote'), 2 => __('On-site'), 3 => __('Hybrid'), default => '' };
                $empLabel = match($listing->employment_type ?? '') { 'full_time' => __('Full-time'), 'part_time' => __('Part-time'), 'contract' => __('Contract'), 'freelance' => __('Freelance'), default => '' };
                $initial  = strtoupper(substr($listing->employer?->fullname ?? $listing->title, 0, 1));
            @endphp
            <div data-reveal data-delay="{{ $idx * 60 }}">
                <a href="{{ route('jobs.show', $listing->slug) }}" style="text-decoration:none; display:block;">
                    <div class="card" style="padding:22px 24px; display:flex; align-items:flex-start; gap:18px; position:relative; transition:transform .18s, box-shadow .18s;"
                         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 28px rgba(10,10,11,0.09)'"
                         onmouseout="this.style.transform='';this.style.boxShadow=''">

                        {{-- Featured badge --}}
                        <div style="position:absolute; top:16px; right:18px; background:rgba(245,213,71,0.12); border:1px solid rgba(245,213,71,0.3); border-radius:99px; padding:3px 9px; font-size:10.5px; font-weight:700; color:#ca8a04; white-space:nowrap;">⭐ {{ __('Featured') }}</div>

                        {{-- Logo / Initial --}}
                        @if($listing->cover_image)
                        <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image) }}"
                             alt="{{ $listing->title }}"
                             style="width:52px; height:52px; border-radius:12px; object-fit:cover; flex-shrink:0; border:1px solid var(--border);">
                        @else
                        <div style="width:52px; height:52px; border-radius:12px; background:linear-gradient(135deg,rgba(96,165,250,0.15),rgba(96,165,250,0.3)); display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid rgba(96,165,250,0.2);">
                            <span style="font-size:20px; font-weight:700; color:#60A5FA;">{{ $initial }}</span>
                        </div>
                        @endif

                        {{-- Content --}}
                        <div style="flex:1; min-width:0; padding-right:88px;">
                            <div style="font-size:16px; font-weight:600; color:var(--text); margin-bottom:5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $listing->title }}</div>

                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin-bottom:12px;">
                                @if($listing->employer)
                                <span style="font-size:13px; color:var(--muted);">{{ $listing->employer->fullname }}</span>
                                @endif
                                @if($listing->location && $listing->location_type != 1)
                                <span style="color:var(--border-strong); font-size:12px;">·</span>
                                <span style="font-size:13px; color:var(--muted);">{{ $listing->location }}</span>
                                @endif
                            </div>

                            <p style="font-size:13.5px; color:var(--muted); line-height:1.55; margin:0 0 14px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ Str::limit(strip_tags($listing->description), 160) }}</p>

                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                                @if($locLabel)
                                <span style="font-size:12px; background:rgba(96,165,250,0.1); color:#3b82f6; border-radius:99px; padding:4px 11px; font-weight:600;">{{ $locLabel }}</span>
                                @endif
                                @if($empLabel)
                                <span style="font-size:12px; background:rgba(10,10,11,0.05); color:var(--muted); border-radius:99px; padding:4px 11px; font-weight:500;">{{ $empLabel }}</span>
                                @endif
                                @if($listing->salary_visible && ($listing->salary_min || $listing->salary_max))
                                <span style="font-size:12px; background:rgba(34,197,94,0.08); color:#15803d; border-radius:99px; padding:4px 11px; font-weight:600;">
                                    {{ $listing->salary_currency ?? '' }}
                                    @if($listing->salary_min && $listing->salary_max)
                                        {{ number_format($listing->salary_min) }} – {{ number_format($listing->salary_max) }}
                                    @elseif($listing->salary_min)
                                        {{ number_format($listing->salary_min) }}+
                                    @else
                                        {{ __('up to') }} {{ number_format($listing->salary_max) }}
                                    @endif
                                </span>
                                @endif
                                @if($listing->closes_at && $listing->closes_at->isFuture())
                                <span style="font-size:11.5px; color:var(--muted);">{{ __('Closes') }} {{ $listing->closes_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Action --}}
                        <div style="align-self:center; flex-shrink:0; display:none;" class="feat-job-action">
                            @auth('web')
                            <a href="{{ route('user.jobs.show', $listing->id) }}" class="btn btn-primary btn-sm" onclick="event.stopPropagation()">{{ __('Apply') }}</a>
                            @else
                            <a href="{{ route('user.login') }}" class="btn btn-primary btn-sm" onclick="event.stopPropagation()">{{ __('Apply') }}</a>
                            @endauth
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>

        {{-- Footer link --}}
        <div style="text-align:center; padding-top:8px; border-top:1px solid var(--border);">
            <a href="{{ route('jobs.index') }}" style="font-size:13.5px; color:var(--muted); text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:color .15s;" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
                {{ __('Browse all hiring jobs') }}
                <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
            </a>
        </div>
        @endif
    </div>

</div>
</div>

<style>
@media (max-width:1024px) {
    .feat-instant-grid { grid-template-columns: 1fr 1fr !important; }
    .featured-wrap { padding-left:24px !important; padding-right:24px !important; }
}
@media (max-width:640px) {
    .feat-instant-grid { grid-template-columns: 1fr !important; }
    .featured-wrap { padding-left:20px !important; padding-right:20px !important; }
}
@media (min-width:900px) {
    .feat-job-action { display:flex !important; }
}
[data-reveal] {
    opacity:0; transform:translateY(20px);
    transition:opacity .55s cubic-bezier(.22,1,.36,1), transform .55s cubic-bezier(.22,1,.36,1);
}
[data-reveal].is-visible { opacity:1; transform:translateY(0); }
</style>

@push('scripts')
<script>
(function() {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                var el = e.target;
                setTimeout(function() { el.classList.add('is-visible'); }, parseInt(el.dataset.delay || 0, 10));
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
    document.querySelectorAll('[data-reveal]').forEach(function(el) { observer.observe(el); });
})();
</script>
@endpush

@endsection
