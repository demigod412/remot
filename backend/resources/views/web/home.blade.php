@extends('web.layouts.app')

@section('title', gs()->site_name ?? config('app.name'))

@section('content')

{{-- Pass translated typewriter text to JS before the push block --}}
@php $heroTyper = [__("Work that pays\nin "), __("minutes"), __(",\ncareers that\npay for years.")]; @endphp
<script>
window._heroTyper = @json($heroTyper);
</script>

{{-- ── HERO ──────────────────────────────────────────────────── --}}
<section style="position:relative; overflow:hidden; padding:80px 40px 100px; background:#fff;">
    {{-- Background video (cache-busted by file mtime so swaps show immediately) --}}
    @php $heroV = @filemtime(public_path('videos/jobstation-hero.mp4')) ?: 1; @endphp
    <video class="hero-bg-video" autoplay muted loop playsinline preload="auto"
           poster="{{ asset('videos/jobstation-hero.jpg') }}?v={{ $heroV }}"
           style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:0; pointer-events:none;">
        <source src="{{ asset('videos/jobstation-hero.mp4') }}?v={{ $heroV }}" type="video/mp4">
    </video>
    {{-- White overlay (stronger over the text, lighter over the cards) --}}
    <div aria-hidden="true" style="position:absolute; inset:0; z-index:1; pointer-events:none;
         background:linear-gradient(100deg, rgba(255,255,255,0.90) 0%, rgba(255,255,255,0.76) 46%, rgba(255,255,255,0.5) 100%);"></div>

    <div style="position:relative; z-index:2; max-width:1280px; margin:0 auto; display:grid; grid-template-columns:1.1fr 1fr; gap:80px; align-items:center;" class="hero-grid">

        <div>
            <div class="anim-hero-badge" style="display:inline-flex; align-items:center; gap:8px; padding:5px 12px 5px 6px; border-radius:999px; background:#fff; border:1px solid var(--border); font-size:12.5px; color:var(--muted); margin-bottom:28px; box-shadow:0 1px 3px rgba(10,10,11,0.06);">
                <span style="background:var(--accent-soft); color:var(--accent); padding:2px 8px; border-radius:999px; font-weight:600; font-size:11px;">{{ __('NEW') }}</span>
                {{ __('Contracts are live — long-term private work →') }}
            </div>
            <h1 id="hero-h1" style="font-size:clamp(48px,5.2vw,76px); line-height:1.08; font-weight:600; letter-spacing:-2.5px; margin:0 0 24px; color:var(--text); min-height:4.4em;">
                <span id="hero-typed"></span><span class="typing-cursor">|</span>
            </h1>
            <p class="anim-hero-p" style="font-size:17px; color:var(--muted); line-height:1.55; max-width:480px; margin:0 0 36px;">
                {{ __('Job Station is one marketplace for three kinds of work — instant microtasks, hiring jobs, and long-term contracts. Find paid work in seconds or hire the right person in hours.') }}
            </p>
            <div class="anim-hero-actions" style="display:flex; gap:12px; margin-bottom:40px; flex-wrap:wrap;">
                <a href="{{ route('works.index') }}" class="btn btn-primary" style="padding:12px 20px; font-size:14px;">
                    {{ __('Browse jobs') }}
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
                </a>
                <a href="{{ route('user.works.index') }}" class="btn btn-secondary" style="padding:12px 20px; font-size:14px;">{{ __('Post a job') }}</a>
            </div>
            <div class="anim-hero-stats" style="display:flex; gap:36px; padding-top:28px; border-top:1px solid var(--border); flex-wrap:wrap;">
                <div>
                    <div class="mono counter" data-target="{{ $stats['completed'] }}" style="font-size:22px; font-weight:600; letter-spacing:-0.5px; color:var(--text);">{{ number_format($stats['completed']) }}</div>
                    <div style="font-size:12px; color:var(--muted); margin-top:2px;">{{ __('tasks completed') }}</div>
                </div>
                <div>
                    <div class="mono" style="font-size:22px; font-weight:600; letter-spacing:-0.5px; color:#F5D547;">{{ coinSymbol() }} {{ number_format($stats['completed'] * 8.5) }}</div>
                    <div style="font-size:12px; color:var(--muted); margin-top:2px;">{{ __('paid out to workers') }}</div>
                </div>
                <div>
                    <div class="mono counter" data-target="{{ $stats['workers'] }}" style="font-size:22px; font-weight:600; letter-spacing:-0.5px; color:var(--text);">{{ number_format($stats['workers']) }}</div>
                    <div style="font-size:12px; color:var(--muted); margin-top:2px;">{{ __('active workers') }}</div>
                </div>
            </div>
        </div>

        {{-- Floating job cards --}}
        <div style="position:relative; height:520px;" class="hero-cards">
            @php
                $heroWorks = $featured->take(4);
                $positions = [['0px','0px'],['130px','200px'],['280px','10px'],['400px','220px']];
                $floatDelays = ['0s','0.8s','1.6s','2.4s'];
            @endphp
            @if($heroWorks->isNotEmpty())
                @foreach($heroWorks as $i => $work)
                <div class="hero-float-card" style="position:absolute; top:{{ $positions[$i][0] }}; left:{{ $positions[$i][1] }}; width:260px; padding:16px; box-shadow:0 20px 60px -20px rgba(10,10,11,0.15); animation-delay:{{ $floatDelays[$i] }};" class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span class="chip chip-instant" style="font-size:10px; text-transform:uppercase;">⚡ {{ __('Instant') }}</span>
                        <span class="mono" style="font-size:11px; color:var(--muted);">{{ $work->time_limit ?? rand(5,45) }}{{ __('min') }}</span>
                    </div>
                    <div style="font-size:14px; font-weight:500; line-height:1.35; margin-bottom:14px; color:var(--text);">{{ Str::limit($work->title, 52) }}</div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="mono" style="font-size:14px; font-weight:600; color:#F5D547;">{{ coinSymbol() }}{{ $work->coins_per_worker }}</span>
                        <span style="font-size:11px; color:var(--muted);">{{ $work->category?->name }}</span>
                    </div>
                </div>
                @endforeach
            @else
                @foreach([
                    [__('Verify this app listing screenshot'),'12',__('QA'),'0px','0px','0s'],
                    [__('Senior React Engineer'),'8k–12k',__('Full-time'),'130px','200px','0.8s'],
                    [__('6-month brand design retainer'),'5k/mo',__('Design'),'280px','10px','1.6s'],
                    [__('Translate 80-word paragraph → FR'),'4',__('Language'),'400px','220px','2.4s'],
                ] as [$t,$r,$c,$top,$left,$delay])
                <div class="hero-float-card card" style="position:absolute; top:{{ $top }}; left:{{ $left }}; width:260px; padding:16px; box-shadow:0 20px 60px -20px rgba(10,10,11,0.15); animation-delay:{{ $delay }};">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span class="chip chip-instant" style="font-size:10px; text-transform:uppercase;">⚡ {{ __('Instant') }}</span>
                        <span class="mono" style="font-size:11px; color:var(--muted);">14{{ __('min') }}</span>
                    </div>
                    <div style="font-size:14px; font-weight:500; line-height:1.35; margin-bottom:14px; color:var(--text);">{{ $t }}</div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="mono" style="font-size:14px; font-weight:600; color:#F5D547;">{{ coinSymbol() }}{{ $r }}</span>
                        <span style="font-size:11px; color:var(--muted);">{{ $c }}</span>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</section>

{{-- ── HOW IT WORKS ──────────────────────────────────────────── --}}
<section id="how-it-works" style="padding:100px 40px; border-top:1px solid var(--border); background:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:60px; flex-wrap:wrap; gap:20px;">
            <div>
                <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--accent); margin-bottom:12px;">{{ __('How Job Station works') }}</div>
                <h2 style="font-size:clamp(28px,3.5vw,48px); font-weight:600; letter-spacing:-1.5px; margin:0; max-width:600px; color:var(--text); line-height:1.1;">
                    {{ __('Three systems, one wallet, zero friction.') }}
                </h2>
            </div>
            <a href="{{ route('contact') }}" class="btn btn-secondary" style="font-size:13px;">
                {{ __('Learn more') }}
                <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
            </a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px;" class="three-col">
            @foreach([
                ['01','⚡',__('Instant Jobs'),'#FF7A59',[__('Take a task, beat the timer'),__('Submit proof — screenshot or text'),__("Coins credit the moment it's approved")]],
                ['02','💼',__('Hiring Jobs'),'#60A5FA',[__('Search or filter by skill'),__('One-click apply with your Job Station profile'),__('Chat, interview, get hired on platform')]],
                ['03','📄',__('Contracts'),'#34D399',[__('Define milestones privately'),__('Funds held in escrow until delivery'),__('Dispute-protected by Job Station mediation')]],
            ] as $idx => [$n,$icon,$title,$color,$bullets])
            <div class="card reveal-card" data-reveal data-delay="{{ $idx * 120 }}" style="padding:28px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px;">
                    <div style="width:44px; height:44px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:20px; background:{{ $color }}22; color:{{ $color }};">{{ $icon }}</div>
                    <span class="mono" style="font-size:11px; color:#A1A1AA;">{{ $n }}</span>
                </div>
                <h3 style="font-size:22px; font-weight:600; margin:0 0 20px; letter-spacing:-0.4px; color:var(--text);">{{ $title }}</h3>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px;">
                    @foreach($bullets as $b)
                    <li style="font-size:13.5px; color:var(--muted); display:flex; gap:10px; align-items:flex-start;">
                        <svg width="16" height="16" viewBox="0 0 18 18" fill="none" stroke="{{ $color }}" stroke-width="1.8" stroke-linecap="round" style="flex-shrink:0; margin-top:2px;"><path d="M3 9l4 4 8-8"/></svg>
                        {{ $b }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── LIVE STRIP ─────────────────────────────────────────────── --}}
<section style="padding:80px 40px; border-top:1px solid var(--border);">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:center; gap:12px; margin-bottom:28px;">
            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:var(--urgent); animation:pulse-urgent 1.6s infinite; flex-shrink:0;"></span>
            <span style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--urgent);">{{ __('Live right now') }}</span>
            <div style="flex:1; height:1px; background:var(--border);"></div>
            <span style="font-size:12.5px; color:var(--muted);">{{ $stats['works'] }} {{ __('active tasks') }}</span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px;" class="four-col">
            @forelse($featured->take(4) as $idx => $work)
            <a href="{{ route('works.show', $work->slug) }}" style="text-decoration:none; display:block;" data-reveal data-delay="{{ $idx * 80 }}">
                <div class="card" style="padding:14px; transition:transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s ease;" onmouseover="this.style.transform='translateY(-4px) scale(1.01)';this.style.boxShadow='0 12px 32px rgba(10,10,11,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span class="chip chip-instant" style="font-size:10px; text-transform:uppercase;">⚡ {{ __('Instant') }}</span>
                        <span class="mono" style="font-size:11px; color:var(--muted);">{{ $work->category?->name }}</span>
                    </div>
                    <div style="font-size:13.5px; font-weight:500; line-height:1.35; margin-bottom:12px; min-height:38px; color:var(--text);">{{ Str::limit($work->title, 55) }}</div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid var(--border);">
                        <span class="mono" style="font-size:14px; font-weight:600; color:#F5D547;">{{ coinSymbol() }}{{ $work->coins_per_worker }}</span>
                        <span style="font-size:11px; font-weight:500; color:var(--accent);">{{ __('Start →') }}</span>
                    </div>
                </div>
            </a>
            @empty
            @foreach([[__('Find business address on website'),3,__('Research')],[__('Proofread product page copy'),8,__('Writing')],[__('Test checkout flow on iOS'),15,__('QA')],[__('Categorize 50 support tickets'),22,__('Data')]] as $idx => [$t,$r,$c])
            <div class="card" style="padding:14px;" data-reveal data-delay="{{ $idx * 80 }}">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span class="chip chip-instant" style="font-size:10px; text-transform:uppercase;">⚡ {{ __('Instant') }}</span>
                    <span style="font-size:11px; color:var(--muted);">{{ $c }}</span>
                </div>
                <div style="font-size:13.5px; font-weight:500; line-height:1.35; margin-bottom:12px; min-height:38px; color:var(--text);">{{ $t }}</div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid var(--border);">
                    <span class="mono" style="font-size:14px; font-weight:600; color:#F5D547;">{{ coinSymbol() }}{{ $r }}</span>
                    <a href="{{ route('works.index') }}" style="font-size:11px; font-weight:500; color:var(--accent);">{{ __('Start →') }}</a>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>
    </div>
</section>

{{-- ── LOGO WALL ──────────────────────────────────────────────── --}}
<section style="padding:60px 40px; border-top:1px solid var(--border); background:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="text-align:center; font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:36px;">
            {{ __('Trusted by teams at 8,000+ companies') }}
        </div>
        <div style="display:grid; grid-template-columns:repeat(6,1fr); border-top:1px solid var(--border); border-left:1px solid var(--border);" class="logo-grid">
            @foreach(['Parcel.io','Halo Studio','Delta','Framework','Pillar','Matter','Groundtruth','Loop Media','Astra','Tessera','Orbit','Kindred'] as $idx => $logo)
            <div class="logo-cell" data-reveal data-delay="{{ ($idx % 6) * 60 }}" style="padding:28px 20px; border-right:1px solid var(--border); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:center; color:#A1A1AA; font-size:15px; font-weight:500; letter-spacing:-0.3px; transition:color .2s, background .2s;" onmouseover="this.style.color='var(--text)';this.style.background='rgba(47,84,235,0.04)'" onmouseout="this.style.color='#A1A1AA';this.style.background=''">
                {{ $logo }}
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── STATS BAND ─────────────────────────────────────────────── --}}
<section style="padding:100px 40px; border-top:1px solid var(--border);">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:48px; flex-wrap:wrap; gap:20px;">
            <div>
                <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--accent); margin-bottom:12px;">{{ __('Platform health') }}</div>
                <h2 style="font-size:clamp(28px,3.5vw,48px); font-weight:600; letter-spacing:-1.5px; margin:0; max-width:620px; color:var(--text); line-height:1.1;">
                    {{ __('The numbers behind the marketplace.') }}
                </h2>
            </div>
            <span style="font-size:12.5px; color:var(--muted);">{{ __('Updated live · all time unless noted') }}</span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); border-top:1px solid var(--border); border-left:1px solid var(--border);" class="four-col stats-grid">
            @foreach([
                [coinSymbol().' '.number_format($stats['completed'] * 8.5), __('Paid out to workers'),  __('Since launch'),         null],
                [number_format($stats['completed']),             __('Tasks completed'),        __('All time'),            $stats['completed']],
                ['97.4%',                                        __('Approval rate'),          __('Across instant jobs'), null],
                ['4m 12s',                                       __('Median payout time'),     __('Instant jobs'),        null],
                [number_format($stats['workers']),               __('Active workers'),         __('Verified (L1+)'),      $stats['workers']],
                [number_format($stats['works']),                 __('Active tasks'),           __('Right now'),           $stats['works']],
                [$stats['categories'].' '.__('categories'),      __('Work categories'),        __('All types'),           null],
                ['< 0.4%',                                       __('Dispute rate'),           __('Contracts system'),    null],
            ] as $idx => [$v,$l,$s,$target])
            <div data-reveal data-delay="{{ ($idx % 4) * 80 }}" style="padding:32px 28px; border-right:1px solid var(--border); border-bottom:1px solid var(--border);">
                <div class="mono{{ $target ? ' counter' : '' }}"{{ $target ? ' data-target="'.$target.'"' : '' }} style="font-size:clamp(22px,2.5vw,34px); font-weight:600; letter-spacing:-1.2px; line-height:1; margin-bottom:12px; color:var(--text);">{{ $v }}</div>
                <div style="font-size:13px; color:var(--text); font-weight:500; margin-bottom:4px;">{{ $l }}</div>
                <div style="font-size:11.5px; color:var(--muted);">{{ $s }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── TESTIMONIALS ───────────────────────────────────────────── --}}
<section style="padding:100px 40px; border-top:1px solid var(--border); background:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:48px; flex-wrap:wrap; gap:20px;">
            <div>
                <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--accent); margin-bottom:12px;">{{ __('Loved by workers and clients') }}</div>
                <h2 style="font-size:clamp(28px,3.5vw,48px); font-weight:600; letter-spacing:-1.5px; margin:0; max-width:620px; color:var(--text); line-height:1.05;">
                    {{ __('The marketplace people actually come back to.') }}
                </h2>
            </div>
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="display:flex; gap:2px; font-size:18px; color:#F5D547;">★★★★★</div>
                <div>
                    <div style="font-size:14px; font-weight:600; color:var(--text);">4.9 / 5</div>
                    <div style="font-size:11px; color:var(--muted);">{{ __('Avg across 12,400 reviews') }}</div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1.4fr 1fr 1fr; gap:18px;" class="testi-grid">
            <div class="card" data-reveal data-delay="0" style="padding:36px; display:flex; flex-direction:column; gap:20px;">
                <div style="font-size:40px; line-height:1; color:var(--accent); font-weight:600; font-family:'Poppins',sans-serif;">"</div>
                <p style="font-size:20px; line-height:1.45; font-weight:500; letter-spacing:-0.3px; margin:0; color:var(--text);">
                    {{ __('I earned my first JC500 in a weekend doing QA tasks during my commute. Three months later I landed a full hiring gig through the same platform — same profile, same wallet.') }}
                </p>
                <div style="display:flex; align-items:center; gap:12px; padding-top:20px; border-top:1px solid var(--border); margin-top:auto;">
                    <div style="width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,#2f54eb,#60A5FA); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:16px; flex-shrink:0;">P</div>
                    <div style="flex:1;">
                        <div style="font-size:13.5px; font-weight:500; color:var(--text);">Priya Nair</div>
                        <div style="font-size:11.5px; color:var(--muted);">{{ __('QA Engineer · L2 verified') }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="mono" style="font-size:15px; font-weight:600; color:#F5D547;">{{ coinSymbol() }} 28,463</div>
                        <div style="font-size:10.5px; color:var(--muted);">{{ __('Lifetime earnings') }}</div>
                    </div>
                </div>
            </div>
            @foreach([
                ['M','Maya Rhee',__('Head of Ops · Parcel.io'),__('We posted 120 research tasks and had them all back in 4 hours. Quality checks are baked in, nobody had to chase anyone for proof.'),__('1.2k jobs posted'),'#FF7A59'],
                ['T','Tom Winters',__('Creative Director · Halo Studio'),__('Contracts gave us a way to keep our best illustrator on retainer. Escrow removes every awkward conversation about money.'),__('6 active contracts'),'#34D399'],
            ] as $idx => [$init,$name,$role,$quote,$meta,$color])
            <div class="card" data-reveal data-delay="{{ ($idx + 1) * 120 }}" style="padding:24px; display:flex; flex-direction:column; gap:16px;">
                <div style="font-size:13px; color:#F5D547;">★★★★★</div>
                <p style="font-size:14px; line-height:1.55; color:var(--muted); margin:0; flex:1;">"{{ $quote }}"</p>
                <div style="display:flex; align-items:center; gap:10px; padding-top:16px; border-top:1px solid var(--border);">
                    <div style="width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,{{ $color }},{{ $color }}99); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:12px; flex-shrink:0;">{{ $init }}</div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:12.5px; font-weight:500; color:var(--text);">{{ $name }}</div>
                        <div style="font-size:10.5px; color:var(--muted);">{{ $role }}</div>
                    </div>
                    <span style="font-size:10px; color:var(--accent); padding:3px 7px; border-radius:4px; background:var(--accent-soft); white-space:nowrap; flex-shrink:0;">{{ $meta }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <div style="margin-top:18px; display:grid; grid-template-columns:repeat(3,1fr); gap:18px;" class="three-col">
            @foreach([
                ['T','Tomas Klein',__('Freelance translator'),'#60A5FA',__('Payouts hit my Wise account in under 10 minutes. Unreal.')],
                ['A','Amara Obi',__('Data annotator'),'#22C55E',__('Clear briefs. Fair rejections. I finally quit the other platforms.')],
                ['L','Lee Jung',__('iOS developer'),'#F59E0B',__('The contract escrow saved me from a 4-week payment chase.')],
            ] as $idx => [$init,$name,$role,$color,$quote])
            <div class="card" data-reveal data-delay="{{ $idx * 100 }}" style="padding:20px; display:flex; gap:12px; align-items:flex-start;">
                <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,{{ $color }},{{ $color }}99); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:13px; flex-shrink:0;">{{ $init }}</div>
                <div>
                    <p style="font-size:13px; line-height:1.45; margin:0 0 8px; color:var(--text);">"{{ $quote }}"</p>
                    <div style="font-size:11px; color:var(--muted);">{{ $name }} · {{ $role }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── FAQ ────────────────────────────────────────────────────── --}}
<section style="padding:100px 40px; border-top:1px solid var(--border);">
    <div style="max-width:1100px; margin:0 auto; display:grid; grid-template-columns:380px 1fr; gap:80px;" class="faq-grid">
        <div data-reveal>
            <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--accent); margin-bottom:12px;">{{ __('FAQ') }}</div>
            <h2 style="font-size:clamp(28px,3vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0 0 20px; line-height:1.05; color:var(--text);">{{ __('Questions, answered.') }}</h2>
            <p style="font-size:14.5px; color:var(--muted); line-height:1.55; margin:0 0 24px;">
                {{ __("Can't find what you're looking for? Our help center has 200+ articles and our support team replies in under 4 hours.") }}
            </p>
            <a href="{{ route('contact') }}" class="btn btn-secondary" style="font-size:13px;">
                {{ __('Visit help center') }}
                <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
            </a>
        </div>
        <div x-data="{ open: 0 }" data-reveal data-delay="100">
            @foreach([
                [__('What exactly is a "coin" on Job Station?'), __('Coins (JC) are Job Station\'s internal credit unit. 1 JC = $0.10 USD for accounting. You can cash out to bank, Wise, PayPal, or USDC anytime above a JC50 minimum.')],
                [__('How fast do instant jobs pay?'), __('The moment a submission is approved (or auto-approved after the SLA), coins land in your wallet. Median approval time is 4m 12s.')],
                [__('What if a submission is rejected unfairly?'), __('You can dispute within 48 hours. A Job Station moderator reviews the proof and the rejection reason; approved disputes pay double the reward as a trust bonus.')],
                [__('Do I need KYC to get paid?'), __('You can earn and hold coins without KYC. To withdraw above JC500/month you\'ll need L1 (phone + ID). Contracts and high-value hiring jobs require L2.')],
                [__('How is Contracts different from Hiring Jobs?'), __('Hiring Jobs is a job board — you apply and get hired off-platform. Contracts keep everything on Job Station: private scope, milestone escrow, and dispute mediation built in.')],
                [__('Are there fees?'), __('Workers pay 0% on earnings. Clients pay a flat 5% on instant jobs and 3% on contracts. Hiring posts are free; we charge a success fee only if you hire.')],
            ] as $idx => [$q,$a])
            <div style="border-bottom:1px solid var(--border); {{ $idx===0 ? 'border-top:1px solid var(--border);' : '' }}">
                <button @click="open === {{ $idx }} ? open = -1 : open = {{ $idx }}"
                        style="width:100%; padding:20px 0; display:flex; justify-content:space-between; align-items:flex-start; gap:20px; background:none; border:none; cursor:pointer; text-align:left; font-family:inherit;">
                    <span style="font-size:16px; font-weight:500; letter-spacing:-0.2px; color:var(--text);">{{ $q }}</span>
                    <span style="color:var(--muted); flex-shrink:0; margin-top:2px; transition:transform .18s;" :style="open === {{ $idx }} ? 'transform:rotate(45deg)' : ''">
                        <svg width="16" height="16" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 3v12M3 9h12"/></svg>
                    </span>
                </button>
                <div x-show="open === {{ $idx }}" x-transition style="padding-bottom:20px;">
                    <p style="font-size:14px; color:var(--muted); line-height:1.6; margin:0;">{{ $a }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── CTA BAND ───────────────────────────────────────────────── --}}
<section style="padding:100px 40px; border-top:1px solid var(--border);">
    <div data-reveal style="max-width:1000px; margin:0 auto; text-align:center; padding:80px 60px; background:linear-gradient(135deg,rgba(47,84,235,0.08),#fff); border:1px solid var(--border); border-radius:24px; box-shadow:0 1px 3px rgba(10,10,11,0.06);">
        <h2 style="font-size:clamp(32px,4vw,56px); font-weight:600; letter-spacing:-2px; margin:0 0 20px; color:var(--text);">
            {{ __('Your next JC is two clicks away.') }}
        </h2>
        <p style="font-size:17px; color:var(--muted); max-width:560px; margin:0 auto 32px; line-height:1.55;">
            {{ __('Sign up free. No listing fees. Get verified in 6 minutes and take your first task.') }}
        </p>
        <div style="display:inline-flex; gap:12px; flex-wrap:wrap; justify-content:center;">
            <a href="{{ route('user.register') }}" class="btn btn-primary" style="padding:12px 22px; font-size:14.5px;">{{ __('Get started free') }}</a>
            <a href="{{ route('works.index') }}" class="btn btn-secondary" style="padding:12px 22px; font-size:14.5px;">{{ __('See open jobs') }}</a>
        </div>
    </div>
</section>

<style>
@keyframes pulse-urgent {
    0%   { box-shadow: 0 0 0 0 rgba(255,122,89,0.55); }
    70%  { box-shadow: 0 0 0 8px rgba(255,122,89,0); }
    100% { box-shadow: 0 0 0 0 rgba(255,122,89,0); }
}
@keyframes fadeUp {
    from { opacity:0; transform:translateY(28px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes fadeDown {
    from { opacity:0; transform:translateY(-16px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes fadeIn {
    from { opacity:0; }
    to   { opacity:1; }
}
@keyframes floatCard {
    0%,100% { transform: translateY(0px) rotate(0deg); }
    33%     { transform: translateY(-10px) rotate(0.4deg); }
    66%     { transform: translateY(-5px) rotate(-0.3deg); }
}
@keyframes cardEntrance {
    from { opacity:0; transform:translateY(20px) scale(0.96); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes cursorBlink {
    0%, 100% { opacity:1; }
    50%       { opacity:0; }
}
@keyframes cursorFade {
    to { opacity:0; }
}
.typing-cursor {
    color: var(--accent);
    font-weight: 300;
    animation: cursorBlink 0.65s step-end infinite;
}
.typing-cursor.done {
    animation: cursorBlink 0.65s step-end 3, cursorFade 0.5s ease 1.95s forwards;
}
.anim-hero-badge { animation: fadeDown 0.55s cubic-bezier(.22,1,.36,1) both; }
.anim-hero-p     { animation: fadeUp 0.65s cubic-bezier(.22,1,.36,1) 0.26s both; }
.anim-hero-actions { animation: fadeUp 0.65s cubic-bezier(.22,1,.36,1) 0.4s both; }
.anim-hero-stats   { animation: fadeUp 0.65s cubic-bezier(.22,1,.36,1) 0.54s both; }
.hero-float-card {
    animation: cardEntrance 0.7s cubic-bezier(.22,1,.36,1) both, floatCard 6s ease-in-out infinite;
    animation-delay: var(--card-delay, 0s), calc(var(--card-delay, 0s) + 0.7s);
}
[data-reveal] {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.65s cubic-bezier(.22,1,.36,1), transform 0.65s cubic-bezier(.22,1,.36,1);
}
[data-reveal].is-visible { opacity: 1; transform: translateY(0); }
.hero-cards { display: none; }
@media (min-width:1024px) { .hero-cards { display: block; } }
@media (max-width:1024px) {
    .hero-grid       { grid-template-columns: 1fr !important; }
    .testi-grid      { grid-template-columns: 1fr !important; }
    .footer-grid     { grid-template-columns: 1fr 1fr !important; }
    .faq-grid        { grid-template-columns: 1fr !important; }
    .three-col       { grid-template-columns: 1fr 1fr !important; }
    .four-col        { grid-template-columns: 1fr 1fr !important; }
    .stats-grid      { grid-template-columns: 1fr 1fr !important; }
    .logo-grid       { grid-template-columns: repeat(3,1fr) !important; }
}
@media (max-width:640px) {
    .three-col, .four-col { grid-template-columns: 1fr !important; }
    .logo-grid { grid-template-columns: repeat(2,1fr) !important; }
    section { padding-left: 20px !important; padding-right: 20px !important; }
    footer  { padding-left: 20px !important; padding-right: 20px !important; }
}
@media (prefers-reduced-motion: reduce) {
    .anim-hero-badge, .anim-hero-p, .anim-hero-actions, .anim-hero-stats,
    .typing-cursor, .hero-float-card, [data-reveal] {
        animation: none !important; transition: none !important;
        opacity: 1 !important; transform: none !important;
    }
}
</style>

@push('scripts')
<script>
(function() {
    // Typewriter — text comes from server-side translation via window._heroTyper
    (function() {
        var el     = document.getElementById('hero-typed');
        var cursor = document.querySelector('.typing-cursor');
        if (!el) return;

        var t = window._heroTyper || ['Work that pays\nin ', 'minutes', ',\ncareers that\npay for years.'];
        var parts = [[t[0], null], [t[1], 'var(--urgent)'], [t[2], null]];

        var chars = [];
        parts.forEach(function(p) {
            p[0].split('').forEach(function(ch) { chars.push({ch: ch, color: p[1]}); });
        });

        var idx = 0, speed = 44;

        function buildHTML(count) {
            var html = '', inColor = null, i = 0;
            while (i < count) {
                var c = chars[i];
                if (c.ch === '\n') {
                    if (inColor) { html += '</span>'; inColor = null; }
                    html += '<br>';
                } else if (c.color !== inColor) {
                    if (inColor) html += '</span>';
                    if (c.color) { html += '<span style="color:' + c.color + '">'; }
                    inColor = c.color || null;
                    html += c.ch;
                } else {
                    html += c.ch;
                }
                i++;
            }
            if (inColor) html += '</span>';
            return html;
        }

        function type() {
            if (idx > chars.length) { cursor.classList.add('done'); return; }
            el.innerHTML = buildHTML(idx);
            idx++;
            setTimeout(type, speed);
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            el.innerHTML = buildHTML(chars.length);
            cursor.classList.add('done');
            return;
        }
        setTimeout(type, 450);
    })();

    // Hero card float delays
    document.querySelectorAll('.hero-float-card').forEach(function(card) {
        var delay = card.style.animationDelay || '0s';
        card.style.setProperty('--card-delay', delay);
        card.style.animationDelay = delay + ', calc(' + delay + ' + 0.7s)';
    });

    // Scroll reveal
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var el = entry.target;
                setTimeout(function() { el.classList.add('is-visible'); }, parseInt(el.dataset.delay || 0, 10));
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('[data-reveal]').forEach(function(el) { observer.observe(el); });

    // Number counters
    function animateCounter(el) {
        var target = parseInt(el.dataset.target, 10);
        if (!target) return;
        var original = el.textContent, duration = 1400, start = null;
        function easeOut(t) { return 1 - Math.pow(1 - t, 3); }
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            el.textContent = Math.round(easeOut(p) * target).toLocaleString();
            if (p < 1) requestAnimationFrame(step); else el.textContent = original;
        }
        requestAnimationFrame(step);
    }
    var cObs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) { if (e.isIntersecting) { animateCounter(e.target); cObs.unobserve(e.target); } });
    }, { threshold: 0.5 });
    document.querySelectorAll('.counter[data-target]').forEach(function(el) { cObs.observe(el); });
})();
</script>
@endpush

@endsection
