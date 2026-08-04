<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $gsSettings = gs();
        $siteName   = $gsSettings->site_name ?? config('app.name');
        $metaDesc   = trim($__env->yieldContent('meta_description'))
            ?: 'Complete micro-tasks, hire talent, run escrow contracts and earn coins on ' . $siteName . '.';
        $ogImage    = ! empty($gsSettings->logo)
            ? fileUrl(config('jobstation.upload_paths.logos'), $gsSettings->logo)
            : asset('apple-touch-icon.png');
        $canonical  = url()->current();
    @endphp

    <title>@yield('title', $siteName)</title>
    <meta name="description" content="{{ $metaDesc }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="@yield('title', $siteName)">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="@yield('og_image', $ogImage)">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $siteName)">
    <meta name="twitter:description" content="{{ $metaDesc }}">
    <meta name="twitter:image" content="@yield('og_image', $ogImage)">

    {{-- Organization structured data --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => $siteName,
        'url'      => url('/'),
        'logo'     => $ogImage,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/web.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body x-data="{ mobileOpen: false }" @keydown.escape.window="mobileOpen = false">

{{-- ── Navbar ───────────────────────────────────────────────── --}}
<nav class="navbar">
    <div class="navbar-inner">
        {{-- Logo links to #home (anchor on homepage) --}}
        <a href="{{ route('home') }}#home" class="navbar-brand">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="#2f54eb" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="#2f54eb" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="#2f54eb" stroke-width="2"/>
            </svg>
            {{ gs()->site_name ?? config('app.name') }}
        </a>

        {{-- Navigation links – all point to homepage sections (anchors) --}}
        <nav class="navbar-nav">
            <a href="{{ route('home') }}#about">{{ __('About') }}</a>
            <a href="{{ route('home') }}#how">{{ __('How It Works') }}</a>
            <a href="{{ route('home') }}#projects">{{ __('Projects') }}</a>
            <a href="{{ route('home') }}#testimonials">{{ __('Testimonials') }}</a>
            <a href="{{ route('home') }}#faq">{{ __('FAQ') }}</a>
            <a href="{{ route('home') }}#insights">{{ __('Insights') }}</a>
        </nav>

        <div class="navbar-actions">
            {{-- Coin rate widget / currency switcher --}}
            @if(gs()->show_coin_rate)
            @php
                $webCurrencies = array_values(array_filter(gs()->currencies ?? [], fn($c) => !empty($c['code']) && isset($c['rate']) && $c['rate'] > 0));
                $webDefaultCur = gs()->default_currency ?? ($webCurrencies[0]['code'] ?? '');
            @endphp
            @if(count($webCurrencies) > 0)
            <div x-data="{
                open: false,
                currencies: {{ json_encode($webCurrencies) }},
                sel: localStorage.getItem('jobstation_currency') || '{{ $webDefaultCur }}',
                init() {
                    if (!this.currencies.find(c => c.code === this.sel)) {
                        this.sel = this.currencies[0] ? this.currencies[0].code : '';
                    }
                },
                get cur() { return this.currencies.find(c => c.code === this.sel) || this.currencies[0] || {code:'',name:'',symbol:'',rate:0}; },
                fmt(r) { const n = Number(r); if (isNaN(n)) return '—'; return Number.isInteger(n) ? n.toLocaleString() : n.toFixed(2); },
                pick(code) { this.sel = code; localStorage.setItem('jobstation_currency', code); this.open = false; }
            }" @click.outside="open = false" style="position:relative;">
                <button @click="open = !open" type="button"
                        style="display:flex;align-items:center;gap:5px;padding:5px 11px;border-radius:20px;background:rgba(245,213,71,0.10);border:1px solid rgba(245,213,71,0.22);font-size:12px;font-weight:500;color:#b8960a;white-space:nowrap;cursor:pointer;font-family:inherit;line-height:1;">
                    <span style="font-family:ui-monospace,monospace;font-size:12px;color:#d97706;">{{ coinSymbol() }}1</span>
                    <span style="color:#b8960a;opacity:0.5;">=</span>
                    <span style="font-family:ui-monospace,monospace;font-size:12px;" x-text="fmt(cur.rate)"></span>
                    <span style="font-size:11px;opacity:0.8;" x-text="cur.symbol"></span>
                    <svg style="flex-shrink:0;transition:transform .15s;" :style="open ? {transform:'rotate(180deg)'} : {transform:'rotate(0deg)'}" width="8" height="5" viewBox="0 0 9 5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 1l3.5 3L8 1"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition
                     style="position:absolute;right:0;top:calc(100% + 8px);background:#fff;border:1px solid rgba(0,0,0,0.1);border-radius:12px;min-width:200px;overflow:hidden;z-index:200;box-shadow:0 8px 24px rgba(0,0,0,0.12);">
                    <div style="padding:8px 14px 6px;font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;">Select currency</div>
                    <template x-for="c in currencies" :key="c.code">
                        <button @click="pick(c.code)" type="button"
                                style="display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:9px 14px;font-size:13px;font-family:inherit;border:none;cursor:pointer;transition:background .1s;text-align:left;"
                                :style="{ background: sel===c.code ? 'rgba(47,84,235,0.08)' : 'transparent', color: sel===c.code ? '#16a34a' : '#374151', fontWeight: sel===c.code ? '600' : '400' }">
                            <span x-text="c.name" style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></span>
                            <span style="font-family:ui-monospace,monospace;font-size:11px;color:#9ca3af;flex-shrink:0;" x-text="c.symbol"></span>
                        </button>
                    </template>
                </div>
            </div>
            @elseif(gs()->coin_rate)
            <div style="display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;background:rgba(245,213,71,0.10);border:1px solid rgba(245,213,71,0.22);font-size:12px;font-weight:500;color:#b8960a;white-space:nowrap;">
                <span style="font-family:ui-monospace,monospace;font-size:13px;color:var(--coin,#F5D547);">{{ coinSymbol() }}1</span>
                <span style="color:#b8960a;opacity:0.6;">=</span>
                <span style="font-family:ui-monospace,monospace;">{{ number_format(gs()->coin_rate, gs()->coin_rate == intval(gs()->coin_rate) ? 0 : 2) }} {{ gs()->coin_rate_currency ?? gs()->cur_text }}</span>
            </div>
            @endif
            @endif

            @auth('web')
                <a href="{{ route('user.dashboard') }}" class="btn-primary-sm">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('user.login') }}" class="btn-outline-sm">{{ __('Sign in') }}</a>
                {{-- Application button (separate page) --}}
                @if(config('jobstation.features.invite_only', true))
                    <a href="{{ route('membership.apply') }}" class="btn-primary-sm">{{ __('Apply to join') }}</a>
                @elseif(gs()->registration)
                    <a href="{{ route('user.register') }}" class="btn-primary-sm">{{ __('Get started') }}</a>
                @endif
            @endauth

            {{-- Language switcher --}}
            @php $languages = \App\Models\Language::all(); @endphp
            @if($languages->count() > 1)
            <div style="position:relative" x-data="{ open:false }">
                <button @click="open=!open" class="btn-outline-sm" style="gap:6px;display:flex;align-items:center">
                    {{ strtoupper(app()->getLocale()) }}
                    <svg width="10" height="6" viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                </button>
                <div x-show="open" @click.outside="open=false" x-cloak
                     style="position:absolute;right:0;top:calc(100% + 8px);background:#fff;border:1px solid var(--border);border-radius:10px;min-width:120px;overflow:hidden;z-index:200;box-shadow:0 8px 24px rgba(10,10,11,0.1);">
                    @foreach($languages as $lang)
                        <a href="{{ route('language', $lang->code) }}"
                           style="display:block;padding:10px 16px;font-size:13px;font-weight:500;color:var(--muted);transition:.15s;"
                           onmouseover="this.style.background='rgba(10,10,11,0.04)';this.style.color='var(--text)'"
                           onmouseout="this.style.background='';this.style.color='var(--muted)'">
                            {{ $lang->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <button class="navbar-toggle" @click="mobileOpen = !mobileOpen" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

{{-- Mobile nav drawer --}}
<div x-show="mobileOpen" x-cloak
     style="position:fixed;inset:0;z-index:99;background:rgba(15,23,42,0.4);"
     @click="mobileOpen=false">
</div>
<div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-x-full"
     x-transition:enter-end="opacity-100 translate-x-0"
     style="position:fixed;top:0;left:0;bottom:0;width:280px;z-index:100;background:#fff;box-shadow:4px 0 24px rgba(15,23,42,0.12);padding:24px;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;">
        <a href="{{ route('home') }}#home" style="display:inline-flex;align-items:center;gap:8px;font-size:15px;font-weight:600;color:var(--text);text-decoration:none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="#2f54eb" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="#2f54eb" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="#2f54eb" stroke-width="2"/>
            </svg>
            {{ gs()->site_name ?? config('app.name') }}
        </a>
        <button @click="mobileOpen=false" style="background:none;border:none;cursor:pointer;padding:4px;">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 5l10 10M15 5L5 15" stroke="var(--text)" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
    </div>
    <nav style="display:flex;flex-direction:column;gap:2px;">
        <a href="{{ route('home') }}#about"  style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:var(--text);">About</a>
        <a href="{{ route('home') }}#how"    style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:var(--text);">How It Works</a>
        <a href="{{ route('home') }}#projects" style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:var(--text);">Projects</a>
        <a href="{{ route('home') }}#testimonials" style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:var(--text);">Testimonials</a>
        <a href="{{ route('home') }}#faq"     style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:var(--text);">FAQ</a>
        <a href="{{ route('home') }}#insights" style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:var(--text);">Insights</a>
    </nav>
    <div style="margin-top:24px;padding-top:24px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:8px;">
        @auth('web')
            <a href="{{ route('user.dashboard') }}" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('Dashboard') }}</a>
        @else
            <a href="{{ route('user.login') }}"    class="btn btn-secondary btn-sm" style="justify-content:center;">{{ __('Sign in') }}</a>
            @if(config('jobstation.features.invite_only', true))
            <a href="{{ route('membership.apply') }}" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('Apply to join') }}</a>
            @elseif(gs()->registration)
            <a href="{{ route('user.register') }}" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('Get started') }}</a>
            @endif
        @endauth
    </div>
</div>

{{-- Flash messages --}}
<div class="flash" id="flash-container">
    @foreach(['success','error','info','warning'] as $type)
        @if(session($type))
            <div class="flash-item flash-{{ $type === 'warning' ? 'yellow' : $type }}" onclick="this.remove()">
                {{ session($type) }}
            </div>
        @endif
    @endforeach
</div>

@yield('content')

{{-- ── Footer ───────────────────────────────────────────────── --}}







<style>
@media(max-width:900px){
    .footer-main-grid{ grid-template-columns:1fr !important; gap:40px !important; }
}

/* The cookie banner is position:fixed, bottom:24px, max-width:680px, z-index:9999.
   Centred, that puts it directly over the last element on any page whose content
   column is around the same width — including the Submit button on /apply, which it
   both hid and swallowed the clicks for.

   The class is added by the banner's own script and removed when it is dismissed, so
   the clearance exists only while something is actually covering the page. */
body.has-cookie-banner { padding-bottom: 132px; }
@media(max-width:620px){
    body.has-cookie-banner { padding-bottom: 190px; }
}</style>

{{-- Cookie consent --}}
@php $showCookie = (gs()->cookie_enabled ?? true); @endphp
@if($showCookie)
<div id="cookie-banner">
    {{-- Icon --}}
    <div style="width:36px;height:36px;border-radius:10px;background:var(--accent-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5v.01"/><path d="M16 15.5v.01"/><path d="M12 12v.01"/><path d="M11 17v.01"/><path d="M7 14v.01"/></svg>
    </div>

    {{-- Text --}}
    <div style="flex:1;min-width:0;">
        <div style="font-size:13.5px;font-weight:600;color:var(--text);margin-bottom:2px;">{{ __('We use cookies') }}</div>
        <div style="font-size:12.5px;color:var(--muted);line-height:1.55;">
            {{ gs()->cookie_text ?? __('We use cookies to improve your experience and analyse site usage.') }}
            <a href="{{ route('cookie-policy') }}"
               style="color:var(--accent);text-decoration:none;font-weight:500;"
               onmouseover="this.style.textDecoration='underline'"
               onmouseout="this.style.textDecoration='none'">{{ __('Learn more') }}</a>
        </div>
    </div>

    {{-- Actions --}}
    <div style="display:flex;gap:8px;flex-shrink:0;">
        <button id="reject-cookie" type="button" class="btn btn-secondary btn-sm">
            {{ __('Reject') }}
        </button>
        <button id="accept-cookie" type="button" class="btn btn-primary btn-sm">
            {{ __('Accept all') }}
        </button>
    </div>
</div>
<script>
(function () {
    var banner = document.getElementById('cookie-banner');
    if (!banner) return;
    if (document.cookie.split('; ').some(function (c) { return c.indexOf('cookie_consent=') === 0; })) {
        banner.remove();
        return;
    }

    // Only while the banner is really on screen, so pages are not padded for nothing.
    document.body.classList.add('has-cookie-banner');

    function decide(value) {
        document.cookie = 'cookie_consent=' + value + ';path=/;max-age=31536000;SameSite=Lax';
        banner.classList.add('hiding');
        document.body.classList.remove('has-cookie-banner');
        setTimeout(function () { banner.remove(); }, 280);
    }
    var a = document.getElementById('accept-cookie');
    var r = document.getElementById('reject-cookie');
    if (a) a.addEventListener('click', function () { decide('accepted'); });
    if (r) r.addEventListener('click', function () { decide('rejected'); });
})();
</script>
@endif

{{-- Alpine is bundled by resources/js/app.js, which @vite loads at the top of this
     file. The CDN copy that used to sit here started a SECOND instance: Alpine warns
     about multiple instances and components can initialise twice, which means event
     handlers firing twice on the same click. It also pinned an unversioned 3.x.x from
     a third party on every page load. --}}
<script>
    setTimeout(() => document.querySelectorAll('.flash-item').forEach(el => el.remove()), 5000);
</script>
@stack('scripts')
{{-- Active plugin embed scripts (Tawk.to live chat, etc.) --}}
{!! renderPluginScripts() !!}
</body>
</html>