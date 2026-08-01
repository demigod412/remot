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

    {{-- Task package --}}
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

    {{-- Result upload --}}
    @if ($submission->isOpenForWorker())
        <div style="border:1px solid var(--border);border-radius:10px;padding:20px;">
            <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:12px;text-transform:uppercase;letter-spacing:0.4px;">
                {{ __('Submit your result') }}
            </div>

            <form method="POST" action="{{ route('user.tasks.submit', $submission->id) }}" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom:14px;">
                    <label for="result_file" style="display:block;font-size:13px;margin-bottom:6px;">{{ __('Result file (.json)') }}</label>
                    <input id="result_file" type="file" name="result_file" accept=".json" required>
                    <small style="display:block;color:var(--muted);margin-top:4px;">
                        {{ __('Must be valid JSON. It is parsed on upload, so a renamed file will be rejected.') }}
                    </small>
                </div>

                <div style="margin-bottom:16px;">
                    <label for="proof_note" style="display:block;font-size:13px;margin-bottom:6px;">{{ __('Notes (optional)') }}</label>
                    <textarea id="proof_note" name="proof_note" rows="4" maxlength="2000"
                              style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-family:inherit;resize:vertical;"></textarea>
                </div>

                <button type="submit"
                        style="width:100%;padding:12px;border:0;border-radius:8px;background:var(--accent);color:#fff;font-size:14.5px;font-weight:600;cursor:pointer;">
                    {{ __('Submit result') }}
                </button>
            </form>
        </div>
    @elseif ($submission->delivery_status === \App\Models\WorkSubmission::DEL_SUBMITTED)
        <div style="padding:16px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:14px;">
            {{ __('Your result has been submitted and is awaiting review.') }}
        </div>
    @endif
</div>
@endsection
