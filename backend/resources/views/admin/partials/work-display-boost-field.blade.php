{{--
    Include in the admin task create/edit form, next to worker_slots:
        @include('admin.partials.work-display-boost-field', ['work' => $work ?? null])

    DISPLAY ONLY. This number is added to the applicant count shown publicly so a
    brand new task does not read as dead. It never enters slot arithmetic: a task
    with 100 slots and an 80 boost still accepts 100 real workers.
--}}
@php $work = $work ?? null; @endphp

<div style="margin-bottom:14px;">
    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">
        Starting applicant count (display only)
    </label>
    <input type="number" name="display_application_boost" min="0" max="100000"
           value="{{ old('display_application_boost', $work->display_application_boost ?? 0) }}"
           style="width:100%;font-size:13px;font-family:ui-monospace,monospace;">
    <small style="display:block;color:var(--fg-3);font-size:11px;line-height:1.6;margin-top:4px;">
        Added to the applicant number shown on the public task page, so a new task does
        not sit at zero. Does <strong>not</strong> consume slots, affect who can apply,
        or appear anywhere in your review queue or payout figures.
        @if($work)
            Real applications right now: <strong>{{ $work->real_application_count }}</strong>.
        @endif
    </small>
</div>
