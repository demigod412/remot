@extends('user.layouts.app')
@section('title', $work->title)
@section('page-title', 'Task Details')

@section('content')

<div style="margin-bottom:16px;">
    <a href="{{ route('user.browse.works') }}"
       style="font-size:12.5px; color:var(--accent); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
        <i data-lucide="arrow-left" style="width:13px; height:13px;"></i> Back to Find Work
    </a>
</div>

<div style="display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start;" class="work-detail-grid">

    {{-- ── LEFT: details ─────────────────────────────────────────── --}}
    <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
            <span style="font-size:10.5px; text-transform:uppercase; font-weight:600; padding:3px 9px; border-radius:20px; background:rgba(255,122,89,0.1); color:var(--urgent); border:1px solid rgba(255,122,89,0.2);">⚡ Instant</span>
            @if($work->category)
            <span style="font-size:11px; padding:3px 9px; border-radius:20px; background:var(--surface-2); color:var(--fg-2); border:1px solid var(--border);">{{ $work->category->name }}</span>
            @endif
            @if($work->time_limit)
            <span style="font-size:11px; padding:3px 9px; border-radius:20px; background:var(--surface-2); color:var(--fg-2); border:1px solid var(--border);">~{{ $work->time_limit }} min</span>
            @endif
        </div>

        <h1 style="font-size:24px; font-weight:600; letter-spacing:-0.5px; line-height:1.2; margin:0 0 10px; color:var(--fg);">{{ $work->title }}</h1>

        <div style="display:flex; gap:12px; align-items:center; margin-bottom:22px; font-size:12.5px; color:var(--fg-3); flex-wrap:wrap;">
            @if($poster = $work->poster)
            <span>by {{ $poster->username }}</span>
            <span style="color:var(--border-strong);">·</span>
            @endif
            <span>Posted {{ $work->created_at->diffForHumans() }}</span>
        </div>

        {{-- Description --}}
        <div class="card" style="padding:22px; margin-bottom:16px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px;">What you'll do</div>
            <div class="work-prose" style="font-size:14px; color:var(--fg-2); line-height:1.65;">
                {!! richBody($work->description) !!}
            </div>
        </div>

        {{-- Requirements --}}
        @if($work->requirements)
        <div class="card" style="padding:22px; margin-bottom:16px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px;">Requirements</div>
            <div style="display:flex; flex-direction:column; gap:9px; font-size:13.5px;">
                @foreach(explode("\n", $work->requirements) as $req)
                @if(trim($req))
                <div style="display:flex; gap:9px; align-items:flex-start;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="#22C55E" stroke-width="1.8" stroke-linecap="round" style="flex-shrink:0; margin-top:2px;"><path d="M3 9l4 4 8-8"/></svg>
                    <span style="color:var(--fg-2);">{{ trim($req) }}</span>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Proof instructions --}}
        @if($work->instructions)
        <div class="card" style="padding:22px; margin-bottom:16px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px;">Proof required</div>
            <div class="work-prose" style="font-size:13.5px; color:var(--fg-2); line-height:1.6;">
                {!! richBody($work->instructions) !!}
            </div>
        </div>
        @endif

        {{-- Similar --}}
        @if($similar->isNotEmpty())
        <div style="margin-top:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:12px;">Similar tasks</div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($similar->take(3) as $s)
                <a href="{{ route('user.browse.works.show', $s->slug) }}" style="text-decoration:none; display:block;">
                    <div class="card" style="padding:13px 16px; display:flex; align-items:center; gap:14px;">
                        <div style="width:28px; height:28px; border-radius:7px; background:rgba(255,122,89,0.12); color:var(--urgent); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px;">⚡</div>
                        <div style="flex:1; font-size:13.5px; font-weight:500; color:var(--fg);">{{ Str::limit($s->title, 60) }}</div>
                        <span class="mono" style="font-size:13px; font-weight:600; color:#E6C400; flex-shrink:0;">{{ coinSymbol() }}{{ number_format($s->coins_per_worker) }}</span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ── RIGHT: reward + start ─────────────────────────────────── --}}
    <aside style="position:sticky; top:20px;">
        <div class="card" style="padding:22px;">
            <div style="text-align:center; padding-bottom:18px; border-bottom:1px solid var(--border); margin-bottom:18px;">
                <div style="font-size:11px; color:var(--fg-3); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.08em;">Reward</div>
                {{-- Earnings are USD. The coin figure is not shown here because a
                     worker can never receive coins for work, only spend them on fees. --}}
                <div class="mono" style="font-size:34px; font-weight:600; letter-spacing:-1px; color:var(--fg);">${{ number_format($work->payout_usd, 2) }}</div>
                <div style="font-size:12px; color:var(--fg-3); margin-top:6px;">USD, paid to your earnings balance on approval</div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; margin-bottom:18px;">
                <span style="color:var(--fg-3);">Spots available</span>
                <span style="font-weight:600; color:{{ $slotsRemaining < 5 ? 'var(--urgent)' : 'var(--fg)' }};">{{ $slotsRemaining }} <span style="color:var(--fg-3); font-weight:400;">of {{ $work->worker_slots }}</span></span>
            </div>

            @if($work->requires_kyc && auth('web')->user()->kyc_status !== 1)
            <div style="font-size:12px; color:#b45309; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:9px; padding:11px 13px; margin-bottom:14px; line-height:1.5;">
                🔒 This task requires KYC verification. <a href="{{ route('user.kyc') }}" style="color:#b45309; text-decoration:underline;">Verify now →</a>
            </div>
            @endif

            @if($alreadyApplied && $userSubmission)
            {{-- Applying is not starting. Until admin approves the application and
                 delivers the task package there is nothing for the worker to do, so
                 this shows lifecycle state and never a work-start affordance. --}}
            <div style="text-align:center; padding:11px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; font-size:13px; color:var(--fg-2); font-weight:500; margin-bottom:10px;">
                {{ $userSubmission->lifecycle_label }}
            </div>
            <a href="{{ route('user.tasks.show', $userSubmission->id) }}" class="btn btn-primary" style="width:100%; justify-content:center; padding:11px; font-size:13.5px;">
                @if($userSubmission->isOpenForWorker())
                    Open task →
                @else
                    View application →
                @endif
            </a>
            @if(! $userSubmission->isApprovedToWork())
            <div style="text-align:center; font-size:11.5px; color:var(--fg-3); margin-top:10px;">
                An admin reviews your application first. You will get the task files and a deadline once it is approved.
            </div>
            @endif
            @elseif($slotsRemaining <= 0)
            <button disabled class="btn" style="width:100%; justify-content:center; padding:12px; font-size:14px; opacity:0.5; cursor:not-allowed;">No spots remaining</button>
            @else
            <form method="POST" action="{{ route('user.browse.works.start', $work->slug) }}"
                  x-data="{ sending: false }" @submit="sending = true">
                @csrf
                <button type="submit" class="btn btn-primary" x-bind:disabled="sending"
                        style="width:100%; justify-content:center; padding:12px; font-size:14px;">
                    <span x-show="!sending">Apply for this task</span>
                    <span x-show="sending" x-cloak>Submitting…</span>
                </button>
            </form>
            <div style="text-align:center; font-size:11.5px; color:var(--fg-3); margin-top:10px; line-height:1.55;">
                @if((float) $work->application_cost > 0)
                    A non-refundable application fee of
                    <strong style="color:var(--fg-2);">{{ coinSymbol() }}{{ number_format($work->application_cost, 2) }}</strong>
                    is deducted when you apply. It is only returned if an admin rejects your application.
                @else
                    Applying is free on this task.
                @endif
                Your application is reviewed before any work begins.
            </div>
            @endif
        </div>
    </aside>
</div>

<style>
.work-prose p { margin: 0 0 10px; }
.work-prose p:last-child { margin-bottom: 0; }
.work-prose h2, .work-prose h3 { font-size: 14px; font-weight: 600; color: var(--fg); margin: 14px 0 8px; }
.work-prose ol, .work-prose ul { padding-left: 18px; margin: 8px 0 10px; }
.work-prose li { margin-bottom: 5px; line-height: 1.55; }
.work-prose strong { font-weight: 600; color: var(--fg); }
.work-prose a { color: var(--accent); }
@media (max-width:980px) { .work-detail-grid { grid-template-columns: 1fr !important; } }
</style>

@endsection
