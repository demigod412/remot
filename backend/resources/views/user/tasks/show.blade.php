@extends('user.layouts.app')
@section('title', $submission->work->title ?? __('Task'))

@section('content')
<div style="max-width:780px;margin:0 auto;padding:32px 20px;">

    <a href="{{ route('user.tasks.index') }}" style="display:inline-block;margin-bottom:16px;font-size:13px;color:var(--muted);text-decoration:none;">
        ← {{ __('Back to my tasks') }}
    </a>

    <h1 style="font-size:24px;font-weight:600;margin:0 0 6px;">{{ $submission->work->title ?? __('Task') }}</h1>
    <div style="font-size:13px;color:var(--muted);margin-bottom:24px;">
        {{ $submission->lifecycle_label }}
        @if ($submission->deadline_at)
            · {{ __('deadline') }} {{ $submission->deadline_at->toDayDateTimeString() }}
        @endif
    </div>

    @if (session('error'))
        <div style="padding:13px 15px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:20px;">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div style="padding:13px 15px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:20px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if ($submission->delivery_status === \App\Models\WorkSubmission::DEL_REVISION_REQUESTED && $submission->rejection_reason)
        <div style="padding:14px 16px;border-radius:8px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;margin-bottom:20px;">
            <strong>{{ __('Revision requested') }}</strong>
            <div style="margin-top:6px;font-size:14px;line-height:1.6;">{{ $submission->rejection_reason }}</div>
        </div>
    @endif

    {{-- Instructions --}}
    @if ($submission->task_instructions)
        <div style="border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:0.4px;">
                {{ __('Instructions') }}
            </div>
            <div style="font-size:14.5px;line-height:1.7;white-space:pre-wrap;">{{ $submission->task_instructions }}</div>
        </div>
    @endif

    {{-- Open the task.

         The zip download and the JSON upload form that used to live here are gone.
         Tasks now carry their questions as JSON and are answered in the console, so
         there is nothing to download and nothing to upload — the console posts the
         result itself.

         Legacy submissions from before the change still have task_files, so those
         are still offered rather than stranding work someone has already started. --}}

    @if (!empty($submission->task_files))
        <div style="border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:0.4px;">
                {{ __('Task files') }}
            </div>
            @foreach ($submission->task_files as $i => $file)
                <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border);font-size:14px;">
                    <span>{{ __('Package') }} {{ $i + 1 }}</span>
                    <a href="{{ route('user.tasks.files', ['id' => $submission->id, 'index' => $i]) }}"
                       style="margin-left:auto;color:var(--accent);font-weight:600;text-decoration:none;font-size:13px;">
                        {{ __('Download') }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    @if ($submission->isOpenForWorker() && $submission->annotate_code && !empty($submission->work?->task_json))
        <div style="border:1px solid var(--border);border-radius:10px;padding:22px;">
            <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:0.4px;">
                {{ __('Your task') }}
            </div>

            <p style="font-size:14px;color:var(--text);line-height:1.65;margin:0 0 6px;">
                {{ $submission->work->question_count }} {{ __('questions') }}.
                {{ __('Your answers save as you go, so you can stop and come back on any device.') }}
            </p>

            <p style="font-size:12.5px;color:var(--muted);line-height:1.6;margin:0 0 18px;">
                {{ __('Your code is') }}
                <strong class="mono" style="color:var(--text);letter-spacing:0.5px;">{{ $submission->annotate_code }}</strong>@if($submission->deadline_at),
                {{ __('and this task closes') }} {{ $submission->deadline_at->diffForHumans() }}@endif.
            </p>

            <a href="{{ route('user.annotate.console', $submission->annotate_code) }}"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:8px;background:var(--accent);color:#fff;font-size:14.5px;font-weight:600;text-decoration:none;">
                @if($submission->progress_saved_at)
                    {{ __('Continue task') }} &rarr;
                @else
                    {{ __('Start task') }} &rarr;
                @endif
            </a>

            @if($submission->progress_saved_at)
                <div style="font-size:12px;color:var(--muted);margin-top:12px;">
                    {{ __('Last saved') }} {{ $submission->progress_saved_at->diffForHumans() }}.
                </div>
            @endif
        </div>
    @elseif ($submission->isOpenForWorker() && empty($submission->work?->task_json))
        {{-- Approved onto a task that predates the JSON format. Says so plainly
             rather than showing a button that opens an empty console. --}}
        <div style="padding:16px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:14px;line-height:1.6;">
            {{ __('This task has no question file attached yet, so it cannot be opened. Contact support quoting') }}
            <strong>{{ $submission->work?->task_id ?? $submission->id }}</strong>.
        </div>
    @elseif ($submission->delivery_status === \App\Models\WorkSubmission::DEL_SUBMITTED)
        <div style="padding:16px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:14px;">
            {{ __('Your result has been submitted and is awaiting review.') }}
        </div>
    @endif
</div>
@endsection
