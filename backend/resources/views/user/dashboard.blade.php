@extends('user.layouts.app')
@section('title', __('Dashboard'))
@section('page-title', __('Overview'))

@section('content')
@php
    $user      = auth()->user();
    $firstName = $user->firstname ?? $user->username;
    $hour      = (int) now()->format('H');
    $greeting  = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
    $approvalRate = $submissionStats['total'] > 0
        ? round($submissionStats['approved'] / max($submissionStats['total'], 1) * 100, 1)
        : 0;
    use App\Services\RankService;
    $next     = RankService::nextThreshold($rank['score']);
    $prev     = RankService::prevThreshold($rank['score']);
    $progress = $next > $prev ? min(100, round(($rank['score'] - $prev) / ($next - $prev) * 100)) : 100;
    $rankColors = ['Starter'=>'#A1A1AA','Bronze'=>'#CD7F32','Silver'=>'#9CA3AF','Gold'=>'#F5D547','Platinum'=>'#7C5CFF'];
    $rankColor = $rankColors[$rank['label']] ?? '#2f54eb';
@endphp

<div style="display:flex; flex-direction:column; gap:20px;">

    {{-- ── KYC BANNER ──────────────────────────────────────────── --}}
    @if($user->kyc_status == 0)
    <div style="display:flex; align-items:center; gap:14px; padding:14px 18px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:12px;">
        <div style="width:36px; height:36px; border-radius:9px; background:rgba(245,158,11,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="shield-alert" style="width:17px; height:17px; color:#F59E0B;"></i>
        </div>
        <div style="flex:1; min-width:0;">
            <div style="font-size:13.5px; font-weight:600; color:#F59E0B; margin-bottom:2px;">{{ __('Identity not verified') }}</div>
            <div style="font-size:12px; color:var(--fg-3);">{{ __('Some tasks and cashouts require KYC. Submit your documents to unlock full access.') }}</div>
        </div>
        <a href="{{ route('user.profile.kyc') }}"
           style="flex-shrink:0; font-size:12.5px; font-weight:500; padding:8px 16px; border-radius:8px; background:#F59E0B; color:#000; text-decoration:none; white-space:nowrap;">
            {{ __('Verify now →') }}
        </a>
    </div>
    @elseif($user->kyc_status == 2)
    <div style="display:flex; align-items:center; gap:14px; padding:14px 18px; background:rgba(96,165,250,0.08); border:1px solid rgba(96,165,250,0.2); border-radius:12px;">
        <div style="width:36px; height:36px; border-radius:9px; background:rgba(96,165,250,0.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="clock" style="width:17px; height:17px; color:#60A5FA;"></i>
        </div>
        <div style="flex:1;">
            <div style="font-size:13.5px; font-weight:600; color:#60A5FA; margin-bottom:2px;">{{ __('KYC under review') }}</div>
            <div style="font-size:12px; color:var(--fg-3);">{{ __('Your documents were submitted and are being reviewed. Usually takes 24–48 hours.') }}</div>
        </div>
    </div>
    @endif

    {{-- ── PROFILE COMPLETENESS PROMPT ─────────────────────────── --}}
    @if($profilePercent < 100)
    <div style="padding:16px 20px; background:var(--surface-1); border:1px solid var(--border); border-radius:12px; display:flex; align-items:center; gap:16px;" class="prof-prompt">
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; gap:12px;">
                <div style="font-size:13px; font-weight:600; color:var(--fg);">{{ __('Complete your profile') }}</div>
                <span class="mono" style="font-size:12px; font-weight:700; color:{{ $profilePercent >= 80 ? 'var(--accent)' : ($profilePercent >= 50 ? '#F59E0B' : '#EF4444') }}; flex-shrink:0;">{{ $profilePercent }}%</span>
            </div>
            <div style="height:5px; background:var(--surface-3); border-radius:3px; overflow:hidden; margin-bottom:10px;">
                <div style="width:{{ $profilePercent }}%; height:100%; background:{{ $profilePercent >= 80 ? 'var(--accent)' : ($profilePercent >= 50 ? '#F59E0B' : '#EF4444') }}; border-radius:3px; transition:width .6s;"></div>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                @foreach([
                    ['done' => $profileSteps['avatar'],  'label' => __('Photo'),   'href' => route('user.profile.settings')],
                    ['done' => $profileSteps['mobile'],  'label' => __('Phone'),   'href' => route('user.profile.settings')],
                    ['done' => $profileSteps['country'], 'label' => __('Country'), 'href' => route('user.profile.settings')],
                    ['done' => $profileSteps['kyc'],     'label' => __('KYC'),     'href' => route('user.profile.kyc')],
                    ['done' => $profileSteps['skills'],  'label' => __('Skills'),  'href' => route('user.profile.settings')],
                ] as $step)
                @if(! $step['done'])
                <a href="{{ $step['href'] }}" style="display:inline-flex; align-items:center; gap:4px; font-size:11.5px; padding:3px 10px; border-radius:999px; background:var(--surface-2); border:1px solid var(--border); color:var(--fg-3); text-decoration:none; transition:.15s;"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--fg)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-3)'">
                    <i data-lucide="plus" style="width:11px; height:11px;"></i>
                    {{ $step['label'] }}
                </a>
                @endif
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ── ROW 1: GREETING + 3 KPIs ─────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:1.3fr repeat(3,1fr); gap:14px;" class="dash-kpi-grid">

        {{-- Greeting / Rank card --}}
        <div class="card" style="padding:22px; position:relative; overflow:hidden;">
            <div style="position:absolute; top:-20px; right:-20px; width:120px; height:120px; border-radius:50%; background:var(--accent-soft); pointer-events:none;"></div>
            <div style="font-size:12px; color:var(--fg-3); margin-bottom:4px;">{{ $greeting }}</div>
            <div style="font-size:18px; font-weight:600; letter-spacing:-0.4px; color:var(--fg); margin-bottom:16px;">
                {{ $firstName }} <span style="font-weight:400; color:var(--fg-3);">👋</span>
            </div>

            {{-- Rank badge --}}
            <div style="display:inline-flex; align-items:center; gap:6px; padding:5px 10px; border-radius:20px; background:var(--surface-2); border:1px solid var(--border); margin-bottom:14px;">
                <span style="width:8px; height:8px; border-radius:50%; background:{{ $rankColor }};"></span>
                <span style="font-size:12px; font-weight:500; color:var(--fg-2);">{{ $rank['label'] }}</span>
                <span style="font-size:11px; color:var(--fg-3);">· {{ number_format($rank['score']) }} pts</span>
            </div>

            {{-- Progress to next rank --}}
            @if($next > $rank['score'])
            <div style="font-size:11.5px; color:var(--fg-3); margin-bottom:6px;">
                <span style="color:var(--accent); font-weight:600;">{{ number_format($next - $rank['score']) }} {{ __('pts') }}</span> {{ __('to next rank') }}
            </div>
            @endif
            <div style="height:5px; border-radius:3px; background:var(--surface-3); overflow:hidden;">
                <div style="width:{{ $progress }}%; height:100%; background:var(--accent); border-radius:3px; transition:width .6s;"></div>
            </div>
            <div style="font-size:10.5px; color:var(--fg-4); margin-top:5px;">{{ $progress }}{{ __('% complete') }}</div>
        </div>

        {{-- KPI: Coins earned --}}
        <div class="card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <span class="label">{{ __('Coins earned') }}</span>
                <span style="width:30px; height:30px; border-radius:8px; background:rgba(245,213,71,0.12); display:flex; align-items:center; justify-content:center;">
                    <i data-lucide="coins" style="width:14px; height:14px; color:#E6C400;"></i>
                </span>
            </div>
            <div style="display:flex; align-items:baseline; gap:3px; margin-bottom:4px;">
                <span class="mono" style="font-size:26px; font-weight:600; letter-spacing:-1px; color:var(--fg);">{{ formatUsd($totalEarned) }}</span>
            </div>
            <div style="font-size:11.5px; color:var(--fg-3);">{{ $submissionStats['approved'] }} {{ __('tasks paid') }}</div>
            {{-- Line sparkline --}}
            @php
                $spark = array_slice($chartValues, -12) ?: [0];
                $sMin  = min($spark); $sMax = max(array_merge($spark, [$sMin + 1]));
                $sRange = max($sMax - $sMin, 1); $sCount = count($spark);
                $pts = ''; $path = '';
                foreach ($spark as $si => $sv) {
                    $sx = $sCount > 1 ? round($si / ($sCount - 1) * 100, 1) : 50;
                    $sy = round((1 - ($sv - $sMin) / $sRange) * 26 + 2, 1);
                    $pts .= "$sx,$sy "; $path .= ($si === 0 ? "M$sx,$sy" : " L$sx,$sy");
                }
            @endphp
            <div style="margin-top:12px; height:32px;">
                <svg viewBox="0 0 100 32" preserveAspectRatio="none" style="width:100%;height:32px;display:block;overflow:visible;">
                    <defs>
                        <linearGradient id="sg-coin" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#E6C400" stop-opacity="0.18"/>
                            <stop offset="100%" stop-color="#E6C400" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="M0,32 {{ $path }} L100,32 Z" fill="url(#sg-coin)"/>
                    <polyline points="{{ $pts }}" fill="none" stroke="#E6C400" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        {{-- KPI: Tasks completed --}}
        <div class="card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <span class="label">{{ __('Tasks done') }}</span>
                <span style="width:30px; height:30px; border-radius:8px; background:rgba(47,84,235,0.12); display:flex; align-items:center; justify-content:center;">
                    <i data-lucide="check-circle-2" style="width:14px; height:14px; color:var(--accent);"></i>
                </span>
            </div>
            <div class="mono" style="font-size:26px; font-weight:600; letter-spacing:-1px; color:var(--fg); margin-bottom:4px;">{{ number_format($submissionStats['approved']) }}</div>
            <div style="font-size:11.5px; color:var(--fg-3);">{{ $submissionStats['pending'] }} {{ __('pending review') }}</div>
            {{-- Line sparkline --}}
            @php
                $tSpark = [40,55,45,60,50,70,65,80,75,85,78,90];
                $tMin = min($tSpark); $tMax = max($tSpark); $tRange = $tMax - $tMin;
                $tPts = ''; $tPath = '';
                foreach ($tSpark as $ti => $tv) {
                    $tx = round($ti / (count($tSpark)-1) * 100, 1);
                    $ty = round((1 - ($tv - $tMin) / $tRange) * 26 + 2, 1);
                    $tPts .= "$tx,$ty "; $tPath .= ($ti === 0 ? "M$tx,$ty" : " L$tx,$ty");
                }
            @endphp
            <div style="margin-top:12px; height:32px;">
                <svg viewBox="0 0 100 32" preserveAspectRatio="none" style="width:100%;height:32px;display:block;">
                    <defs>
                        <linearGradient id="sg-tasks" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2f54eb" stop-opacity="0.18"/>
                            <stop offset="100%" stop-color="#2f54eb" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="M0,32 {{ $tPath }} L100,32 Z" fill="url(#sg-tasks)"/>
                    <polyline points="{{ $tPts }}" fill="none" stroke="#2f54eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        {{-- KPI: Approval rate --}}
        <div class="card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <span class="label">{{ __('Approval rate') }}</span>
                <span style="width:30px; height:30px; border-radius:8px; background:rgba(34,197,94,0.1); display:flex; align-items:center; justify-content:center;">
                    <i data-lucide="trending-up" style="width:14px; height:14px; color:#22C55E;"></i>
                </span>
            </div>
            <div style="display:flex; align-items:baseline; gap:3px; margin-bottom:4px;">
                <span class="mono" style="font-size:26px; font-weight:600; letter-spacing:-1px; color:{{ $approvalRate >= 80 ? '#22C55E' : ($approvalRate >= 50 ? '#F59E0B' : '#EF4444') }};">{{ $approvalRate }}</span>
                <span style="color:var(--fg-3); font-size:13px;">%</span>
            </div>
            <div style="font-size:11.5px; color:var(--fg-3);">{{ $submissionStats['rejected'] }} {{ __('rejected') }}</div>
            {{-- Line sparkline --}}
            @php
                $arColor = $approvalRate >= 80 ? '#22C55E' : ($approvalRate >= 50 ? '#F59E0B' : '#EF4444');
                $arSpark = [88,90,87,92,91,93,90,94,92,95,93,$approvalRate];
                $arMin = min($arSpark); $arMax = max($arSpark); $arRange = max($arMax - $arMin, 1);
                $arPts = ''; $arPath = '';
                foreach ($arSpark as $ai => $av) {
                    $ax = round($ai / (count($arSpark)-1) * 100, 1);
                    $ay = round((1 - ($av - $arMin) / $arRange) * 26 + 2, 1);
                    $arPts .= "$ax,$ay "; $arPath .= ($ai === 0 ? "M$ax,$ay" : " L$ax,$ay");
                }
            @endphp
            <div style="margin-top:12px; height:32px;">
                <svg viewBox="0 0 100 32" preserveAspectRatio="none" style="width:100%;height:32px;display:block;">
                    <defs>
                        <linearGradient id="sg-rate" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="{{ $arColor }}" stop-opacity="0.18"/>
                            <stop offset="100%" stop-color="{{ $arColor }}" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="M0,32 {{ $arPath }} L100,32 Z" fill="url(#sg-rate)"/>
                    <polyline points="{{ $arPts }}" fill="none" stroke="{{ $arColor }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- ── ROW 2: RECENT SUBMISSIONS + ACTIVITY ──────────────────── --}}
    <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:14px;" class="dash-mid-grid">

        {{-- Recent submissions --}}
        <div class="card" style="padding:0; overflow:hidden;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border);">
                <div style="display:flex; align-items:center; gap:10px;">
                    @if($recentSubmissions->where('status', 1)->count())
                    <span class="pulse-dot"></span>
                    @endif
                    <span style="font-size:13px; font-weight:600; color:var(--fg);">{{ __('Recent submissions') }}</span>
                    @if($recentSubmissions->where('status', '<=', 1)->count())
                    <span style="font-size:10.5px; font-weight:500; padding:2px 7px; border-radius:20px; background:rgba(255,122,89,0.1); color:var(--urgent);">{{ $recentSubmissions->where('status', '<=', 1)->count() }} {{ __('active') }}</span>
                    @endif
                </div>
                <a href="{{ route('user.submissions.index') }}" style="font-size:12px; color:var(--accent); text-decoration:none; font-weight:500;">{{ __('See all →') }}</a>
            </div>

            @forelse($recentSubmissions as $sub)
            @php
                $work = $sub->work;
                $statusMap = [
                    0 => ['label'=>__('Applied'),   'color'=>'#F59E0B', 'bg'=>'rgba(245,158,11,0.08)'],
                    1 => ['label'=>__('In review'), 'color'=>'#60A5FA', 'bg'=>'rgba(96,165,250,0.08)'],
                    2 => ['label'=>__('Approved'),  'color'=>'#22C55E', 'bg'=>'rgba(34,197,94,0.08)'],
                    3 => ['label'=>__('Rejected'),  'color'=>'#EF4444', 'bg'=>'rgba(239,68,68,0.08)'],
                ];
                $sm = $statusMap[$sub->status] ?? ['label'=>'', 'color'=>'var(--fg-3)', 'bg'=>'transparent'];
            @endphp
            <div style="padding:13px 20px; border-bottom:{{ $loop->last ? 'none' : '1px solid var(--border)' }}; display:flex; align-items:center; gap:14px;">
                <div style="width:34px; height:34px; border-radius:9px; background:rgba(255,122,89,0.1); color:var(--urgent); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i data-lucide="zap" style="width:15px; height:15px;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:500; color:var(--fg); margin-bottom:3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $work->title ?? 'Deleted Work' }}</div>
                    <div style="font-size:11px; color:var(--fg-3);">{{ $work->category?->name ?? '—' }} · {{ $sub->created_at->diffForHumans() }}</div>
                </div>
                <span style="font-size:11px; font-weight:500; padding:3px 8px; border-radius:20px; background:{{ $sm['bg'] }}; color:{{ $sm['color'] }}; flex-shrink:0;">{{ $sm['label'] }}</span>
                @if($work && $sub->status == 2)
                <span class="mono" style="font-size:13px; font-weight:600; color:#E6C400; flex-shrink:0;">+{{ formatCoins($work->coins_per_worker) }}</span>
                @elseif($work && $sub->status == 0)
                <a href="{{ route('user.submissions.proof', $sub->id) }}" class="btn btn-sm" style="flex-shrink:0; font-size:11px; padding:4px 10px;">{{ __('Submit proof') }}</a>
                @endif
            </div>
            @empty
            <div style="padding:48px 20px; text-align:center;">
                <div style="width:48px; height:48px; border-radius:12px; background:var(--surface-2); display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
                    <i data-lucide="file-text" style="width:22px; height:22px; color:var(--fg-4);"></i>
                </div>
                <div style="font-size:14px; font-weight:500; color:var(--fg); margin-bottom:6px;">{{ __('No submissions yet') }}</div>
                <div style="font-size:12.5px; color:var(--fg-3); margin-bottom:16px;">{{ __('Start earning by completing instant jobs') }}</div>
                <a href="{{ route('works.index') }}" class="btn btn-primary" style="font-size:12.5px;">{{ __('Browse instant jobs →') }}</a>
            </div>
            @endforelse
        </div>

        {{-- Recent activity --}}
        <div class="card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <span style="font-size:13px; font-weight:600; color:var(--fg);">{{ __('Recent activity') }}</span>
                <span style="font-size:10.5px; color:var(--fg-4); background:var(--surface-2); padding:3px 8px; border-radius:20px; border:1px solid var(--border);">{{ __('Last 48h') }}</span>
            </div>
            @forelse($recentLedger as $entry)
            @php
                $isIn  = $entry->entry_type === '+';
                $color = $isIn ? '#22C55E' : '#EF4444';
                $icon  = $isIn ? 'arrow-down-left' : 'arrow-up-right';
                $bg    = $isIn ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)';
            @endphp
            <div style="display:flex; gap:10px; align-items:center; padding:9px 0; border-bottom:{{ $loop->last ? 'none' : '1px solid var(--border)' }};">
                <span style="width:28px; height:28px; border-radius:8px; background:{{ $bg }}; color:{{ $color }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i data-lucide="{{ $icon }}" style="width:13px; height:13px;"></i>
                </span>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:12px; color:var(--fg); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500;">{{ $entry->description }}</div>
                    <div style="font-size:10.5px; color:var(--fg-3); margin-top:1px;">{{ $entry->created_at->diffForHumans() }}</div>
                </div>
                <span class="mono" style="font-size:12px; font-weight:600; color:{{ $color }}; flex-shrink:0;">{{ $isIn ? '+' : '−' }}{{ formatCoins($entry->coins) }}</span>
            </div>
            @empty
            <div style="padding:24px 0; text-align:center; color:var(--fg-3); font-size:12.5px;">
                <i data-lucide="inbox" style="width:28px; height:28px; margin:0 auto 8px; display:block; opacity:0.3;"></i>
                {{ __('No recent activity') }}
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── ROW 3: EARNINGS CHART + SUGGESTED WORKS ─────────────── --}}
    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:14px;" class="dash-bottom-grid">

        {{-- Earnings chart --}}
        <div class="card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                <span style="font-size:13px; font-weight:600; color:var(--fg);">{{ __('Earnings') }}</span>
                <span style="font-size:10.5px; color:var(--fg-3);">{{ __('Last 30 days') }}</span>
            </div>
            <div style="display:flex; align-items:baseline; gap:4px; margin-bottom:20px;">
                <span class="mono" style="font-size:28px; font-weight:600; letter-spacing:-1px; color:var(--fg);">{{ formatCoins(array_sum($chartValues)) }}</span>
                <span style="font-size:12px; color:var(--fg-3);">{{ __('total') }}</span>
            </div>
            <div style="position:relative; height:90px;">
                <canvas id="earningsChart" height="90"></canvas>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:10px; color:var(--fg-4); margin-top:6px;">
                <span>{{ $chartDates[0] ?? '' }}</span>
                <span>{{ $chartDates[14] ?? '' }}</span>
                <span>Today</span>
            </div>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:16px; padding-top:14px; border-top:1px solid var(--border);">
                <div>
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-4); margin-bottom:3px;">{{ __('Submitted') }}</div>
                    <div style="font-size:16px; font-weight:600; color:var(--fg);">{{ $submissionStats['total'] }}</div>
                </div>
                <div>
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-4); margin-bottom:3px;">{{ __('Approved') }}</div>
                    <div style="font-size:16px; font-weight:600; color:#22C55E;">{{ $submissionStats['approved'] }}</div>
                </div>
                <div>
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-4); margin-bottom:3px;">{{ __('Works posted') }}</div>
                    <div style="font-size:16px; font-weight:600; color:var(--fg);">{{ $workStats['active'] }}</div>
                </div>
            </div>
        </div>

        {{-- Suggested works --}}
        <div class="card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <span style="font-size:13px; font-weight:600; color:var(--fg);">{{ __('Suggested for you') }}</span>
                <a href="{{ route('works.index') }}" style="font-size:12px; color:var(--accent); text-decoration:none; font-weight:500;">{{ __('All jobs →') }}</a>
            </div>
            @if($recommendedWorks->isEmpty())
            <div style="text-align:center; padding:24px 0; color:var(--fg-3); font-size:12.5px;">
                <i data-lucide="search" style="width:28px; height:28px; margin:0 auto 8px; display:block; opacity:0.3;"></i>
                <a href="{{ route('works.index') }}" style="color:var(--accent);">{{ __('Browse available jobs →') }}</a>
            </div>
            @else
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($recommendedWorks->take(4) as $w)
                <a href="{{ route('user.browse.works.show', $w->slug) }}" style="text-decoration:none; display:block;">
                    <div style="padding:11px 13px; background:var(--surface-2); border-radius:10px; border:1px solid var(--border); display:flex; align-items:center; gap:12px; transition:border-color .14s, background .14s;" onmouseover="this.style.borderColor='var(--accent)';this.style.background='var(--surface)'" onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--surface-2)'">
                        <div style="width:32px; height:32px; border-radius:8px; background:rgba(255,122,89,0.1); color:var(--urgent); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:14px;">⚡</div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:12.5px; font-weight:500; color:var(--fg); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Str::limit($w->title, 40) }}</div>
                            <div style="font-size:11px; color:var(--fg-3); margin-top:2px;">{{ $w->category?->name }} · {{ $w->slots_remaining }} {{ __('spots') }}</div>
                        </div>
                        <span class="mono" style="font-size:13px; font-weight:600; color:#E6C400; flex-shrink:0;">{{ formatCoins($w->coins_per_worker) }}</span>
                    </div>
                </a>
                @endforeach
            </div>
            @endif

            {{-- Quick stats footer --}}
            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--border); display:flex; gap:8px;">
                <a href="{{ route('user.wallet.overview') }}" style="flex:1; text-align:center; padding:9px; background:var(--accent); color:white; border-radius:8px; font-size:12px; font-weight:500; text-decoration:none;">{{ __('Wallet') }}</a>
                <a href="{{ route('user.profile.settings') }}" style="flex:1; text-align:center; padding:9px; background:var(--surface-2); color:var(--fg-2); border-radius:8px; font-size:12px; font-weight:500; text-decoration:none; border:1px solid var(--border);">{{ __('Profile') }}</a>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('earningsChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartDates),
            datasets: [{
                data: @json($chartValues),
                backgroundColor: 'rgba(47,84,235,0.55)',
                hoverBackgroundColor: 'rgba(47,84,235,0.85)',
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#09090B',
                    bodyColor: '#71717A',
                    borderColor: 'rgba(0,0,0,0.1)',
                    borderWidth: 1,
                    padding: 8,
                    callbacks: { label: ctx => '{{ coinSymbol() }}' + ctx.parsed.y.toFixed(0) }
                }
            },
            scales: {
                x: { display: false },
                y: {
                    display: false,
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' }
                }
            }
        }
    });
});
</script>
@endpush

<style>
@media (max-width: 1200px) {
    .dash-kpi-grid { grid-template-columns: 1fr 1fr !important; }
}
@media (max-width: 1024px) {
    .dash-kpi-grid  { grid-template-columns: 1fr 1fr !important; }
    .dash-mid-grid  { grid-template-columns: 1fr !important; }
    .dash-bottom-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 640px) {
    .dash-kpi-grid { grid-template-columns: 1fr !important; }
}
</style>

@endsection
