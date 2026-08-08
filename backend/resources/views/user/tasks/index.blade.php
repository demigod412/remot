@extends('user.layouts.app')
@section('title', __('My tasks'))

@section('content')
<div style="max-width:1000px;margin:0 auto;padding:32px 20px;">

    <h1 style="font-size:26px;font-weight:600;margin:0 0 6px;">{{ __('My tasks') }}</h1>
    <p style="font-size:14px;color:var(--muted);margin:0 0 26px;">
        {{ __('Applications you have made and tasks assigned to you.') }}
    </p>

    @if (session('success'))
        <div style="padding:13px 15px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;margin-bottom:20px;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div style="padding:13px 15px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:20px;">
            {{ session('error') }}
        </div>
    @endif

    @forelse ($submissions as $s)
        <div style="border:1px solid var(--border);border-radius:10px;padding:18px;margin-bottom:12px;display:flex;align-items:center;gap:16px;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:15.5px;font-weight:600;margin-bottom:3px;">{{ $s->work->title ?? __('Task removed') }}</div>
                <div style="font-size:12.5px;color:var(--muted);">
                    {{ __('Applied') }} {{ $s->created_at?->diffForHumans() }}
                    @if ((float) $s->fee_paid > 0) · {{ __('fee') }} {{ formatCoins($s->fee_paid) }} @endif
                    @if ($s->annotate_code) · <span class="mono">{{ $s->annotate_code }}</span> @endif
                    @if ($s->progress_saved_at) · {{ __('saved') }} {{ $s->progress_saved_at->diffForHumans() }} @endif
                    @if ($s->deadline_at && $s->isOpenForWorker())
                        · <span style="color:{{ $s->deadline_at->isPast() ? '#dc2626' : 'inherit' }};">
                            {{ __('due') }} {{ $s->deadline_at->diffForHumans() }}
                          </span>
                    @endif
                </div>
            </div>

            @php $state = $s->worker_state; @endphp

            {{-- Coloured by state rather than always grey, so the list can be read at a
                 glance instead of one row at a time. --}}
            <span style="display:inline-block;padding:4px 11px;border-radius:99px;font-size:12px;font-weight:600;white-space:nowrap;
                  background:{{ $state['tint'] }};color:{{ $state['colour'] }};">
                {{ $state['label'] }}
            </span>

            {{-- Straight into the console where there is work to do. Routing through the
                 detail page first is an extra click on the one action that matters. --}}
            @if ($state['action'] && $s->annotate_code && !empty($s->work?->task_json))
                <a href="{{ route('user.annotate.console', $s->annotate_code) }}"
                   style="padding:8px 16px;border-radius:8px;background:var(--accent);color:#fff;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
                    {{ $state['action'] === 'continue' ? __('Continue') : __('Start') }}
                </a>
            @elseif ($s->isApprovedToWork())
                <a href="{{ route('user.tasks.show', $s->id) }}"
                   style="padding:8px 16px;border-radius:8px;border:1px solid var(--border);color:var(--fg-2);font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
                    {{ __('View') }}
                </a>
            @endif
        </div>
    @empty
        <div style="border:1px dashed var(--border);border-radius:10px;padding:48px;text-align:center;color:var(--muted);">
            {{ __('You have not applied to any tasks yet.') }}
        </div>
    @endforelse

    <div style="margin-top:20px;">{{ $submissions->links() }}</div>
</div>
@endsection
