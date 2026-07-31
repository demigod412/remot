@extends('web.layouts.app')

@section('title', 'Blog — ' . (gs()->site_name ?? config('app.name')))

@section('content')

@php
    $featured = $posts->first();
    $rest     = $posts->slice(1);
    $tones    = ['violet','sky','emerald','amber','rose','orange'];
    $gradients = [
        'violet'  => 'linear-gradient(135deg,#2f54eb 0%,#4C1D95 100%)',
        'sky'     => 'linear-gradient(135deg,#60A5FA 0%,#1E3A8A 100%)',
        'emerald' => 'linear-gradient(135deg,#34D399 0%,#065F46 100%)',
        'amber'   => 'linear-gradient(135deg,#F59E0B 0%,#92400E 100%)',
        'rose'    => 'linear-gradient(135deg,#FB7185 0%,#9F1239 100%)',
        'orange'  => 'linear-gradient(135deg,#FB923C 0%,#92400E 100%)',
    ];
    $cats = ['All','Product','Workers','Clients','Engineering','Company','Tutorials'];
@endphp

{{-- ── PAGE HEADER ────────────────────────────────────────────── --}}
<section style="padding:72px 40px 40px;">
    <div style="max-width:1200px; margin:0 auto; display:flex; align-items:flex-end; justify-content:space-between; gap:40px; flex-wrap:wrap;">
        <div>
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; color:var(--accent); margin-bottom:14px;">The Job Station Blog</div>
            <h1 style="font-size:clamp(36px,5vw,64px); font-weight:600; letter-spacing:-2px; margin:0 0 16px; line-height:1; max-width:760px; color:var(--text);">
                Notes from the marketplace.
            </h1>
            <p style="font-size:16px; color:var(--muted); max-width:560px; line-height:1.55; margin:0;">
                Product updates, worker stories, engineering deep-dives, and the numbers behind {{ gs()->site_name ?? config('app.name') }}.
            </p>
        </div>
        <div class="card" style="padding:20px; width:360px; flex-shrink:0;">
            <div style="font-size:13px; font-weight:600; margin-bottom:4px; color:var(--text);">Subscribe to the newsletter</div>
            <div style="font-size:11.5px; color:var(--muted); margin-bottom:14px;">Monthly. No spam.</div>
            <form method="POST" action="{{ route('subscribe') }}" style="display:flex; gap:8px;">
                @csrf
                <input type="email" name="email" placeholder="you@company.com" style="flex:1; font-size:13px;" required>
                <button type="submit" class="btn btn-primary" style="white-space:nowrap; padding:8px 14px; font-size:12.5px;">Subscribe</button>
            </form>
        </div>
    </div>
</section>

{{-- ── CATEGORY TABS ───────────────────────────────────────────── --}}
<div style="padding:0 40px; border-bottom:1px solid var(--border);">
    <div style="max-width:1200px; margin:0 auto; display:flex; align-items:center; gap:4px; overflow-x:auto;">
        @foreach($cats as $c)
        <a href="{{ route('blog.index') }}"
           style="padding:14px 16px; border:none; background:transparent; text-decoration:none;
                  color:{{ $c === 'All' ? 'var(--text)' : 'var(--muted)' }};
                  font-weight:{{ $c === 'All' ? '600' : '500' }};
                  font-size:13px; cursor:pointer; font-family:inherit; margin-bottom:-1px;
                  white-space:nowrap; display:inline-block;
                  border-bottom:{{ $c === 'All' ? '2px solid var(--accent)' : '2px solid transparent' }};">
            {{ $c }}
        </a>
        @endforeach
        <div style="flex:1;"></div>
        <form method="GET" action="{{ route('blog.index') }}" style="position:relative; width:240px; flex-shrink:0; padding:8px 0;">
            <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--muted);">
                <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="8" cy="8" r="5"/><path d="M13 13l3 3"/></svg>
            </span>
            <input name="search" value="{{ $search ?? '' }}" placeholder="Search posts…" style="padding-left:32px; font-size:12.5px; height:32px; width:100%;">
        </form>
    </div>
</div>

{{-- ── FEATURED POST ──────────────────────────────────────────── --}}
@if($featured)
@php
    $fdata = $featured->secs[0] ?? [];
    $ftag  = $fdata['tag'] ?? 'Featured';
@endphp
<section style="padding:48px 40px 24px;">
    <div style="max-width:1200px; margin:0 auto;">
        <div class="card featured-post-grid" style="padding:0; overflow:hidden; display:grid; grid-template-columns:1.1fr 1fr; gap:0;">
            {{-- Gradient cover --}}
            <div style="min-height:340px; background:linear-gradient(135deg,#2f54eb 0%,#4C1D95 100%); display:flex; align-items:flex-end; padding:32px; position:relative; overflow:hidden;">
                @if(!empty($fdata['image']))
                <img src="{{ fileUrl($fdata['image']) }}" alt="" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0.3;">
                @endif
                <div style="position:relative; z-index:1;">
                    <span style="display:inline-block; padding:4px 10px; background:rgba(255,255,255,0.18); border-radius:20px; font-size:10.5px; font-weight:600; color:white; text-transform:uppercase; letter-spacing:0.06em;">{{ $ftag }}</span>
                </div>
            </div>
            {{-- Content --}}
            <div style="padding:40px; display:flex; flex-direction:column; justify-content:center;">
                <div style="display:flex; gap:10px; align-items:center; margin-bottom:16px; flex-wrap:wrap;">
                    <span class="chip" style="background:var(--accent-soft); color:var(--accent); border-color:transparent; font-size:10.5px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase;">Featured</span>
                    <span class="chip" style="font-size:10.5px;">{{ $ftag }}</span>
                </div>
                <h2 style="font-size:clamp(20px,2.5vw,34px); font-weight:600; letter-spacing:-1px; line-height:1.1; margin:0 0 16px; color:var(--text);">{{ $featured->name }}</h2>
                @if(!empty($fdata['excerpt']))
                <p style="font-size:15px; color:var(--muted); line-height:1.6; margin:0 0 24px;">{{ Str::limit($fdata['excerpt'], 160) }}</p>
                @endif
                <div style="display:flex; align-items:center; gap:12px; padding-top:20px; border-top:1px solid var(--border); flex-wrap:wrap;">
                    <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#2f54eb,#60A5FA); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:13px; flex-shrink:0;">
                        {{ strtoupper(substr(gs()->site_name ?? 'T', 0, 1)) }}
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:13px; font-weight:500; color:var(--text);">{{ gs()->site_name ?? config('app.name') }} Team</div>
                        <div style="font-size:11.5px; color:var(--muted);">{{ $featured->created_at->format('M d, Y') }}</div>
                    </div>
                    <a href="{{ route('blog.show', $featured->id) }}" class="btn btn-primary" style="font-size:12.5px; padding:8px 14px;">Read post →</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ── POST GRID ──────────────────────────────────────────────── --}}
<section style="padding:32px 40px 80px;">
    <div style="max-width:1200px; margin:0 auto;">
        @if($posts->isEmpty())
        <div style="text-align:center; padding:80px 24px; color:var(--muted);">
            <div style="font-size:32px; margin-bottom:12px;">📝</div>
            <div style="font-size:15px; font-weight:500; color:var(--text); margin-bottom:8px;">No posts yet</div>
            <p style="font-size:13px;">Check back soon for updates.</p>
        </div>
        @else
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:22px;" class="blog-grid">
            @foreach($rest->take(6) as $i => $p)
            @php
                $pd   = $p->secs[0] ?? [];
                $ptag = $pd['tag'] ?? 'Post';
                $grad = $gradients[$tones[$i % count($tones)]];
            @endphp
            <a href="{{ route('blog.show', $p->id) }}" style="text-decoration:none; display:block;">
                <div class="card" style="padding:0; overflow:hidden; transition:transform .14s, box-shadow .14s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(10,10,11,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="height:160px; background:{{ $grad }}; position:relative; overflow:hidden;">
                        @if(!empty($pd['image']))
                        <img src="{{ fileUrl($pd['image']) }}" alt="" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0.3;">
                        @endif
                        <div style="position:absolute; bottom:16px; left:16px;">
                            <span style="display:inline-block; padding:3px 9px; background:rgba(255,255,255,0.18); border-radius:20px; font-size:10px; font-weight:600; color:white; text-transform:uppercase; letter-spacing:0.06em;">{{ $ptag }}</span>
                        </div>
                    </div>
                    <div style="padding:20px;">
                        <h3 style="font-size:15px; font-weight:600; color:var(--text); margin:0 0 10px; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $p->name }}</h3>
                        @if(!empty($pd['excerpt']))
                        <p style="font-size:13px; color:var(--muted); line-height:1.55; margin:0 0 16px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $pd['excerpt'] }}</p>
                        @endif
                        <div style="font-size:11.5px; color:var(--muted);">{{ $p->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @if($posts->hasPages())
        <div style="margin-top:40px;">{{ $posts->links() }}</div>
        @elseif($posts->count() > 0)
        <div style="text-align:center; margin-top:40px;">
            <button class="btn" style="font-size:13px;">Load more posts</button>
        </div>
        @endif
        @endif
    </div>
</section>

{{-- ── POPULAR TOPICS ─────────────────────────────────────────── --}}
<section style="padding:60px 40px; border-top:1px solid var(--border); background:var(--bg-2);">
    <div style="max-width:1200px; margin:0 auto;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:22px;">
            <h3 style="font-size:22px; font-weight:600; letter-spacing:-0.5px; margin:0; color:var(--text);">Popular topics</h3>
            <span style="font-size:12.5px; color:var(--muted);">Updated weekly</span>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            @foreach(['Instant jobs','Getting paid','Contracts 101','Client best practices','KYC','Worker spotlight','Platform updates','API','Fraud prevention','Payouts','Mobile','Case studies','Career growth','Remote work'] as $topic)
            <span class="chip" style="padding:7px 13px; font-size:12.5px; cursor:pointer; transition:border-color .15s, color .15s;"
                  onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                  onmouseout="this.style.borderColor='';this.style.color=''">{{ $topic }}</span>
            @endforeach
        </div>
    </div>
</section>

<style>
@media (max-width:1024px) {
    .featured-post-grid { grid-template-columns: 1fr !important; }
    .blog-grid { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width:640px) {
    .blog-grid { grid-template-columns: 1fr !important; }
}
</style>

@endsection
