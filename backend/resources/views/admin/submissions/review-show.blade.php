@extends('admin.layouts.app')
@section('title', 'Review Submission')
@section('page-title', 'Review Submission')

@section('content')

<a href="{{ route('admin.task-review.index') }}"
   style="display:inline-block;margin-bottom:16px;font-size:12.5px;color:var(--fg-3);text-decoration:none;">← Back to queue</a>

@php
    $work     = $submission->work;
    $category = $work?->category;
    // payout_usd, not coins_per_worker. TaskReviewService pays from payout_usd in
    // USD; reading the coin column here showed "0 connect" on every admin-posted task,
    // on the one screen where the figure is actually authorised.
    $gross    = (float) ($work->payout_usd ?? 0);
    $comm     = $category ? $category->calculateCommission($gross) : 0;
    $net      = $gross - $comm;
@endphp

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:16px;">

    {{-- Left: context --}}
    <div>
        <div class="jobstation-card" style="padding:22px;margin-bottom:16px;">
            <div style="font-size:20px;font-weight:600;letter-spacing:-0.4px;margin-bottom:4px;">
                {{ $work->title ?? 'Task removed' }}
            </div>
            <div style="font-size:12.5px;color:var(--fg-3);margin-bottom:18px;">
                {{ $category->name ?? 'No category' }} ·
                worker {{ $submission->worker->username ?? '#' . $submission->worker_id }}
            </div>

            <table style="width:100%;font-size:13.5px;border-collapse:collapse;">
                @foreach ([
                    'Application'      => $submission->application_status_label,
                    'Delivery'         => $submission->delivery_status_label,
                    'Fee paid'         => formatCoins($submission->fee_paid),
                    'Revisions used'   => $submission->revision_count,
                    'Task delivered'   => $submission->task_delivered_at?->toDayDateTimeString() ?? '—',
                    'Work submitted'   => $submission->submitted_at?->toDayDateTimeString() ?? '—',
                    'Deadline'         => $submission->deadline_at?->toDayDateTimeString() ?? '—',
                ] as $label => $value)
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:9px 0;color:var(--fg-3);width:160px;">{{ $label }}</td>
                        <td style="padding:9px 0;color:var(--fg);">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        {{-- Payout maths, shown before you approve anything --}}
        <div class="jobstation-card" style="padding:22px;margin-bottom:16px;">
            <div class="label" style="margin-bottom:12px;">Payout on approval</div>
            <table style="width:100%;font-size:13.5px;border-collapse:collapse;">
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:9px 0;color:var(--fg-3);">Task payout (gross)</td>
                    <td style="padding:9px 0;text-align:right;" class="mono">{{ formatUsd($gross) }}</td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:9px 0;color:var(--fg-3);">
                        Commission ({{ rtrim(rtrim(number_format($category->commission_percent ?? 0, 2), '0'), '.') }}%, inherited from category)
                    </td>
                    <td style="padding:9px 0;text-align:right;color:#F59E0B;" class="mono">− {{ formatCoins($comm) }}</td>
                </tr>
                <tr>
                    <td style="padding:9px 0;font-weight:600;">Worker receives</td>
                    <td style="padding:9px 0;text-align:right;font-weight:600;color:#22C55E;" class="mono">{{ formatUsd($net) }}</td>
                </tr>
            </table>
        </div>

        {{-- Worker's submitted result --}}
        {{-- The worker's history, on the screen where the decision is made. --}}
        @if ($performance && $submission->worker)
            @include('admin.partials.user-performance-card', [
                'user'        => $submission->worker,
                'performance' => $performance,
            ])
        @endif

        @if ($submission->proof_note || !empty($submission->proof_files))
        <div class="jobstation-card" style="padding:22px;">
            <div class="label" style="margin-bottom:12px;">Submitted result</div>

            @if ($submission->proof_note)
                <div style="font-size:13.5px;color:var(--fg-2);line-height:1.65;white-space:pre-wrap;margin-bottom:14px;">{{ $submission->proof_note }}</div>
            @endif

            @foreach ($submission->proof_files ?? [] as $i => $file)
                <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px;">
                    <i data-lucide="file-json" style="width:15px;height:15px;color:var(--fg-3);"></i>
                    <span style="color:var(--fg-2);">Result file {{ $i + 1 }}</span>
                    <a href="{{ route('secure.workProof', ['submission' => $submission->id, 'index' => $i]) }}"
                       style="margin-left:auto;font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;">Download</a>
                </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Right: actions, driven by which stage we are at --}}
    <div class="jobstation-card" style="padding:22px;height:fit-content;">

        {{-- Stage 1: decide on the application --}}
        @if ($submission->isAwaitingApplicationReview())
            <div class="label" style="margin-bottom:12px;">Approve application</div>
            <div style="font-size:12.5px;color:var(--fg-3);line-height:1.6;margin-bottom:14px;">
                Upload the task package and instructions. The worker gets
                {{ $work->review_window_hours ?? 48 }} hours from now to deliver.
            </div>

            <form method="POST" action="{{ route('admin.task-review.application.approve', $submission->id) }}"
                  enctype="multipart/form-data" style="margin-bottom:22px;">
                @csrf
                {{-- No zip upload. The questions live on the work as task_json and are
                     served straight to the console, so there is nothing to attach here.
                     Approving simply issues the annotate code. --}}
                @if (empty($submission->work?->task_json))
                    <div style="padding:11px 13px;border-radius:8px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.28);font-size:12.5px;color:#B45309;line-height:1.55;margin-bottom:12px;">
                        This task has no question file attached. Approving now gives the worker a
                        code that opens nothing &mdash; add the JSON on the task first.
                    </div>
                @else
                    <div style="font-size:12.5px;color:var(--fg-3);line-height:1.6;margin-bottom:12px;">
                        {{ $submission->work->question_count }} questions will be served from
                        <strong style="color:var(--fg-2);">{{ $submission->work->task_id }}</strong>.
                        The worker gets an annotate code and can start straight away.
                    </div>
                @endif

                <label for="task_instructions" style="display:block;font-size:12.5px;margin-bottom:6px;color:var(--fg-2);">Extra instructions (optional)</label>
                <textarea id="task_instructions" name="task_instructions" rows="3"
                          placeholder="Anything specific to this worker. The task's own instructions are already in the console."
                          style="width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;"></textarea>

                <button type="submit"
                        style="width:100%;margin-top:12px;padding:11px;border:0;border-radius:8px;background:#22C55E;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                    Approve &amp; deliver task
                </button>
            </form>

            <div style="height:1px;background:var(--border);margin:20px 0;"></div>

            <div class="label" style="margin-bottom:10px;">Reject application</div>
            <div style="font-size:12.5px;color:#F59E0B;line-height:1.6;margin-bottom:10px;">
                The {{ formatCoins($submission->fee_paid) }} application fee is
                <strong>not refunded</strong>. The slot is released for someone else.
            </div>
            <form method="POST" action="{{ route('admin.task-review.application.reject', $submission->id) }}"
                  onsubmit="return confirm('Reject this application and refund the fee?');">
                @csrf
                <textarea name="rejection_reason" rows="3" required minlength="5" maxlength="500" placeholder="Reason for the worker"
                          style="width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;"></textarea>
                <button type="submit"
                        style="width:100%;margin-top:10px;padding:11px;border:0;border-radius:8px;background:#EF4444;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                    Reject &amp; refund
                </button>
            </form>

        {{-- Stage 2: decide on the delivered work --}}
        @elseif ($submission->delivery_status === \App\Models\WorkSubmission::DEL_SUBMITTED)
            <div class="label" style="margin-bottom:12px;">Approve work</div>
            <form method="POST" action="{{ route('admin.task-review.delivery.approve', $submission->id) }}"
                  onsubmit="return confirm('Approve and pay {{ formatUsd($net) }} to this worker?');"
                  style="margin-bottom:22px;">
                @csrf
                <button type="submit"
                        style="width:100%;padding:11px;border:0;border-radius:8px;background:#22C55E;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                    Approve &amp; pay {{ formatUsd($net) }}
                </button>
            </form>

            <div style="height:1px;background:var(--border);margin:20px 0;"></div>

            <div class="label" style="margin-bottom:10px;">Request revision</div>
            <form method="POST" action="{{ route('admin.task-review.revision', $submission->id) }}" style="margin-bottom:22px;">
                @csrf
                <textarea name="revision_notes" rows="3" required minlength="10" maxlength="2000" placeholder="What needs fixing"
                          style="width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;"></textarea>
                <button type="submit"
                        style="width:100%;margin-top:10px;padding:11px;border:0;border-radius:8px;background:#F59E0B;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                    Send back for revision
                </button>
            </form>

            <div style="height:1px;background:var(--border);margin:20px 0;"></div>

            <div class="label" style="margin-bottom:10px;">Reject work</div>
            <div style="font-size:12.5px;color:var(--fg-3);line-height:1.6;margin-bottom:10px;">
                No payout, and the application fee is <strong>not</strong> refunded.
            </div>
            <form method="POST" action="{{ route('admin.task-review.delivery.reject', $submission->id) }}"
                  onsubmit="return confirm('Reject this work? No payout and no refund.');">
                @csrf
                <textarea name="rejection_reason" rows="3" required minlength="5" maxlength="500" placeholder="Reason for the worker"
                          style="width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;"></textarea>
                <button type="submit"
                        style="width:100%;margin-top:10px;padding:11px;border:0;border-radius:8px;background:#EF4444;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                    Reject work
                </button>
            </form>

        {{-- Nothing to do --}}
        @else
            <div class="label" style="margin-bottom:10px;">No action available</div>
            <div style="font-size:13px;color:var(--fg-3);line-height:1.6;">
                This submission is at stage <strong style="color:var(--fg-2);">{{ $submission->lifecycle_label }}</strong>.
                @if ($submission->delivery_status === \App\Models\WorkSubmission::DEL_NOT_STARTED)
                    Waiting for the worker to submit their result.
                @elseif ($submission->delivery_status === \App\Models\WorkSubmission::DEL_REVISION_REQUESTED)
                    Waiting for the worker to resubmit.
                @else
                    It is settled and cannot be actioned again.
                @endif
            </div>

            @if ($submission->rejection_reason)
                <div style="margin-top:16px;padding:12px 14px;border-radius:8px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);font-size:12.5px;color:var(--fg-2);">
                    {{ $submission->rejection_reason }}
                </div>
            @endif
        @endif
    </div>
</div>

@endsection
