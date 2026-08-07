{{--
    Worker track record and skills, in one card.

    Usage:
        @include('admin.partials.user-performance-card', [
            'user'        => $user,
            'performance' => $performance,   // WorkerReliabilityService::performance()
        ])

    Included on both admin/users/show and the Task Review detail screen, because the
    approve-or-not decision is made on the latter and walking away to look up a
    worker's history is how it stops being checked at all.

    TWO RATES, NOT ONE. They answer different questions:

      Acceptance  = of applications made, how many an admin let onto a task. Measures
                    past admin decisions, not skill. Low can just mean poor aiming.
      Approval    = of work actually delivered, how much passed review. This is the
                    one that predicts how approving them will go.

    A worker with 4 applications and 100% approval is a safer bet than one with 40
    applications and 60% approval, and showing only a single blended figure hides that.
--}}
@php
    $rateColour = function (?int $rate): string {
        if ($rate === null) return 'var(--fg-3)';
        if ($rate >= 80)    return '#22C55E';
        if ($rate >= 50)    return '#F59E0B';
        return '#EF4444';
    };
    $show = fn (?int $rate) => $rate === null ? '—' : $rate . '%';

    $skills = $user->relationLoaded('skills') ? $user->skills : $user->skills()->get();
@endphp

<div class="jobstation-card" style="padding:20px;margin-bottom:16px;">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div class="label">Track record</div>
        <span style="font-size:11.5px;color:var(--fg-3);">{{ $user->username }}</span>
    </div>

    {{-- Headline: the quality signal, all time. The number to look at first. --}}
    <div style="display:flex;align-items:baseline;gap:10px;margin-bottom:4px;">
        <span class="mono" style="font-size:34px;font-weight:600;letter-spacing:-1.2px;color:{{ $rateColour($performance['all']['approval_rate']) }};">
            {{ $show($performance['all']['approval_rate']) }}
        </span>
        <span style="font-size:12.5px;color:var(--fg-2);">work approved, all time</span>
    </div>
    <div style="font-size:11.5px;color:var(--fg-3);margin-bottom:18px;">
        {{ $performance['all']['approved'] }} of {{ $performance['all']['delivered'] }} deliveries passed review
        @if($performance['all']['delivered'] === 0)
            &middot; <span style="color:#F59E0B;">no completed work yet</span>
        @endif
    </div>

    {{-- Per period, side by side. --}}
    <div style="border:1px solid var(--border);border-radius:9px;overflow:hidden;margin-bottom:16px;">
        <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
            <thead>
                <tr style="background:var(--surface-2);color:var(--fg-3);text-align:left;">
                    <th style="padding:8px 12px;font-weight:500;"></th>
                    <th style="padding:8px 10px;font-weight:500;text-align:right;">Today</th>
                    <th style="padding:8px 10px;font-weight:500;text-align:right;">{{ now()->format('M') }}</th>
                    <th style="padding:8px 12px;font-weight:500;text-align:right;">All time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['Applied',   'applied',   null],
                    ['Accepted',  'accepted',  'acceptance_rate'],
                    ['Delivered', 'delivered', null],
                    ['Approved',  'approved',  'approval_rate'],
                ] as [$label, $field, $rateField])
                <tr style="border-top:1px solid var(--border);">
                    <td style="padding:8px 12px;color:var(--fg-2);">{{ $label }}</td>
                    @foreach (['today', 'month', 'all'] as $period)
                    <td style="padding:8px 10px;text-align:right;" class="mono">
                        <span style="color:var(--fg);">{{ $performance[$period][$field] }}</span>
                        @if($rateField)
                            <span style="color:{{ $rateColour($performance[$period][$rateField]) }};font-size:11px;">
                                ({{ $show($performance[$period][$rateField]) }})
                            </span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Things that should stop an approval, surfaced rather than left to be inferred
         from the numbers above. --}}
    @if($performance['all']['expired'] > 0 || $performance['all']['pending'] > 0)
    <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:11.5px;margin-bottom:16px;">
        @if($performance['all']['pending'] > 0)
        <span style="color:var(--fg-3);">
            <strong style="color:#F59E0B;">{{ $performance['all']['pending'] }}</strong> awaiting your review
        </span>
        @endif
        @if($performance['all']['expired'] > 0)
        <span style="color:var(--fg-3);">
            <strong style="color:#EF4444;">{{ $performance['all']['expired'] }}</strong> abandoned past deadline
        </span>
        @endif
    </div>
    @endif

    {{-- Skills. Empty is decision-relevant, so it says so rather than showing nothing. --}}
    <div style="border-top:1px solid var(--border);padding-top:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:9px;">
            <div class="label" style="margin:0;">Skills</div>
            <span style="font-size:11px;color:var(--fg-3);">{{ $skills->count() }} listed</span>
        </div>

        @forelse ($skills as $skill)
            <span style="display:inline-block;margin:0 5px 5px 0;padding:4px 9px;border-radius:6px;background:var(--surface-2);border:1px solid var(--border);font-size:11.5px;color:var(--fg-2);">
                {{ $skill->name }}
            </span>
        @empty
            <div style="font-size:12px;color:#F59E0B;line-height:1.5;">
                This worker has not listed any skills. There is nothing here to match
                against the task, so judge on the approval rate above.
            </div>
        @endforelse
    </div>
</div>
