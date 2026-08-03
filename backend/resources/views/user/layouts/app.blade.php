<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false }">
<head>
    <script>(function(){if(localStorage.getItem('jobstation-theme')==='dark')document.documentElement.classList.add('dark');})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ gs()->site_name ?? 'Job Station' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @if(gs()->custom_css)
        <style>{!! gs()->custom_css !!}</style>
    @endif
    @stack('styles')
</head>
<body style="background:var(--bg); color:var(--fg);">

{{-- Mobile overlay --}}
<div x-show="sidebarOpen" @click="sidebarOpen = false"
     x-transition.opacity
     style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:20;"
     class="lg:hidden"></div>

{{-- ===================== SIDEBAR ===================== --}}
<aside style="background:var(--bg-2); border-right:1px solid var(--border);"
       class="fixed top-0 left-0 h-full w-60 z-30 flex flex-col transform transition-transform duration-200 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    {{-- Logo --}}
    <div style="padding:18px 16px; border-bottom:1px solid var(--border); flex-shrink:0;">
        <a href="{{ route('home') }}" style="display:flex; align-items:center; gap:8px; text-decoration:none;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="var(--accent)" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="var(--accent)" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="var(--accent)" stroke-width="2"/>
            </svg>
            <span style="font-weight:600; font-size:15px; color:var(--fg); letter-spacing:-0.3px;">
                {{ gs()->site_name ?? 'Job Station' }}
            </span>
        </a>
    </div>

    {{-- Coin Balance Card --}}
    <div style="margin:12px 10px; padding:14px; border-radius:12px; background:var(--accent-soft); border:1px solid rgba(47,84,235,0.2); flex-shrink:0;">
        <div class="label" style="margin-bottom:6px;">Your Balance</div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <span class="mono" style="font-size:22px; font-weight:600; letter-spacing:-0.5px;">{{ formatCoins(auth()->user()->coin_balance) }}</span>
        </div>
        <div style="display:flex; gap:6px;">
            <a href="{{ route('user.wallet.topup') }}"
               style="flex:1; text-align:center; font-size:11.5px; padding:6px; border-radius:7px; background:var(--accent); color:white; text-decoration:none; font-weight:500;">
                + Top-up
            </a>
            <a href="{{ route('user.wallet.cashout') }}"
               style="flex:1; text-align:center; font-size:11.5px; padding:6px; border-radius:7px; background:var(--surface-2); color:var(--fg-2); text-decoration:none; font-weight:500; border:1px solid var(--border);">
                Withdraw
            </a>
        </div>
    </div>

    {{-- Navigation --}}
    <nav style="flex:1; overflow-y:auto; padding:8px 10px; display:flex; flex-direction:column; gap:2px;">
        @php
            $navSections = [
                'Work' => [
                    ['route' => 'user.dashboard',           'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
                    ['route' => 'user.browse.works',         'icon' => 'search',           'label' => 'Active Task'],
                    // The microtask lifecycle screen: application status, task package
                    // download, JSON result upload. This is where a worker actually works.
                    ['route' => 'user.tasks.index',          'icon' => 'clipboard-list',   'label' => 'My Tasks'],
                    ['route' => 'user.works.saved',          'icon' => 'bookmark',         'label' => 'Saved Works'],
                    // My Works lists gigs the user posted themselves. The whole
                    // user.works.* group sits behind feature:enable_user_gigs, so with
                    // gigs abolished this link 403s. Shown only when the flag is on.
                    ...(config('jobstation.features.enable_user_gigs') ? [
                        ['route' => 'user.works.index',      'icon' => 'briefcase',        'label' => 'My Works'],
                    ] : []),
                    ['route' => 'user.submissions.index',    'icon' => 'file-check',       'label' => 'My Submissions'],
                ],

                // The job board is abolished on this install. FeatureEnabled middleware
                // 403s these routes server-side, so linking to them unconditionally gave
                // the worker four dead links. Flip JOBSTATION_ENABLE_JOB_BOARD=true to
                // restore both the routes and this section together.
                ...(config('jobstation.features.enable_job_board') ? ['Jobs' => [
                    ['route' => 'user.jobs.browse',          'icon' => 'building-2',        'label' => 'Find Jobs'],
                    ['route' => 'user.jobs.listings.index',  'icon' => 'clipboard-list',    'label' => 'My Listings'],
                    ['route' => 'user.jobs.my-applications', 'icon' => 'send',              'label' => 'My Applications'],
                    ['route' => 'user.jobs.saved',           'icon' => 'bookmark',          'label' => 'Saved Jobs'],
                ]] : []),

                 //'Contracts' => [
                   //  ['route' => 'user.contracts.sent',       'icon' => 'file-output',       'label' => 'Contracts Sent'],
                   //  ['route' => 'user.contracts.received',   'icon' => 'file-input',        'label' => 'Contracts Received'],
                // ],
                
                'Account' => [
                    ['route' => 'user.wallet.overview',          'icon' => 'wallet',            'label' => 'Wallet'],
                    ['route' => 'user.wallet.payout-accounts',   'icon' => 'credit-card',       'label' => 'Withdrawal Accounts'],
                    ['route' => 'user.referral.index',           'icon' => 'users',             'label' => 'Referrals'],
                    ['route' => 'user.profile.kyc',              'icon' => 'shield',            'label' => 'KYC Verification'],
                    ['route' => 'user.helpdesk.index',           'icon' => 'headphones',        'label' => 'Support'],
                    ['route' => 'user.profile.settings',         'icon' => 'settings',          'label' => 'Profile & Settings'],
                ],
            ];
        @endphp

        @foreach($navSections as $section => $items)
            <div class="label" style="padding:8px 8px 4px; margin-top:4px;">{{ $section }}</div>
            @foreach($items as $item)
                @php
                    $active = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*');
                    $kycStatus = auth()->user()->kyc_status ?? 0;
                    $isKycLink = $item['route'] === 'user.profile.kyc';
                    $kycBadgeColor = match($kycStatus) { 1 => '#22C55E', 2 => '#F59E0B', default => '#EF4444' };
                    $kycIconColor  = match($kycStatus) { 1 => '#22C55E', 2 => '#F59E0B', default => '#EF4444' };
                @endphp
                <a href="{{ route($item['route']) }}"
                   class="sidebar-link {{ $active ? 'active' : '' }}">
                    <i data-lucide="{{ $item['icon'] }}"
                       style="width:16px;height:16px;flex-shrink:0;color:{{ $isKycLink ? $kycIconColor : ($active ? 'var(--accent)' : '') }};"></i>
                    {{ $item['label'] }}
                    @if($isKycLink)
                        @if($kycStatus == 1)
                        <span style="margin-left:auto;font-size:10px;font-weight:600;color:#22C55E;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);padding:1px 7px;border-radius:999px;">Verified</span>
                        @elseif($kycStatus == 2)
                        <span style="margin-left:auto;font-size:10px;font-weight:600;color:#F59E0B;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);padding:1px 7px;border-radius:999px;">Pending</span>
                        @else
                        <span style="margin-left:auto;font-size:10px;font-weight:600;color:#EF4444;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);padding:1px 7px;border-radius:999px;">Required</span>
                        @endif
                    @elseif($active)
                    <span style="margin-left:auto;width:4px;height:4px;border-radius:2px;background:var(--accent);"></span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    {{-- User Quickbar --}}
    <div style="padding:10px; border-top:1px solid var(--border); flex-shrink:0; display:flex; align-items:center; gap:10px;">
        @php
            $uName  = auth()->user()->fullname ?? auth()->user()->username;
            $uInit  = strtoupper(substr(auth()->user()->firstname ?? auth()->user()->username, 0, 1));
            $uColors = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
            $uColor  = $uColors[ord($uInit) % count($uColors)];
        @endphp
        <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,{{$uColor}},{{$uColors[(ord($uInit)+2)%count($uColors)]}});display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:12px;flex-shrink:0;">
            {{ $uInit }}
        </div>
        <div style="flex:1; min-width:0;">
            <div style="font-size:12.5px; font-weight:500; color:var(--fg); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $uName }}</div>
            <div style="font-size:10.5px; color:var(--fg-3);">{{ '@' . auth()->user()->username }}</div>
        </div>
        <form method="POST" action="{{ route('user.logout') }}">
            @csrf
            <button type="submit" title="Logout"
                    style="padding:6px;border-radius:6px;color:var(--fg-3);border:none;background:transparent;cursor:pointer;display:flex;align-items:center;"
                    onmouseover="this.style.color='#EF4444';this.style.background='rgba(239,68,68,0.1)'"
                    onmouseout="this.style.color='var(--fg-3)';this.style.background='transparent'">
                <i data-lucide="log-out" style="width:14px;height:14px;"></i>
            </button>
        </form>
    </div>
</aside>

{{-- ===================== MAIN ===================== --}}
<div class="lg:pl-60 min-h-screen flex flex-col">

    {{-- Top Bar --}}
    <header class="user-top-bar" style="position:sticky;top:0;z-index:10;background:rgba(250,250,250,0.92);backdrop-filter:blur(14px);border-bottom:1px solid var(--border);padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
        <div style="display:flex; align-items:center; gap:10px;">
            <button @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden"
                    style="padding:7px;border-radius:7px;color:var(--fg-3);border:none;background:transparent;cursor:pointer;"
                    onmouseover="this.style.background='var(--surface-2)'"
                    onmouseout="this.style.background='transparent'">
                <i data-lucide="menu" style="width:18px;height:18px;"></i>
            </button>
            <div>
                <div style="font-size:11px; color:var(--fg-3);">Worker dashboard</div>
                <div style="font-size:15px; font-weight:600; letter-spacing:-0.3px;">@yield('page-title', 'Dashboard')</div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            {{-- Dark mode toggle (only if admin enables the feature) --}}
            @if(gs()->allow_dark_mode)
            <button onclick="(function(){var d=document.documentElement.classList.toggle('dark');localStorage.setItem('jobstation-theme',d?'dark':'light');this.querySelector('[data-lucide]').setAttribute('data-lucide',d?'sun':'moon');if(window.lucide)lucide.createIcons({icons:lucide.icons});}).call(this)"
                    title="Toggle dark mode"
                    style="padding:6px;border-radius:999px;color:var(--fg-3);border:1px solid var(--border);background:var(--surface);cursor:pointer;display:flex;align-items:center;"
                    onmouseover="this.style.color='var(--fg)';this.style.borderColor='var(--border-strong)'"
                    onmouseout="this.style.color='var(--fg-3)';this.style.borderColor='var(--border)'">
                <i data-lucide="moon" style="width:14px;height:14px;"></i>
            </button>
            @endif

            {{-- Language switcher --}}
            @php $languages = \App\Models\Language::all(); @endphp
            @if($languages->count() > 1)
            <div x-data="{ open: false }" @click.outside="open = false" style="position:relative;">
                <button @click="open = !open"
                        style="display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:7px;font-size:12px;font-weight:500;color:var(--fg-3);border:1px solid var(--border);background:var(--surface);cursor:pointer;font-family:inherit;"
                        onmouseover="this.style.color='var(--fg)';this.style.borderColor='var(--border-strong)'"
                        onmouseout="this.style.color='var(--fg-3)';this.style.borderColor='var(--border)'">
                    <i data-lucide="globe" style="width:13px;height:13px;"></i>
                    {{ strtoupper(app()->getLocale()) }}
                    <svg width="9" height="5" viewBox="0 0 9 5" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 1l3.5 3.5L8 1"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition
                     style="position:absolute;right:0;top:calc(100% + 8px);background:var(--surface);border:1px solid var(--border-strong);border-radius:10px;min-width:140px;overflow:hidden;z-index:200;box-shadow:0 8px 32px rgba(0,0,0,0.25);">
                    @foreach($languages as $lang)
                    <a href="{{ route('language', $lang->code) }}"
                       style="display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;color:{{ app()->getLocale() === $lang->code ? 'var(--accent)' : 'var(--fg-2)' }};text-decoration:none;transition:.12s;font-weight:{{ app()->getLocale() === $lang->code ? '600' : '400' }};"
                       onmouseover="this.style.background='var(--surface-2)'"
                       onmouseout="this.style.background='transparent'">
                        @if($lang->icon)<span style="font-size:15px;">{{ $lang->icon }}</span>@endif
                        {{ $lang->name }}
                        @if(app()->getLocale() === $lang->code)
                        <svg style="margin-left:auto;width:12px;height:12px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- KYC badge --}}
            @if(auth()->user()->kyc_status == 0)
                <a href="{{ route('user.profile.kyc') }}"
                   class="hidden sm:flex chip"
                   style="background:rgba(245,158,11,0.12);color:#F59E0B;border-color:rgba(245,158,11,0.3);text-decoration:none;">
                    <i data-lucide="shield-alert" style="width:11px;height:11px;"></i> Verify KYC
                </a>
            @elseif(auth()->user()->kyc_status == 1)
                <span class="hidden sm:inline-flex chip"
                      style="background:rgba(34,197,94,0.12);color:#22C55E;border-color:rgba(34,197,94,0.3);">
                    <i data-lucide="shield-check" style="width:11px;height:11px;"></i> KYC Verified
                </span>
            @endif

            {{-- Notification Bell --}}
            @php
                $unreadCount  = \App\Models\UserNotification::where('user_id', auth()->id())->whereNull('read_at')->count();
                $recentNotifs = \App\Models\UserNotification::where('user_id', auth()->id())->latest()->limit(8)->get();
            @endphp
            <div x-data="{ open: false }"
                 x-effect="if (open && window.lucide) $nextTick(() => lucide.createIcons({ icons: lucide.icons }))"
                 style="position:relative;">
                <button @click="open = !open" @click.outside="open = false"
                        style="position:relative;padding:7px;border-radius:7px;color:var(--fg-3);border:none;background:transparent;cursor:pointer;"
                        onmouseover="this.style.color='var(--fg)';this.style.background='var(--surface-2)'"
                        onmouseout="this.style.color='var(--fg-3)';this.style.background='transparent'">
                    <i data-lucide="bell" style="width:18px;height:18px;display:block;"></i>
                    @if($unreadCount > 0)
                    <span style="position:absolute;top:4px;right:4px;width:7px;height:7px;border-radius:50%;background:#EF4444;"></span>
                    @endif
                </button>

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     style="position:absolute;right:0;top:calc(100%+8px);width:300px;background:var(--surface);border:1px solid var(--border-strong);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,0.4);z-index:50;overflow:hidden;">

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);">
                        <span style="font-size:13px;font-weight:600;">Notifications</span>
                        @if($unreadCount > 0)
                        <form method="POST" action="{{ route('user.notifications.read-all') }}">
                            @csrf
                            <button type="submit" style="font-size:11.5px;color:var(--accent);background:none;border:none;cursor:pointer;">Mark all read</button>
                        </form>
                        @endif
                    </div>

                    <div style="max-height:320px;overflow-y:auto;">
                        @forelse($recentNotifs as $notif)
                        @php $isUnread = is_null($notif->read_at); @endphp
                        <a href="{{ $notif->url ?? '#' }}"
                           style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);text-decoration:none;background:{{ $isUnread ? 'rgba(47,84,235,0.05)' : 'transparent' }};"
                           onmouseover="this.style.background='var(--surface-2)'"
                           onmouseout="this.style.background='{{ $isUnread ? 'rgba(47,84,235,0.05)' : 'transparent' }}'">
                            <div style="width:30px;height:30px;border-radius:50%;background:{{ $isUnread ? 'var(--accent-soft)' : 'var(--surface-2)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                                <i data-lucide="{{ $notif->icon }}" style="width:13px;height:13px;color:{{ $isUnread ? 'var(--accent)' : 'var(--fg-3)' }};"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;font-weight:500;color:var(--fg);line-height:1.35;">{{ $notif->title }}</div>
                                @if($notif->body)
                                <div style="font-size:11px;color:var(--fg-3);margin-top:2px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $notif->body }}</div>
                                @endif
                                <div style="font-size:10px;color:var(--fg-4);margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                            </div>
                            @if($isUnread)
                            <span style="width:6px;height:6px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:5px;"></span>
                            @endif
                        </a>
                        @empty
                        <div style="padding:32px;text-align:center;color:var(--fg-3);font-size:12px;">
                            <i data-lucide="bell-off" style="width:24px;height:24px;margin:0 auto 8px;display:block;opacity:0.4;"></i>
                            No notifications yet
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Flash Messages --}}
    @if(session('success') || session('error') || session('info'))
    <div style="padding:16px 24px 0;">
        @if(session('success'))
            <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#22C55E;font-size:13px;">
                <i data-lucide="check-circle" style="width:15px;height:15px;flex-shrink:0;"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#EF4444;font-size:13px;">
                <i data-lucide="alert-circle" style="width:15px;height:15px;flex-shrink:0;"></i>{{ session('error') }}
            </div>
        @endif
        @if(session('info'))
            <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(96,165,250,0.1);border:1px solid rgba(96,165,250,0.25);color:#60A5FA;font-size:13px;">
                <i data-lucide="info" style="width:15px;height:15px;flex-shrink:0;"></i>{{ session('info') }}
            </div>
        @endif
    </div>
    @endif

    {{-- Page Content --}}
    <main style="flex:1; padding:24px;">
        @yield('content')
    </main>

    <footer style="padding:16px 24px; border-top:1px solid var(--border); font-size:11px; color:var(--fg-4); text-align:center;">
        &copy; {{ date('Y') }} {{ gs()->site_name ?? 'Job Station' }}. All rights reserved.
    </footer>
</div>

@stack('scripts')
<script>
    document.addEventListener('alpine:initialized', () => {
        if (window.lucide) lucide.createIcons({ icons: lucide.icons });
    });
</script>
{{-- Active plugin embed scripts (Tawk.to live chat, etc.) --}}
{!! renderPluginScripts() !!}
</body>
</html>
