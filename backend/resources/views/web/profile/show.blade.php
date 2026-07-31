@extends('web.layouts.app')
@section('title', $profile->username . ' — ' . (gs()->site_name ?? config('app.name')))
@section('meta_description', 'View ' . $profile->username . '\'s profile on ' . (gs()->site_name ?? config('app.name')))

@push('head')
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
@endpush

@section('content')
@php
    $init    = strtoupper(substr($profile->firstname ?? $profile->username, 0, 1));
    $palette = ['#2f54eb','#FF7A59','#60A5FA','#F59E0B','#EC4899','#8B5CF6','#06B6D4'];
    $c1      = $palette[ord($init) % count($palette)];
    $c2      = $palette[(ord($init) + 3) % count($palette)];
    $name    = trim($profile->fullname) ?: $profile->username;

    $rankColors = [
        'Newcomer' => '#A1A1AA', 'Bronze' => '#CD7F32',
        'Silver'   => '#9CA3AF', 'Gold'   => '#F5D547', 'Platinum' => '#7C5CFF',
    ];
    $rankColor = $rankColors[$rank['label']] ?? '#2f54eb';

    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($revealedRatings as $rv) {
        $distribution[max(1, min(5, (int)$rv->rating))]++;
    }
@endphp

<div style="max-width:1100px;margin:0 auto;padding:32px 24px 80px;">

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--muted);margin-bottom:24px;">
        <a href="{{ route('home') }}" style="color:var(--muted);text-decoration:none;"
           onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">Home</a>
        <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
        <span>{{ $profile->username }}</span>
    </div>

    {{-- ══════════════════════════════════════════
         HERO CARD
    ══════════════════════════════════════════ --}}
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:20px;">

        {{-- Banner --}}
        <div style="height:110px;background:linear-gradient(135deg,{{ $c1 }}30,{{ $c2 }}20,transparent);position:relative;">
            <div style="position:absolute;inset:0;background-image:radial-gradient(rgba(0,0,0,0.06) 1px,transparent 1px);background-size:22px 22px;"></div>
        </div>

        <div style="padding:0 28px 28px;">

            {{-- Avatar row --}}
            <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-top:-44px;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
                <div style="position:relative;width:fit-content;">
                    <div style="width:86px;height:86px;border-radius:50%;background:linear-gradient(135deg,{{ $c1 }},{{ $c2 }});display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;color:white;border:4px solid var(--bg-card);box-shadow:0 4px 20px rgba(0,0,0,0.12);">
                        {{ $init }}
                    </div>
                    @if($profile->kyc_status == 1)
                    <div style="position:absolute;bottom:3px;right:3px;width:22px;height:22px;border-radius:50%;background:#22C55E;border:2px solid var(--bg-card);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="check" style="width:11px;height:11px;color:white;stroke-width:3;"></i>
                    </div>
                    @endif
                </div>

                {{-- Rank badge --}}
                <div style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:20px;background:var(--bg-card);border:1px solid var(--border);box-shadow:var(--shadow-card);">
                    <span style="width:9px;height:9px;border-radius:50%;background:{{ $rankColor }};flex-shrink:0;"></span>
                    <span style="font-size:13px;font-weight:600;color:var(--text);">{{ $rank['icon'] }} {{ $rank['label'] }}</span>
                    <span style="font-size:11.5px;color:var(--muted);">· {{ number_format($rank['score']) }} pts</span>
                </div>
            </div>

            {{-- Name + handle --}}
            <div style="margin-bottom:14px;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:5px;">
                    <h1 style="font-size:24px;font-weight:700;color:var(--text);letter-spacing:-0.5px;margin:0;">{{ $name }}</h1>
                    @if($profile->kyc_status == 1)
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:500;background:rgba(34,197,94,0.1);color:#22C55E;border:1px solid rgba(34,197,94,0.22);">
                        <i data-lucide="shield-check" style="width:11px;height:11px;"></i> Verified
                    </span>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:16px;font-size:13px;color:var(--muted);flex-wrap:wrap;">
                    <span>{{ '@' . $profile->username }}</span>
                    @if($profile->country_code)
                    <span style="display:inline-flex;align-items:center;gap:4px;">
                        <i data-lucide="map-pin" style="width:12px;height:12px;"></i> {{ $profile->country_code }}
                    </span>
                    @endif
                    <span style="display:inline-flex;align-items:center;gap:4px;">
                        <i data-lucide="calendar" style="width:12px;height:12px;"></i> Joined {{ $profile->created_at->format('M Y') }}
                    </span>
                </div>
            </div>

            {{-- Star bar --}}
            <div style="display:flex;align-items:center;gap:10px;padding-top:16px;border-top:1px solid var(--border);">
                <div style="display:flex;gap:2px;">
                    @for($i = 1; $i <= 5; $i++)
                    <span style="font-size:16px;line-height:1;color:{{ ($avgRating > 0 && $i <= round($avgRating)) ? '#F5D547' : '#D4D4D8' }};">★</span>
                    @endfor
                </div>
                @if($avgRating > 0)
                <span style="font-size:16px;font-weight:700;color:var(--text);">{{ number_format($avgRating, 1) }}</span>
                <span style="font-size:12.5px;color:var(--muted);">{{ $totalReviews }} {{ Str::plural('review', $totalReviews) }}</span>
                @else
                <span style="font-size:13px;color:var(--muted);">No reviews yet</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         STATS ROW
    ══════════════════════════════════════════ --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;" class="pub-stat-grid">
        @foreach([
            ['Tasks Done',     $tasksCompleted,      'check-circle', '#22C55E',       'rgba(34,197,94,0.1)'],
            ['Works Posted',   $worksPosted,         'zap',          '#2f54eb',        'rgba(47,84,235,0.1)'],
            ['Jobs Completed', $contractsAsWorker,   'briefcase',    '#F59E0B',        'rgba(245,158,11,0.1)'],
            ['Hires Made',     $contractsAsEmployer, 'users',        '#EC4899',        'rgba(236,72,153,0.1)'],
        ] as [$label, $val, $icon, $color, $bg])
        <div class="card" style="padding:20px;">
            <div style="width:36px;height:36px;border-radius:10px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                <i data-lucide="{{ $icon }}" style="width:17px;height:17px;color:{{ $color }};"></i>
            </div>
            <div style="font-family:ui-monospace,monospace;font-size:28px;font-weight:700;color:var(--text);letter-spacing:-1px;line-height:1;margin-bottom:5px;">{{ number_format($val) }}</div>
            <div style="font-size:12.5px;color:var(--muted);">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════
         MAIN COLUMNS
    ══════════════════════════════════════════ --}}
    <div style="display:grid;grid-template-columns:1fr 310px;gap:20px;align-items:start;" class="pub-main-grid">

        {{-- ── LEFT ────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Skills --}}
            @if($profile->skills && $profile->skills->count())
            <div class="card" style="padding:22px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);margin-bottom:14px;">Skills</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    @foreach($profile->skills as $skill)
                    <span style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:20px;font-size:12.5px;font-weight:500;background:rgba(47,84,235,0.1);color:#2442c4;border:1px solid rgba(47,84,235,0.25);">{{ $skill->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Reviews --}}
            <div class="card" style="padding:0;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);">Reviews</div>
                    @if($totalReviews > 0)
                    <span style="font-size:12px;color:var(--muted);background:rgba(0,0,0,0.04);padding:3px 10px;border-radius:999px;border:1px solid var(--border);">{{ $totalReviews }} total</span>
                    @endif
                </div>

                @forelse($revealedRatings as $r)
                @php
                    $ri = strtoupper(substr($r->rater->firstname ?? $r->rater->username, 0, 1));
                    $rc = $palette[ord($ri) % count($palette)];
                @endphp
                <div style="display:flex;align-items:flex-start;gap:14px;padding:18px 22px;border-bottom:1px solid var(--border);transition:background .15s;"
                     onmouseover="this.style.background='rgba(0,0,0,0.02)'" onmouseout="this.style.background=''">

                    <div style="width:40px;height:40px;border-radius:50%;background:{{ $rc }};display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:white;flex-shrink:0;">{{ $ri }}</div>

                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <a href="{{ route('user.public-profile', $r->rater->username) }}"
                                   style="font-size:14px;font-weight:600;color:var(--text);text-decoration:none;transition:color .15s;"
                                   onmouseover="this.style.color='#2f54eb'" onmouseout="this.style.color='var(--text)'">
                                    {{ $r->rater->fullname ?: $r->rater->username }}
                                </a>
                                <span style="font-size:12.5px;color:var(--muted);">{{ '@' . $r->rater->username }}</span>
                            </div>
                            <span style="font-size:12px;color:var(--muted);flex-shrink:0;">{{ $r->created_at->diffForHumans() }}</span>
                        </div>
                        <div style="display:flex;gap:2px;margin-bottom:8px;">
                            @for($i = 1; $i <= 5; $i++)
                            <span style="font-size:14px;line-height:1;color:{{ $i <= $r->rating ? '#F5D547' : '#D4D4D8' }};">★</span>
                            @endfor
                        </div>
                        @if($r->review)
                        <p style="font-size:14px;color:var(--muted);line-height:1.65;margin:0;">{{ $r->review }}</p>
                        @else
                        <p style="font-size:13px;color:#A1A1AA;font-style:italic;margin:0;">No written review.</p>
                        @endif
                    </div>
                </div>
                @empty
                <div style="display:flex;flex-direction:column;align-items:center;padding:60px 24px;text-align:center;">
                    <div style="width:54px;height:54px;border-radius:50%;background:rgba(0,0,0,0.04);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i data-lucide="star" style="width:22px;height:22px;color:#D4D4D8;"></i>
                    </div>
                    <div style="font-size:15px;font-weight:600;color:var(--text);margin-bottom:6px;">No reviews yet</div>
                    <div style="font-size:13.5px;color:var(--muted);max-width:280px;line-height:1.6;">Reviews appear once both parties have rated each other after completing work.</div>
                </div>
                @endforelse
            </div>
        </div>

        {{-- ── RIGHT ───────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Rating breakdown --}}
            <div class="card" style="padding:22px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);margin-bottom:18px;">Rating</div>
                @if($totalReviews > 0)
                <div style="text-align:center;margin-bottom:18px;padding-bottom:18px;border-bottom:1px solid var(--border);">
                    <div style="font-family:ui-monospace,monospace;font-size:54px;font-weight:800;color:var(--text);letter-spacing:-2px;line-height:1;">{{ number_format($avgRating, 1) }}</div>
                    <div style="display:flex;justify-content:center;gap:3px;margin:8px 0 5px;">
                        @for($i = 1; $i <= 5; $i++)
                        <span style="font-size:18px;line-height:1;color:{{ $i <= round($avgRating) ? '#F5D547' : '#D4D4D8' }};">★</span>
                        @endfor
                    </div>
                    <div style="font-size:12.5px;color:var(--muted);">{{ $totalReviews }} {{ Str::plural('review', $totalReviews) }}</div>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach([5,4,3,2,1] as $star)
                    @php $pct = $totalReviews > 0 ? round($distribution[$star] / $totalReviews * 100) : 0; @endphp
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:12.5px;color:var(--muted);width:8px;text-align:right;flex-shrink:0;">{{ $star }}</span>
                        <span style="font-size:12px;color:#F5D547;flex-shrink:0;">★</span>
                        <div style="flex:1;height:6px;border-radius:3px;background:rgba(0,0,0,0.07);overflow:hidden;">
                            <div style="width:{{ $pct }}%;height:100%;background:#F5D547;border-radius:3px;transition:width .4s;"></div>
                        </div>
                        <span style="font-size:12px;color:var(--muted);width:18px;text-align:right;flex-shrink:0;">{{ $distribution[$star] }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="text-align:center;padding:20px 0;">
                    <div style="font-family:ui-monospace,monospace;font-size:44px;font-weight:800;color:#D4D4D8;line-height:1;margin-bottom:8px;">—</div>
                    <div style="font-size:13px;color:var(--muted);">No ratings yet</div>
                </div>
                @endif
            </div>

            {{-- Activity --}}
            <div class="card" style="padding:22px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);margin-bottom:16px;">Activity</div>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach([
                        ['Tasks completed',     $tasksCompleted,      '#22C55E',  'check-circle'],
                        ['Works posted',        $worksPosted,         '#2f54eb',  'zap'],
                        ['Contracts as worker', $contractsAsWorker,   '#F59E0B',  'briefcase'],
                        ['Hires completed',     $contractsAsEmployer, '#EC4899',  'users'],
                    ] as [$label, $val, $color, $icon])
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="display:flex;align-items:center;gap:9px;">
                            <i data-lucide="{{ $icon }}" style="width:14px;height:14px;color:{{ $color }};flex-shrink:0;opacity:0.85;"></i>
                            <span style="font-size:13.5px;color:var(--muted);">{{ $label }}</span>
                        </div>
                        <span style="font-family:ui-monospace,monospace;font-size:13.5px;font-weight:600;color:var(--text);">{{ number_format($val) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Rank --}}
            <div class="card" style="padding:20px;background:linear-gradient(135deg,{{ $rankColor }}0C,transparent);">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);margin-bottom:14px;">Rank</div>
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="width:48px;height:48px;border-radius:13px;background:{{ $rankColor }}18;border:1px solid {{ $rankColor }}30;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                        {{ $rank['icon'] }}
                    </div>
                    <div>
                        <div style="font-size:16px;font-weight:700;color:{{ $rankColor }};margin-bottom:3px;">{{ $rank['label'] }}</div>
                        <div style="font-size:12.5px;color:var(--muted);">{{ number_format($rank['score']) }} pts earned</div>
                    </div>
                </div>
            </div>

            {{-- CTA for guests --}}
            @guest('web')
            <div style="padding:20px;border-radius:14px;background:linear-gradient(135deg,rgba(47,84,235,0.1),rgba(47,84,235,0.04));border:1px solid rgba(47,84,235,0.2);text-align:center;">
                <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:6px;">Join Job Station</div>
                <div style="font-size:12.5px;color:var(--muted);margin-bottom:14px;line-height:1.5;">Work with {{ $profile->username }} and thousands of others</div>
                <a href="{{ route('user.register') }}"
                   style="display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#2f54eb;color:white;font-size:13.5px;font-weight:600;padding:10px 20px;border-radius:10px;text-decoration:none;transition:background .15s;"
                   onmouseover="this.style.background='#2442c4'" onmouseout="this.style.background='#2f54eb'">
                    Get Started Free
                </a>
            </div>
            @endguest

        </div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .pub-main-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 640px) {
    .pub-stat-grid { grid-template-columns: repeat(2,1fr) !important; }
}
</style>

@push('scripts')
<script>
    if (window.lucide) lucide.createIcons();
</script>
@endpush

@endsection
