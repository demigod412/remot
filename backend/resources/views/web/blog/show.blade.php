@extends('web.layouts.app')

@php
    $data    = $post->secs[0] ?? [];
    $tag     = $data['tag'] ?? 'Post';
    $excerpt = $data['excerpt'] ?? '';
    $content = $data['content'] ?? '';
    $tones   = ['violet','sky','emerald','amber','rose','orange'];
    $gradients = [
        'violet'  => 'linear-gradient(135deg,#2f54eb 0%,#4C1D95 100%)',
        'sky'     => 'linear-gradient(135deg,#60A5FA 0%,#1E3A8A 100%)',
        'emerald' => 'linear-gradient(135deg,#34D399 0%,#065F46 100%)',
        'amber'   => 'linear-gradient(135deg,#F59E0B 0%,#92400E 100%)',
        'rose'    => 'linear-gradient(135deg,#FB7185 0%,#9F1239 100%)',
        'orange'  => 'linear-gradient(135deg,#FB923C 0%,#92400E 100%)',
    ];
    $grad = $gradients['violet'];
@endphp

@section('title', $post->name . ' — ' . (gs()->site_name ?? config('app.name')))
@section('meta_description', $excerpt ?: Str::limit(strip_tags($content), 160))

@section('content')

{{-- ── ARTICLE HEADER ─────────────────────────────────────────── --}}
<article style="padding:60px 40px 0;">
    <div style="max-width:760px; margin:0 auto;">
        {{-- Breadcrumb --}}
        <div style="font-size:12.5px; color:var(--muted); margin-bottom:20px; display:flex; gap:6px; align-items:center;">
            <a href="{{ route('blog.index') }}" style="color:var(--muted); text-decoration:none;">Blog</a>
            <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
            <span>{{ $tag }}</span>
        </div>

        {{-- Chips --}}
        <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
            <span class="chip" style="background:var(--accent-soft); color:var(--accent); border-color:transparent; font-size:10.5px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase;">{{ $tag }}</span>
        </div>

        {{-- Title --}}
        <h1 style="font-size:clamp(28px,4vw,48px); font-weight:600; letter-spacing:-1.8px; line-height:1.08; margin:0 0 20px; color:var(--text);">
            {{ $post->name }}
        </h1>

        @if($excerpt)
        <p style="font-size:19px; color:var(--muted); line-height:1.5; margin:0 0 36px;">
            {{ $excerpt }}
        </p>
        @endif

        {{-- Byline --}}
        <div style="display:flex; align-items:center; gap:14px; padding-bottom:36px; border-bottom:1px solid var(--border); flex-wrap:wrap;">
            <div style="width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,#2f54eb,#60A5FA); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:16px; flex-shrink:0;">
                {{ strtoupper(substr(gs()->site_name ?? 'T', 0, 1)) }}
            </div>
            <div style="flex:1;">
                <div style="font-size:14px; font-weight:500; color:var(--text);">{{ gs()->site_name ?? config('app.name') }} Team</div>
                <div style="font-size:12px; color:var(--muted);">{{ $post->created_at->format('M d, Y') }}</div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button onclick="navigator.clipboard.writeText(window.location.href).then(()=>this.textContent='Copied!')" class="btn" style="font-size:12px; padding:6px 12px;">Copy link</button>
            </div>
        </div>
    </div>
</article>

{{-- ── COVER ──────────────────────────────────────────────────── --}}
<div style="max-width:960px; margin:36px auto 0; padding:0 40px;">
    <div style="border-radius:16px; overflow:hidden; height:320px; background:{{ $grad }}; position:relative;">
        @if(!empty($data['image']))
        <img src="{{ fileUrl($data['image']) }}" alt="{{ $post->name }}" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0.4;">
        @endif
        <div style="position:absolute; inset:0; display:flex; align-items:flex-end; padding:32px;">
            <span style="display:inline-block; padding:4px 12px; background:rgba(255,255,255,0.18); border-radius:20px; font-size:11px; font-weight:600; color:white; text-transform:uppercase; letter-spacing:0.06em;">{{ $tag }}</span>
        </div>
    </div>
</div>

{{-- ── BODY — 2-COL WITH TOC ──────────────────────────────────── --}}
<div style="max-width:1100px; margin:48px auto 80px; padding:0 40px; display:grid; grid-template-columns:200px 1fr; gap:60px;" class="article-layout">

    {{-- TOC Sidebar --}}
    <aside style="position:sticky; top:96px; align-self:start;">
        <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); margin-bottom:14px;">Contents</div>
        <div style="display:flex; flex-direction:column; gap:10px; font-size:12.5px; border-left:1px solid var(--border);" id="toc-list">
        </div>

        <div style="margin-top:36px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); margin-bottom:12px;">Share</div>
            <div style="display:flex; gap:6px;">
                @foreach(['X','Li','Rd','Fb'] as $s)
                <span style="width:28px; height:28px; border-radius:7px; background:var(--bg-2); border:1px solid var(--border); display:inline-flex; align-items:center; justify-content:center; font-size:10.5px; font-weight:600; color:var(--muted); cursor:pointer;" onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'" onmouseout="this.style.borderColor='';this.style.color=''">{{ $s }}</span>
                @endforeach
            </div>
        </div>
    </aside>

    {{-- Article body --}}
    <main class="article-body">
        @if($content)
        <div class="prose-content">
            {!! $content !!}
        </div>
        @else
        <p style="color:var(--muted); font-size:15px; line-height:1.72;">No content yet.</p>
        @endif

        {{-- Tags --}}
        @if(!empty($data['tag']))
        <div style="margin-top:32px; display:flex; gap:8px; flex-wrap:wrap;">
            <span class="chip" style="font-size:11.5px;">#{{ $data['tag'] }}</span>
        </div>
        @endif

        {{-- Author card --}}
        <div class="card" style="padding:24px; margin-top:40px; display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
            <div style="width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#2f54eb,#60A5FA); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:20px; flex-shrink:0;">
                {{ strtoupper(substr(gs()->site_name ?? 'T', 0, 1)) }}
            </div>
            <div style="flex:1;">
                <div style="font-size:15px; font-weight:600; color:var(--text);">{{ gs()->site_name ?? config('app.name') }} Team</div>
                <div style="font-size:12px; color:var(--muted); margin-bottom:6px;">The team behind {{ gs()->site_name ?? config('app.name') }}</div>
                <div style="font-size:13px; color:var(--muted); line-height:1.5;">Product updates, worker stories, engineering deep-dives, and the numbers behind the marketplace.</div>
            </div>
        </div>

        {{-- Back to blog --}}
        <div style="margin-top:40px; padding-top:32px; border-top:1px solid var(--border);">
            <a href="{{ route('blog.index') }}" style="font-size:13px; color:var(--accent); text-decoration:none;">← Back to Blog</a>
        </div>
    </main>
</div>

{{-- ── RELATED POSTS ──────────────────────────────────────────── --}}
@if($recent->isNotEmpty())
<section style="padding:60px 40px; border-top:1px solid var(--border); background:var(--bg-2);">
    <div style="max-width:1200px; margin:0 auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h3 style="font-size:22px; font-weight:600; letter-spacing:-0.5px; margin:0; color:var(--text);">Keep reading</h3>
            <a href="{{ route('blog.index') }}" style="font-size:13px; color:var(--accent); text-decoration:none;">All posts →</a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:22px;" class="related-grid">
            @foreach($recent->take(3) as $i => $r)
            @php
                $rd   = $r->secs[0] ?? [];
                $rtag = $rd['tag'] ?? 'Post';
                $rgrad = ['linear-gradient(135deg,#60A5FA 0%,#1E3A8A 100%)','linear-gradient(135deg,#34D399 0%,#065F46 100%)','linear-gradient(135deg,#F59E0B 0%,#92400E 100%)'][$i % 3];
            @endphp
            <a href="{{ route('blog.show', $r->id) }}" style="text-decoration:none; display:block;">
                <div class="card" style="padding:0; overflow:hidden; transition:transform .14s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                    <div style="height:140px; background:{{ $rgrad }}; position:relative; overflow:hidden;">
                        @if(!empty($rd['image']))
                        <img src="{{ fileUrl($rd['image']) }}" alt="" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0.3;">
                        @endif
                        <div style="position:absolute; bottom:12px; left:12px;">
                            <span style="display:inline-block; padding:2px 8px; background:rgba(255,255,255,0.18); border-radius:20px; font-size:9.5px; font-weight:600; color:white; text-transform:uppercase; letter-spacing:0.05em;">{{ $rtag }}</span>
                        </div>
                    </div>
                    <div style="padding:16px 18px;">
                        <h4 style="font-size:14px; font-weight:600; color:var(--text); margin:0 0 8px; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $r->name }}</h4>
                        <div style="font-size:11px; color:var(--muted);">{{ $r->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<style>
.prose-content p { font-size: 16px; line-height: 1.72; color: var(--muted); margin: 0 0 22px; }
.prose-content h2 { font-size: 26px; font-weight: 600; letter-spacing: -0.6px; margin: 44px 0 16px; color: var(--text); }
.prose-content h3 { font-size: 18px; font-weight: 600; margin: 32px 0 12px; color: var(--text); }
.prose-content ul { margin: 0 0 22px; padding-left: 20px; color: var(--muted); line-height: 1.7; }
.prose-content li { margin-bottom: 6px; font-size: 15px; }
.prose-content a { color: var(--accent); }
.prose-content blockquote { padding: 28px 32px; border-left: 3px solid var(--accent); background: var(--bg-2); border-radius: 0 12px 12px 0; margin: 36px 0; font-size: 18px; font-weight: 500; letter-spacing: -0.3px; line-height: 1.45; color: var(--text); }
.prose-content img { max-width: 100%; border-radius: 12px; margin: 24px 0; }
@media (max-width: 900px) {
    .article-layout { grid-template-columns: 1fr !important; }
    .article-layout aside { display: none; }
    .related-grid { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width: 640px) {
    .related-grid { grid-template-columns: 1fr !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const headings = document.querySelectorAll('.prose-content h2');
    const tocList  = document.getElementById('toc-list');
    if (tocList && headings.length) {
        headings.forEach(function(h, i) {
            h.id = 'section-' + i;
            const a = document.createElement('a');
            a.href = '#section-' + i;
            a.textContent = h.textContent;
            a.style.cssText = 'padding:4px 14px; color:var(--muted); border-left:2px solid transparent; margin-left:-1px; text-decoration:none; font-size:12.5px; display:block; transition:color .15s;';
            a.onmouseover = () => { a.style.color = 'var(--accent)'; };
            a.onmouseout  = () => { a.style.color = 'var(--muted)'; };
            tocList.appendChild(a);
        });
    }
});
</script>

@endsection
