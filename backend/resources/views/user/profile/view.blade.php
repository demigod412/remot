@extends('user.layouts.app')
@section('title', '@' . $profile->username . ' — Profile')
@section('page-title', 'Profile')

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
    $rankColor = $rankColors[$rank['label']] ?? 'var(--accent)';

    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($revealedRatings as $rv) {
        $distribution[max(1, min(5, (int)$rv->rating))]++;
    }
@endphp

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-4);margin-bottom:24px;">
    <a href="{{ route('user.dashboard') }}" style="color:var(--fg-4);text-decoration:none;" onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">Dashboard</a>
    <i data-lucide="chevron-right" style="width:12px;height:12px;"></i>
    <span style="color:var(--fg-3);">{{ '@' . $profile->username }}</span>
</div>

{{-- ═══════════════════════════════════════════
     HERO CARD
═══════════════════════════════════════════ --}}
<div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">

    {{-- Banner --}}
    <div style="height:100px;background:linear-gradient(135deg,{{ $c1 }}28,{{ $c2 }}18,transparent);border-bottom:1px solid var(--border);position:relative;">
        <div style="position:absolute;inset:0;background-image:radial-gradient(var(--border) 1px,transparent 1px);background-size:22px 22px;"></div>
    </div>

    <div style="padding:0 24px 24px;">

        {{-- Avatar row --}}
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-top:-40px;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
            <div style="position:relative;width:fit-content;">
                <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,{{ $c1 }},{{ $c2 }});display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;color:white;border:3px solid var(--surface);box-shadow:0 4px 20px rgba(0,0,0,0.15);">
                    {{ $init }}
                </div>
                @if($profile->kyc_status == 1)
                <div style="position:absolute;bottom:2px;right:2px;width:20px;height:20px;border-radius:50%;background:#22C55E;border:2px solid var(--surface);display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="check" style="width:10px;height:10px;color:white;stroke-width:3;"></i>
                </div>
                @endif
            </div>

            {{-- Rank chip --}}
            <div style="display:inline-flex;align-items:center;gap:7px;padding:6px 13px;border-radius:20px;background:var(--surface-2);border:1px solid var(--border);">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $rankColor }};flex-shrink:0;"></span>
                <span style="font-size:13px;font-weight:600;color:var(--fg-2);">{{ $rank['icon'] }} {{ $rank['label'] }}</span>
                <span style="font-size:11px;color:var(--fg-4);">· {{ number_format($rank['score']) }} pts</span>
            </div>
        </div>

        {{-- Name + handle --}}
        <div style="margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                <h1 style="font-size:22px;font-weight:700;color:var(--fg);letter-spacing:-0.5px;margin:0;">{{ $name }}</h1>
                @if($profile->kyc_status == 1)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:500;background:rgba(34,197,94,0.1);color:#22C55E;border:1px solid rgba(34,197,94,0.2);">
                    <i data-lucide="shield-check" style="width:11px;height:11px;"></i> Verified
                </span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:14px;font-size:13px;color:var(--fg-3);flex-wrap:wrap;">
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

        {{-- Star rating bar --}}
        <div style="display:flex;align-items:center;gap:10px;padding-top:14px;border-top:1px solid var(--border);">
            <div style="display:flex;gap:2px;">
                @for($i = 1; $i <= 5; $i++)
                <span style="font-size:15px;line-height:1;color:{{ ($avgRating > 0 && $i <= round($avgRating)) ? '#F5D547' : 'var(--surface-3)' }};">★</span>
                @endfor
            </div>
            @if($avgRating > 0)
            <span style="font-size:15px;font-weight:700;color:var(--fg);">{{ number_format($avgRating, 1) }}</span>
            <span style="font-size:12px;color:var(--fg-4);">{{ $totalReviews }} {{ Str::plural('review', $totalReviews) }}</span>
            @else
            <span style="font-size:12px;color:var(--fg-4);">No reviews yet</span>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     STATS ROW
═══════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px;" class="profile-stat-grid">
    @foreach([
        ['Tasks Done',     $tasksCompleted,      'check-circle', '#22C55E', 'rgba(34,197,94,0.1)'],
        ['Works Posted',   $worksPosted,         'zap',          'var(--accent)', 'var(--accent-soft)'],
        ['Jobs Completed', $contractsAsWorker,   'briefcase',    '#F59E0B', 'rgba(245,158,11,0.1)'],
        ['Hires Made',     $contractsAsEmployer, 'users',        '#EC4899', 'rgba(236,72,153,0.1)'],
    ] as [$label, $val, $icon, $color, $bg])
    <div class="card" style="padding:18px;">
        <div style="width:34px;height:34px;border-radius:9px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i data-lucide="{{ $icon }}" style="width:16px;height:16px;color:{{ $color }};"></i>
        </div>
        <div class="mono" style="font-size:26px;font-weight:700;color:var(--fg);letter-spacing:-1px;line-height:1;margin-bottom:4px;">{{ number_format($val) }}</div>
        <div style="font-size:12px;color:var(--fg-3);">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════
     MAIN COLUMNS
═══════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start;" class="profile-main-grid">

    {{-- ── LEFT ────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Skills --}}
        @if($profile->skills && $profile->skills->count())
        <div class="card" style="padding:20px;">
            <div class="label" style="margin-bottom:12px;">Skills</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($profile->skills as $skill)
                <span class="chip" style="background:var(--accent-soft);color:var(--accent);border-color:rgba(47,84,235,0.3);">{{ $skill->name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Reviews --}}
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
                <div class="label">Reviews</div>
                @if($totalReviews > 0)
                <span style="font-size:11.5px;color:var(--fg-4);background:var(--surface-2);padding:2px 8px;border-radius:999px;border:1px solid var(--border);">{{ $totalReviews }} total</span>
                @endif
            </div>

            @forelse($revealedRatings as $r)
            @php
                $ri = strtoupper(substr($r->rater->firstname ?? $r->rater->username, 0, 1));
                $rc = $palette[ord($ri) % count($palette)];
            @endphp
            <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-bottom:1px solid var(--border);"
                 onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">

                {{-- Reviewer avatar --}}
                <div style="width:38px;height:38px;border-radius:50%;background:{{ $rc }};display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:white;flex-shrink:0;">{{ $ri }}</div>

                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:5px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <a href="{{ route('user.public-profile', $r->rater->username) }}"
                               style="font-size:13.5px;font-weight:600;color:var(--fg);text-decoration:none;"
                               onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg)'">
                                {{ $r->rater->fullname ?: $r->rater->username }}
                            </a>
                            <span style="font-size:12px;color:var(--fg-4);">{{ '@' . $r->rater->username }}</span>
                        </div>
                        <span style="font-size:11.5px;color:var(--fg-4);flex-shrink:0;">{{ $r->created_at->diffForHumans() }}</span>
                    </div>
                    {{-- Stars --}}
                    <div style="display:flex;gap:2px;margin-bottom:8px;">
                        @for($i = 1; $i <= 5; $i++)
                        <span style="font-size:13px;line-height:1;color:{{ $i <= $r->rating ? '#F5D547' : 'var(--surface-3)' }};">★</span>
                        @endfor
                    </div>
                    @if($r->review)
                    <p style="font-size:13.5px;color:var(--fg-2);line-height:1.6;margin:0;">{{ $r->review }}</p>
                    @else
                    <p style="font-size:12px;color:var(--fg-4);font-style:italic;margin:0;">No written review.</p>
                    @endif
                </div>
            </div>
            @empty
            <div style="display:flex;flex-direction:column;align-items:center;padding:56px 24px;text-align:center;">
                <div style="width:50px;height:50px;border-radius:50%;background:var(--surface-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin-bottom:14px;">
                    <i data-lucide="star" style="width:20px;height:20px;color:var(--fg-4);"></i>
                </div>
                <div style="font-size:14px;font-weight:500;color:var(--fg-3);margin-bottom:6px;">No reviews yet</div>
                <div style="font-size:12px;color:var(--fg-4);max-width:260px;line-height:1.5;">Reviews appear once both parties have rated each other after completing work.</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── RIGHT ───────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Rating score --}}
        <div class="card" style="padding:20px;">
            <div class="label" style="margin-bottom:16px;">Rating</div>
            @if($totalReviews > 0)
            <div style="text-align:center;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border);">
                <div class="mono" style="font-size:52px;font-weight:800;color:var(--fg);letter-spacing:-2px;line-height:1;">{{ number_format($avgRating, 1) }}</div>
                <div style="display:flex;justify-content:center;gap:3px;margin:6px 0 4px;">
                    @for($i = 1; $i <= 5; $i++)
                    <span style="font-size:17px;line-height:1;color:{{ $i <= round($avgRating) ? '#F5D547' : 'var(--surface-3)' }};">★</span>
                    @endfor
                </div>
                <div style="font-size:12px;color:var(--fg-4);">{{ $totalReviews }} {{ Str::plural('review', $totalReviews) }}</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:7px;">
                @foreach([5,4,3,2,1] as $star)
                @php $pct = $totalReviews > 0 ? round($distribution[$star] / $totalReviews * 100) : 0; @endphp
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:12px;color:var(--fg-4);width:8px;text-align:right;flex-shrink:0;">{{ $star }}</span>
                    <span style="font-size:11px;color:#F5D547;flex-shrink:0;">★</span>
                    <div style="flex:1;height:5px;border-radius:3px;background:var(--surface-3);overflow:hidden;">
                        <div style="width:{{ $pct }}%;height:100%;background:#F5D547;border-radius:3px;"></div>
                    </div>
                    <span style="font-size:11.5px;color:var(--fg-4);width:18px;text-align:right;flex-shrink:0;">{{ $distribution[$star] }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div style="text-align:center;padding:20px 0;">
                <div class="mono" style="font-size:42px;font-weight:800;color:var(--surface-3);line-height:1;margin-bottom:8px;">—</div>
                <div style="font-size:12px;color:var(--fg-4);">No ratings yet</div>
            </div>
            @endif
        </div>

        {{-- Activity --}}
        <div class="card" style="padding:20px;">
            <div class="label" style="margin-bottom:14px;">Activity</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach([
                    ['Tasks completed',     $tasksCompleted,      '#22C55E',        'check-circle'],
                    ['Works posted',        $worksPosted,         'var(--accent)',   'zap'],
                    ['Contracts as worker', $contractsAsWorker,   '#F59E0B',        'briefcase'],
                    ['Hires completed',     $contractsAsEmployer, '#EC4899',        'users'],
                ] as [$label, $val, $color, $icon])
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i data-lucide="{{ $icon }}" style="width:13px;height:13px;color:{{ $color }};flex-shrink:0;opacity:0.8;"></i>
                        <span style="font-size:13px;color:var(--fg-3);">{{ $label }}</span>
                    </div>
                    <span class="mono" style="font-size:13px;font-weight:600;color:var(--fg);">{{ number_format($val) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Rank --}}
        <div class="card" style="padding:18px;background:linear-gradient(135deg,{{ $rankColor }}0A,transparent);">
            <div class="label" style="margin-bottom:12px;">Rank</div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;border-radius:11px;background:{{ $rankColor }}18;border:1px solid {{ $rankColor }}30;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                    {{ $rank['icon'] }}
                </div>
                <div>
                    <div style="font-size:15px;font-weight:700;color:{{ $rankColor }};margin-bottom:2px;">{{ $rank['label'] }}</div>
                    <div style="font-size:12px;color:var(--fg-4);">{{ number_format($rank['score']) }} pts earned</div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
@media (max-width: 900px) {
    .profile-main-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 700px) {
    .profile-stat-grid { grid-template-columns: repeat(2,1fr) !important; }
}
</style>

@endsection
