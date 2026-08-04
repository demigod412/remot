<a href="{{ route('works.show', $work->slug) }}"
   class="card" style="display:block;cursor:pointer;transition:.2s;position:relative;"
   onmouseover="this.style.borderColor='rgba(108,71,255,.35)';this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 32px rgba(108,71,255,.12)'"
   onmouseout="this.style.borderColor='var(--border)';this.style.transform='';this.style.boxShadow='var(--shadow-card)'">

    {{-- Cover --}}
    @if($work->cover_image)
        <div style="height:160px;border-radius:8px;overflow:hidden;margin:-24px -24px 20px;">
            <img src="{{ fileUrl($work->cover_image) }}" alt="{{ $work->title }}" style="width:100%;height:100%;object-fit:cover;">
        </div>
    @else
        <div style="height:120px;border-radius:8px;margin:-24px -24px 20px;
                    background:linear-gradient(135deg,rgba(108,71,255,.1),rgba(108,71,255,.04));
                    display:flex;align-items:center;justify-content:center;">
            <span style="font-size:36px;">{{ $work->category->icon ?? '📋' }}</span>
        </div>
    @endif

    {{-- Badges: Hot + Expiring Soon --}}
    <div style="position:absolute;top:12px;right:12px;display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
        @if($work->is_hot)
            <span class="badge badge-yellow">🔥 {{ __('Hot') }}</span>
        @endif
        @if($work->expires_at && $work->expires_at->isFuture() && $work->expires_at->diffInHours(now()) <= 24)
            <span class="badge badge-red">
                ⏳ {{ $work->expires_at->diffInHours(now()) <= 6 ? __('Urgent') : __('Expiring Soon') }}
            </span>
        @endif
    </div>

    {{-- Category + time --}}
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
        @if($work->category)
            <span class="badge badge-purple">{{ $work->category->name }}</span>
        @endif
        @if($work->avg_minutes)
            <span class="badge badge-gray">~{{ $work->avg_minutes }} {{ __('min') }}</span>
        @endif
    </div>

    <h3 style="font-size:15px;font-weight:800;color:var(--text);margin-bottom:8px;
               display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
        {{ $work->title }}
    </h3>

    {{-- Poster profile link (user-posted works only) — span+onclick avoids nested <a> --}}
    @if($work->poster_type === 2 && $work->poster)
    <div style="margin-bottom:10px;">
        <span onclick="event.stopPropagation();event.preventDefault();window.location.href='{{ route('user.public-profile', $work->poster->username) }}';"
              style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--muted);cursor:pointer;transition:color .15s;"
              onmouseover="this.style.color='var(--green)'"
              onmouseout="this.style.color='var(--muted)'">
            <svg style="width:11px;height:11px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            {{ '@' . $work->poster->username }}
        </span>
    </div>
    @endif

    {{-- Countdown (expiring within 72h) --}}
    @if($work->expires_at && $work->expires_at->isFuture() && $work->expires_at->diffInHours(now()) <= 72)
        <div style="margin-bottom:10px;font-size:12px;color:var(--muted);display:flex;align-items:center;gap:4px;">
            <svg style="width:12px;height:12px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="card-countdown"
                  data-expires="{{ $work->expires_at->timestamp }}"
                  style="color:{{ $work->expires_at->diffInHours(now()) <= 6 ? 'var(--red)' : 'var(--yellow)' }};font-weight:700;">
                {{ $work->expires_at->diffForHumans() }}
            </span>
        </div>
    @endif

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:14px;border-top:1px solid var(--border);">
        <div>
            <div style="font-size:20px;font-weight:900;color:var(--purple);">
                {{ formatUsd($work->payout_usd) }}
                <span style="font-size:13px;font-weight:500;color:var(--muted);">{{ gs()->coin_symbol ?? coinSymbol() }}</span>
            </div>
            <div style="font-size:12px;color:var(--muted);">{{ __('per work') }}</div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:13px;font-weight:700;color:{{ $work->slots_remaining <= 3 ? 'var(--red)' : 'var(--green)' }}">
                {{ $work->slots_remaining }} {{ __('slots left') }}
            </div>
            <div style="font-size:12px;color:var(--muted);">{{ $work->worker_slots }} {{ __('total') }}</div>
        </div>
    </div>
</a>

@once
@push('scripts')
<script>
(function initCardCountdowns() {
    function pad(n) { return n < 10 ? '0' + n : n; }
    function tickAll() {
        document.querySelectorAll('.card-countdown[data-expires]').forEach(function(el) {
            var exp  = parseInt(el.dataset.expires) * 1000;
            var diff = Math.max(0, Math.floor((exp - Date.now()) / 1000));
            if (diff === 0) { el.textContent = 'Expired'; return; }
            var d = Math.floor(diff / 86400);
            var h = Math.floor((diff % 86400) / 3600);
            var m = Math.floor((diff % 3600) / 60);
            var s = diff % 60;
            el.textContent = d > 0
                ? d + 'd ' + pad(h) + 'h ' + pad(m) + 'm'
                : pad(h) + ':' + pad(m) + ':' + pad(s);
        });
    }
    tickAll();
    setInterval(tickAll, 1000);
})();
</script>
@endpush
@endonce
