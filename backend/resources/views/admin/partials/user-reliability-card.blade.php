{{--
    Include in admin/users/show.blade.php:
        @include('admin.partials.user-reliability-card', ['user' => $user, 'reliability' => $reliability])

    $reliability comes from WorkerReliabilityService::summary() and is already
    passed by Admin\UserController::show().
--}}
<div class="jobstation-card" style="padding:20px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div class="label">Reliability</div>
        <span style="font-size:11.5px;color:var(--fg-3);">last {{ $reliability['window_days'] }} days</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px;">
        <div>
            <div class="mono" style="font-size:20px;font-weight:600;color:#22C55E;">{{ $reliability['completed'] }}</div>
            <div style="font-size:11px;color:var(--fg-3);">completed</div>
        </div>
        <div>
            <div class="mono" style="font-size:20px;font-weight:600;color:#F59E0B;">{{ $reliability['rejected'] }}</div>
            <div style="font-size:11px;color:var(--fg-3);">rejected</div>
        </div>
        <div>
            <div class="mono" style="font-size:20px;font-weight:600;color:#EF4444;">{{ $reliability['abandoned'] }}</div>
            <div style="font-size:11px;color:var(--fg-3);">abandoned</div>
        </div>
        <div>
            <div class="mono" style="font-size:20px;font-weight:600;color:{{ $reliability['blocked'] ? '#EF4444' : 'var(--fg)' }};">
                {{ $reliability['strikes'] }}<span style="font-size:13px;color:var(--fg-3);">/{{ $reliability['max_strikes'] }}</span>
            </div>
            <div style="font-size:11px;color:var(--fg-3);">strikes</div>
        </div>
    </div>

    @if ($reliability['blocked'])
        <div style="padding:11px 13px;border-radius:8px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);font-size:12.5px;color:var(--fg-2);line-height:1.6;">
            <strong style="color:#EF4444;">Applications paused.</strong>
            This worker cannot apply to new tasks until the strikes age out, or you clear them.
        </div>
    @endif

    @if ($reliability['strikes'] > 0)
        <form method="POST" action="{{ route('admin.users.clear-strikes', $user->id) }}"
              onsubmit="return confirm('Clear this worker\'s strikes? Their submission history is kept, but past strikes stop counting.');"
              style="margin-top:12px;">
            @csrf
            <button type="submit"
                    style="padding:8px 14px;border:1px solid var(--border);border-radius:8px;background:transparent;color:var(--fg-2);font-size:12.5px;font-weight:600;cursor:pointer;">
                Clear strikes
            </button>
        </form>
    @endif

    @if ($user->strikes_cleared_at)
        <div style="margin-top:10px;font-size:11.5px;color:var(--fg-3);">
            Strikes last cleared {{ $user->strikes_cleared_at->diffForHumans() }}.
        </div>
    @endif
</div>
