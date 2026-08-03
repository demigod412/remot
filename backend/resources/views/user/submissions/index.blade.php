@extends('user.layouts.app')
@section('title', __('My Submissions'))
@section('page-title', __('Submissions'))

@section('content')
@php
    $user       = auth()->user();
    $counts     = [
        'all'      => $user->workSubmissions()->count(),
        'pending'  => $user->workSubmissions()->whereIn('status', [0, 1])->count(),
        'approved' => $user->workSubmissions()->where('status', 2)->count(),
        'rejected' => $user->workSubmissions()->where('status', 3)->count(),
    ];
    // Was summing works.coins_per_worker, which is 0 on every admin-posted task and
    // in any case gross rather than what the worker actually received. Reads the USD
    // work_earn ledger rows instead: net of commission, and denominated correctly.
    $totalEarned = $user->ledgerEntries()
        ->where('entry_type', '+')
        ->where('category', 'work_earn')
        ->inUsd()
        ->sum('coins');
    $currentStatus = request('status', '');
@endphp

{{-- ── STAT TILES ─────────────────────────────────────────────── --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px;" class="sub-stat-grid">
    <div class="card" style="padding:16px;">
        <div class="label" style="margin-bottom:8px;">{{ __('Pending') }}</div>
        <div class="mono" style="font-size:24px; font-weight:600; color:#F59E0B; letter-spacing:-0.5px;">{{ $counts['pending'] }}</div>
        <div style="font-size:11px; color:var(--fg-3); margin-top:4px;">{{ __('Avg review ~24h') }}</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="label" style="margin-bottom:8px;">{{ __('Approved') }}</div>
        <div class="mono" style="font-size:24px; font-weight:600; color:#22C55E; letter-spacing:-0.5px;">{{ $counts['approved'] }}</div>
        <div style="font-size:11px; color:var(--fg-3); margin-top:4px;">
            @if($counts['all'] > 0){{ round($counts['approved'] / $counts['all'] * 100) }}% {{ __('rate') }}@else—@endif
        </div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="label" style="margin-bottom:8px;">{{ __('Rejected') }}</div>
        <div class="mono" style="font-size:24px; font-weight:600; color:#EF4444; letter-spacing:-0.5px;">{{ $counts['rejected'] }}</div>
        <div style="font-size:11px; color:var(--fg-3); margin-top:4px;">{{ __('Rework available') }}</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="label" style="margin-bottom:8px;">{{ __('Earnings cleared') }}</div>
        <div style="display:flex; align-items:baseline; gap:3px;">
            <span class="mono" style="font-size:22px; font-weight:600; letter-spacing:-0.5px; color:var(--coin);">{{ formatUsd($totalEarned) }}</span>
        </div>
    </div>
</div>

{{-- ── TABS ────────────────────────────────────────────────────── --}}
<div style="display:flex; gap:2px; margin-bottom:18px; border-bottom:1px solid var(--border);">
    @foreach(['' => __('All'), '0' => __('Applied'), '1' => __('In review'), '2' => __('Approved'), '3' => __('Rejected')] as $val => $label)
    @php
        $active = $currentStatus === (string)$val;
        $cnt    = match((string)$val) {
            ''  => $counts['all'],
            '0', '1' => $val === '0' ? $user->workSubmissions()->where('status', 0)->count() : $user->workSubmissions()->where('status', 1)->count(),
            '2' => $counts['approved'],
            '3' => $counts['rejected'],
            default => 0
        };
    @endphp
    <a href="{{ route('user.submissions.index', $val !== '' ? ['status' => $val] : []) }}"
       style="padding:10px 16px; text-decoration:none;
              color:{{ $active ? 'var(--fg)' : 'var(--fg-3)' }};
              font-weight:{{ $active ? '600' : '500' }};
              font-size:13px; white-space:nowrap; display:inline-block; margin-bottom:-1px;
              border-bottom:{{ $active ? '2px solid var(--accent)' : '2px solid transparent' }};">
        {{ $label }}
        <span class="mono" style="font-size:11px; color:var(--fg-4); margin-left:4px;">{{ $cnt }}</span>
    </a>
    @endforeach
</div>

{{-- ── LIST ────────────────────────────────────────────────────── --}}
<div style="display:flex; flex-direction:column; gap:10px;">
@forelse($submissions as $sub)
@php
    $work = $sub->work;
    $statusColors = [0 => '#F59E0B', 1 => '#F59E0B', 2 => '#22C55E', 3 => '#EF4444'];
    $statusLabels = [0 => __('Applied'), 1 => __('In review'), 2 => __('Approved'), 3 => __('Rejected')];
    $statusBg     = [0 => 'rgba(245,158,11,0.1)', 1 => 'rgba(245,158,11,0.1)', 2 => 'rgba(34,197,94,0.08)', 3 => 'rgba(239,68,68,0.08)'];
    $sc = $statusColors[$sub->status] ?? 'var(--fg-3)';
    $sl = $statusLabels[$sub->status] ?? '';
    $sbg = $statusBg[$sub->status] ?? 'transparent';
@endphp
<div class="card" style="padding:18px;">
    <div style="display:flex; align-items:flex-start; gap:16px;">
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px; flex-wrap:wrap;">
                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:500; background:{{ $sbg }}; color:{{ $sc }}; border:1px solid {{ $sc }}33;">
                    @if($sub->status == 2)<i data-lucide="check-circle" style="width:11px;height:11px;"></i>@endif
                    @if($sub->status == 3)<i data-lucide="x-circle" style="width:11px;height:11px;"></i>@endif
                    @if($sub->status <= 1)<i data-lucide="clock" style="width:11px;height:11px;"></i>@endif
                    {{ $sl }}
                </span>
                <span style="font-size:11.5px; color:var(--fg-3);">{{ $sub->created_at->diffForHumans() }}</span>
                @if($sub->status <= 1 && $sub->deadline_at)
                <span style="font-size:11.5px; color:{{ $sub->deadline_at->diffInHours(now()) <= 2 ? 'var(--warn)' : 'var(--fg-3)' }};">·
                    {{ __('Auto-approves in') }} <span class="sub-countdown mono"
                        data-expires="{{ $sub->deadline_at->timestamp }}"
                        style="font-weight:600;">{{ $sub->deadline_at->diffForHumans() }}</span>
                </span>
                @endif
            </div>

            <div style="font-size:14px; font-weight:500; color:var(--fg); margin-bottom:{{ ($sub->rejection_reason || $sub->proof_files) ? '10px' : '0' }};">
                {{ $work->title ?? 'Deleted Work' }}
                @if($work)
                <span style="font-size:12px; color:var(--fg-3); font-weight:400; margin-left:8px;">{{ $work->category?->name ?? '' }}</span>
                @endif
            </div>

            @if($sub->rejection_reason && $sub->status == 3)
            <div style="font-size:12.5px; color:#FCA5A5; padding:10px 12px; background:rgba(239,68,68,0.08); border-radius:8px; border-left:2px solid #EF4444; margin-bottom:10px;">
                {{ $sub->rejection_reason }}
            </div>
            @endif

            @if($sub->proof_note && $sub->status != 0)
            <div style="font-size:12.5px; color:var(--fg-2); padding:10px 12px; background:var(--surface-2); border-radius:8px; margin-bottom:10px;">
                {{ Str::limit($sub->proof_note, 120) }}
            </div>
            @endif

            @if($sub->proof_files && count($sub->proof_files))
            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                @foreach($sub->proof_files as $f)
                <span class="chip mono" style="font-size:10.5px; display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="file" style="width:10px;height:10px;"></i>
                    {{ basename($f) }}
                </span>
                @endforeach
            </div>
            @endif
        </div>

        <div style="text-align:right; flex-shrink:0; display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
            @if($work && $sub->status == 2)
            <div style="display:flex; align-items:baseline; gap:3px;">
                
                <span class="mono" style="font-size:20px; font-weight:600; color:green;">{{ formatUsd($work->payout_usd) }}</span>
            </div>
            @elseif($work)
            <div style="display:flex; align-items:baseline; gap:3px; opacity:0.5;">
               </span>
                <span class="mono" style="font-size:20px; font-weight:600; color:orange;">{{ formatUsd($work->payout_usd) }}</span>
            </div>
            @endif

            <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">
                @if($work && $sub->status == 0)
                <a href="{{ route('user.submissions.proof', $sub->id) }}" class="btn btn-primary" style="font-size:12px; padding:6px 12px;">{{ __('Submit proof') }}</a>
                @endif
                @if($work)
                <a href="{{ route('user.browse.works.show', $work->slug) }}" class="btn" style="font-size:12px; padding:6px 10px;" target="_blank">View</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Rate employer inline (approved, user-posted work only) --}}
    @if($sub->status === 2 && $work && $work->poster_type === 2)
    @php
        $posterRated = \App\Models\Rating::where('rater_id', auth()->id())
            ->where('ratable_id', $work->id)
            ->where('ratable_type', \App\Models\Work::class)
            ->where('ratee_id', $work->poster_id)
            ->first();
    @endphp
    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border);"
         x-data="{ open: false, starHover: 0, starPick: {{ $posterRated ? $posterRated->rating : 0 }} }">
        @if($posterRated)
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span style="font-size:11.5px; color:var(--fg-3);">Your rating for employer:</span>
            <div style="display:flex; gap:1px;">
                @for($i = 1; $i <= 5; $i++)
                <span style="font-size:14px;line-height:1;color:{{ $i <= $posterRated->rating ? '#F59E0B' : 'rgba(255,255,255,0.15)' }};">★</span>
                @endfor
            </div>
            <a href="{{ route('user.public-profile', $work->poster->username) }}"
               style="font-size:11.5px; color:var(--accent); text-decoration:none; margin-left:auto;">View employer →</a>
        </div>
        @else
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <button @click="open = !open"
                    style="display:inline-flex; align-items:center; gap:5px; font-size:12px; padding:5px 11px; border-radius:7px; background:rgba(245,158,11,0.1); color:#F59E0B; border:1px solid rgba(245,158,11,0.2); cursor:pointer;">
                <i data-lucide="star" style="width:12px;height:12px;"></i> Rate employer
            </button>
            @if($work->poster)
            <a href="{{ route('user.public-profile', $work->poster->username) }}"
               style="font-size:11.5px; color:var(--fg-3); text-decoration:none; margin-left:auto;">View employer →</a>
            @endif
        </div>
        <div x-show="open" x-transition style="margin-top:12px; padding:14px; background:var(--surface-2); border-radius:10px;">
            <form method="POST" action="{{ route('user.ratings.store') }}" style="display:flex; flex-direction:column; gap:10px;">
                @csrf
                <input type="hidden" name="ratable_type" value="work">
                <input type="hidden" name="ratable_id" value="{{ $work->id }}">
                <input type="hidden" name="ratee_id" value="{{ $work->poster_id }}">
                <input type="hidden" name="rating" :value="starPick">
                <div style="display:flex; gap:2px; align-items:center;">
                    @for($i = 1; $i <= 5; $i++)
                    <button type="button"
                            @mouseover="starHover = {{ $i }}" @mouseleave="starHover = starPick" @click="starPick = {{ $i }}"
                            style="font-size:24px;line-height:1;background:none;border:none;cursor:pointer;padding:0 1px;transition:transform .1s;"
                            :style="(starHover >= {{ $i }} || starPick >= {{ $i }}) ? 'color:#F59E0B;transform:scale(1.2)' : 'color:rgba(255,255,255,0.15)'">★</button>
                    @endfor
                    <span style="font-size:12px; color:var(--fg-3); margin-left:8px;" x-text="['','Poor','Fair','Good','Great','Excellent'][starPick] || ''"></span>
                </div>
                <textarea name="review" rows="2" style="width:100%; font-size:12px; resize:none;" class="input" placeholder="{{ __('Short review (optional)…') }}"></textarea>
                <div style="display:flex; gap:8px;">
                    <button type="submit" :disabled="starPick === 0" :class="starPick === 0 ? 'opacity-40' : ''" class="btn btn-primary" style="font-size:12px; padding:7px 14px;">{{ __('Submit') }}</button>
                    <button type="button" @click="open = false" class="btn" style="font-size:12px; padding:7px 14px;">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
        @endif
    </div>
    @endif
</div>
@empty
<div class="card" style="padding:60px 24px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">📋</div>
    <div style="font-size:15px; font-weight:500; color:var(--fg); margin-bottom:8px;">{{ __('No submissions yet') }}</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;">{{ __('Browse instant jobs and start applying to earn coins.') }}</p>
    <a href="{{ route('works.index') }}" class="btn btn-primary" style="font-size:13px;">{{ __('Browse instant jobs →') }}</a>
</div>
@endforelse
</div>

<div style="margin-top:20px;">{{ $submissions->withQueryString()->links() }}</div>

<style>
@media (max-width: 768px) {
    .sub-stat-grid { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width: 480px) {
    .sub-stat-grid { grid-template-columns: 1fr !important; }
}
</style>

@endsection

@push('scripts')
<script>
(function() {
    function pad(n) { return n < 10 ? '0' + n : n; }
    function tickAll() {
        document.querySelectorAll('.sub-countdown[data-expires]').forEach(function(el) {
            var exp  = parseInt(el.dataset.expires) * 1000;
            var diff = Math.max(0, Math.floor((exp - Date.now()) / 1000));
            if (diff === 0) { el.textContent = 'now'; return; }
            var d = Math.floor(diff / 86400), h = Math.floor((diff % 86400) / 3600);
            var m = Math.floor((diff % 3600) / 60), s = diff % 60;
            el.textContent = d > 0
                ? d + 'd ' + pad(h) + 'h'
                : pad(h) + ':' + pad(m) + ':' + pad(s);
        });
    }
    tickAll();
    setInterval(tickAll, 1000);
})();
</script>
@endpush
