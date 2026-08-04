@extends('web.layouts.app')

@section('title', $work->title . ' — ' . (gs()->site_name ?? config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($work->description), 155))

@if($work->cover_image)
@section('og_image', fileUrl(config('jobstation.upload_paths.work_cover'), $work->cover_image))
@endif

@section('content')

<div style="max-width:1200px; margin:0 auto; padding:28px 40px 80px;">

    {{-- Breadcrumb --}}
    <div style="font-size:12.5px; color:var(--muted); margin-bottom:20px; display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
        <a href="{{ route('works.index') }}" style="color:var(--muted); text-decoration:none;">Find work</a>
        <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
        <a href="{{ route('works.index', ['category' => $work->category_id]) }}" style="color:var(--muted); text-decoration:none;">{{ $work->category?->name ?? 'Instant jobs' }}</a>
        <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
        <span>{{ Str::limit($work->title, 40) }}</span>
    </div>

    <div style="display:grid; grid-template-columns:1fr 340px; gap:40px; align-items:start;" class="detail-grid">

        {{-- ── LEFT COLUMN ────────────────────────────────────────── --}}
        <div>
            {{-- Chips --}}
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px; flex-wrap:wrap;">
                <span class="chip chip-instant" style="font-size:10.5px; text-transform:uppercase; font-weight:600;">⚡ Instant</span>
                @if($work->category)
                <span class="chip" style="font-size:11px;">{{ $work->category->name }}</span>
                @endif
                @if($work->time_limit)
                <span class="chip" style="font-size:11px;">Takes ~{{ $work->time_limit }} min</span>
                @endif
            </div>

            {{-- Title --}}
            <h1 style="font-size:clamp(26px,3vw,40px); font-weight:600; letter-spacing:-1px; line-height:1.15; margin:0 0 16px; color:var(--text);">
                {{ $work->title }}
            </h1>

            {{-- Meta --}}
            <div style="display:flex; gap:18px; align-items:center; margin-bottom:32px; font-size:13px; color:var(--muted); flex-wrap:wrap;">
                @if($work->poster_type && $work->poster_id)
                <div style="display:flex; align-items:center; gap:8px;">
                    @php $poster = $work->poster; @endphp
                    @if($poster)
                    <a href="{{ route('user.public-profile', $poster->username) }}"
                       style="display:flex; align-items:center; gap:8px; text-decoration:none; color:inherit;">
                        <div style="width:24px; height:24px; border-radius:50%; background:linear-gradient(135deg,#2f54eb,#60A5FA); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:10px; flex-shrink:0;">
                            {{ strtoupper(substr($poster->username, 0, 1)) }}
                        </div>
                        <span style="font-weight:500; color:var(--text); transition:color .15s;"
                              onmouseover="this.style.color='#2f54eb'" onmouseout="this.style.color='var(--text)'">
                            {{ $poster->username }}
                        </span>
                    </a>
                    @if($poster->kyc_status == 1)
                    <span style="color:#22C55E; display:inline-flex; align-items:center; gap:4px; font-size:11.5px;">
                        <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 1L2 4v5c0 4 3 7 7 8 4-1 7-4 7-8V4L9 1z"/></svg>
                        Verified
                    </span>
                    @endif
                    @endif
                </div>
                <span style="color:#D4D4D8;">·</span>
                @endif
                <span>Posted {{ $work->created_at->diffForHumans() }}</span>
                @if($work->approvedSubmissions)
                <span style="color:#D4D4D8;">·</span>
                <span>{{ $work->approvedSubmissions->count() }} completed</span>
                @endif
            </div>

            {{-- Description --}}
            <div class="card" style="padding:24px; margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:14px;">What you'll do</div>
                <div class="work-prose" style="font-size:14.5px; color:var(--muted); line-height:1.65;">
                    {!! richBody($work->description) !!}
                </div>
            </div>

            {{-- Requirements --}}
            @if($work->requirements)
            <div class="card" style="padding:24px; margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:14px;">Requirements</div>
                <div style="display:flex; flex-direction:column; gap:10px; font-size:13.5px;">
                    @foreach(explode("\n", $work->requirements) as $req)
                    @if(trim($req))
                    <div style="display:flex; gap:10px; align-items:flex-start;">
                        <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="#22C55E" stroke-width="1.8" stroke-linecap="round" style="flex-shrink:0; margin-top:2px;"><path d="M3 9l4 4 8-8"/></svg>
                        <span style="color:var(--muted);">{{ trim($req) }}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Proof instructions --}}
            @if($work->instructions)
            <div class="card" style="padding:24px; margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:14px;">Proof required</div>
                <div class="work-prose" style="font-size:13.5px; color:var(--muted); line-height:1.6;">
                    {!! richBody($work->instructions) !!}
                </div>
            </div>
            @endif

            {{-- Similar works --}}
            @if($similar->isNotEmpty())
            <div style="margin-top:32px;">
                <div style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:14px;">Similar tasks</div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($similar->take(3) as $s)
                    <a href="{{ route('works.show', $s->slug) }}" style="text-decoration:none; display:block;">
                        <div class="card" style="padding:14px 18px; display:flex; align-items:center; gap:14px; transition:transform .14s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
                            <div style="width:28px; height:28px; border-radius:7px; background:rgba(255,122,89,0.12); color:#FF7A59; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px;">⚡</div>
                            <div style="flex:1; font-size:13.5px; font-weight:500; color:var(--text);">{{ Str::limit($s->title, 60) }}</div>
                            <span class="mono" style="font-size:13px; font-weight:600; color:#22C55E; flex-shrink:0;">{{ formatUsd($s->payout_usd) }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ── RIGHT SIDEBAR ───────────────────────────────────────── --}}
        <aside style="position:sticky; top:80px;">
            {{-- Reward card --}}
            <div class="card" style="padding:24px; margin-bottom:16px;">
                <div style="text-align:center; padding-bottom:22px; border-bottom:1px solid var(--border); margin-bottom:22px;">
                    <div style="font-size:11.5px; color:var(--muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.08em;">Reward</div>
                    <div style="display:inline-flex; align-items:baseline; gap:6px;">
                        {{-- The reward is USD. It used to render the coin symbol beside
                             coins_per_worker, which is 0 on every admin-posted task. --}}
                        <span class="mono" style="font-size:52px; font-weight:600; letter-spacing:-2px; line-height:1; color:var(--text);">{{ formatUsd($work->payout_usd) }}</span>
                    </div>
                    <div style="font-size:12.5px; color:var(--muted); margin-top:8px;">paid instantly on approval</div>
                </div>

                <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:24px;">
                    @if($work->time_limit)
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                        <span style="color:var(--muted);">Time to complete</span>
                        <span style="font-weight:500; color:var(--text);">~{{ $work->time_limit }} min</span>
                    </div>
                    @endif
                    {{--
                        The number of spots left is the REAL figure and is never reduced
                        by display_application_boost. Overstating scarcity to someone
                        about to spend a non-refundable fee is a different thing from a
                        soft popularity signal, and only the second one is done here.

                        The total it is shown "of" carries the same boost as the applied
                        count above, so all three figures agree arithmetically while the
                        availability claim stays true. See Work::$display_slot_total.
                    --}}
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                        <span style="color:var(--muted);">Spots available</span>
                        <span style="font-weight:600; color:{{ $slotsRemaining < 5 ? 'var(--urgent)' : 'var(--text)' }};">
                            {{ $slotsRemaining }} <span style="color:var(--muted); font-weight:400;">of {{ $work->display_slot_total }}</span>
                        </span>
                    </div>

                    {{-- Applicant count. Includes the admin display boost. --}}
                    @if($work->display_application_count > 0)
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                        <span style="color:var(--muted);">Workers applied</span>
                        <span style="font-weight:500; color:var(--text);">
                            {{ number_format($work->display_application_count) }}
                        </span>
                    </div>
                    @endif
                    @if($work->auto_approve_hours)
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                        <span style="color:var(--muted);">Review SLA</span>
                        <span style="font-weight:500; color:var(--text);">Within {{ $work->auto_approve_hours }}h</span>
                    </div>
                    @endif
                </div>

                @if($work->requires_kyc)
                <div style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px; background:rgba(245,158,11,0.07); border:1px solid rgba(245,158,11,0.25); border-radius:10px; margin-bottom:16px;">
                    <span style="font-size:16px; flex-shrink:0; margin-top:1px;">🔒</span>
                    <div>
                        <div style="font-size:12.5px; font-weight:600; color:#b45309; margin-bottom:2px;">KYC Verification Required</div>
                        <div style="font-size:11.5px; color:#92400e; line-height:1.5;">You must complete identity verification before applying to this job. <a href="{{ route('user.dashboard') }}" style="color:#b45309; text-decoration:underline;">Verify now →</a></div>
                    </div>
                </div>
                @endif

                @auth
                    @if($alreadyApplied)
                    <div style="width:100%; text-align:center; padding:12px; background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.25); border-radius:8px; font-size:13.5px; color:#22C55E; font-weight:500;">
                        ✓ Already applied
                    </div>
                    <a href="{{ route('user.submissions.index') }}" style="display:block; text-align:center; margin-top:10px; font-size:12.5px; color:var(--accent);">View submission →</a>
                    @elseif($canReapply && $slotsRemaining > 0)
                    @php
                        $timesCompleted = \App\Models\WorkSubmission::where('work_id', $work->id)
                            ->where('worker_id', auth('web')->id())
                            ->where('worker_type', 2)
                            ->where('status', 2)
                            ->count();
                    @endphp
                    <div style="width:100%; text-align:center; padding:10px; background:rgba(99,102,241,0.1); border:1px solid rgba(99,102,241,0.25); border-radius:8px; font-size:12.5px; color:#818CF8; font-weight:500; margin-bottom:10px;">
                        ✓ Completed {{ $timesCompleted }} {{ Str::plural('time', $timesCompleted) }} — do it again to earn more
                    </div>
                    <form method="POST" action="{{ route('works.apply', $work->slug) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-size:14px;">
                            ⚡ Do it again
                        </button>
                    </form>
                    <div style="text-align:center; font-size:11.5px; color:var(--muted); margin-top:12px;">Each completion earns {{ formatUsd($work->payout_usd) }}.</div>
                    @elseif($canReapply && $slotsRemaining <= 0)
                    <button disabled class="btn" style="width:100%; justify-content:center; padding:12px; font-size:14px; opacity:0.5; cursor:not-allowed;">
                        No spots remaining
                    </button>
                    @elseif($slotsRemaining > 0)
                    <form method="POST" action="{{ route('works.apply', $work->slug) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-size:14px;">
                            ⚡ Start task now
                        </button>
                    </form>
                    <div style="text-align:center; font-size:11.5px; color:var(--muted); margin-top:12px;">Timer starts when you click. Submit before the deadline.</div>
                    @else
                    <button disabled class="btn" style="width:100%; justify-content:center; padding:12px; font-size:14px; opacity:0.5; cursor:not-allowed;">
                        No spots remaining
                    </button>
                    @endif
                @else
                <a href="{{ route('user.login') }}" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-size:14px; display:flex; gap:8px; align-items:center;">
                    ⚡ Sign in to start
                </a>
                <div style="text-align:center; font-size:12px; color:var(--muted); margin-top:10px;">
                    <a href="{{ route('user.register') }}" style="color:var(--accent);">Create account</a> — it's free
                </div>
                @endauth
            </div>
        </aside>
    </div>
</div>

<style>
@media (max-width:1024px) {
    .detail-grid { grid-template-columns: 1fr !important; }
}
@media (max-width:640px) {
    .detail-grid { padding: 0 20px 60px !important; }
}
/* Prose styles for HTML work descriptions */
.work-prose p { margin: 0 0 10px; }
.work-prose p:last-child { margin-bottom: 0; }
.work-prose h3 { font-size: 14px; font-weight: 600; color: var(--text); margin: 14px 0 8px; }
.work-prose ol { padding-left: 18px; margin: 8px 0 10px; }
.work-prose ul { padding-left: 18px; margin: 8px 0 10px; }
.work-prose li { margin-bottom: 5px; line-height: 1.55; }
.work-prose strong { font-weight: 600; color: var(--text); }
</style>

@endsection
