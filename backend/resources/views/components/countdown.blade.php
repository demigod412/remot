{{--
  Countdown Timer Component
  Usage: <x-countdown :expires-at="$work->expires_at" />

  Props:
    $expiresAt  — Carbon instance or null
    $size       — 'sm' | 'md' (default: 'sm')
    $showBadge  — bool: show inline badge (default: true)
--}}

@props([
    'expiresAt' => null,
    'size'      => 'sm',
    'showBadge' => true,
])

@if($expiresAt && $expiresAt->isFuture())
    @php
        $secondsLeft  = max(0, now()->diffInSeconds($expiresAt, false));
        $hoursLeft    = now()->diffInHours($expiresAt, false);
        $isExpiringSoon = $hoursLeft <= 24;
        $isUrgent       = $hoursLeft <= 6;
        $countdownId    = 'cd-' . uniqid();
    @endphp

    <span
        id="{{ $countdownId }}"
        class="countdown-timer inline-flex items-center gap-1 font-mono text-{{ $size }}
            {{ $isUrgent ? 'text-red-400' : ($isExpiringSoon ? 'text-amber-400' : 'text-white/50') }}"
        data-expires="{{ $expiresAt->timestamp }}"
        title="Expires {{ $expiresAt->diffForHumans() }}"
    >
        @if($isExpiringSoon)
            <svg class="w-3 h-3 shrink-0 {{ $isUrgent ? 'animate-pulse' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @endif
        <span class="countdown-value">{{ $expiresAt->diffForHumans() }}</span>
    </span>

    @if($showBadge && $isExpiringSoon)
        <span class="inline-flex items-center gap-1 ml-1 px-1.5 py-0.5 rounded text-xs font-semibold
            {{ $isUrgent ? 'bg-red-500/20 text-red-300' : 'bg-amber-500/20 text-amber-300' }}">
            {{ $isUrgent ? '🔥 Urgent' : '⏳ Expiring Soon' }}
        </span>
    @endif

    <script>
    (function() {
        var el = document.getElementById('{{ $countdownId }}');
        if (!el) return;

        var expires = {{ $expiresAt->timestamp }} * 1000;
        var valueEl = el.querySelector('.countdown-value');

        function pad(n) { return n < 10 ? '0' + n : n; }

        function update() {
            var diff = Math.max(0, Math.floor((expires - Date.now()) / 1000));
            if (diff === 0) {
                valueEl.textContent = 'Expired';
                el.classList.remove('text-amber-400', 'text-white/50');
                el.classList.add('text-red-400');
                return;
            }
            var d = Math.floor(diff / 86400);
            var h = Math.floor((diff % 86400) / 3600);
            var m = Math.floor((diff % 3600) / 60);
            var s = diff % 60;
            if (d > 0) {
                valueEl.textContent = d + 'd ' + pad(h) + 'h ' + pad(m) + 'm';
            } else {
                valueEl.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
            }
            setTimeout(update, 1000);
        }
        update();
    })();
    </script>
@elseif($expiresAt && $expiresAt->isPast())
    <span class="inline-flex items-center gap-1 text-red-400 text-{{ $size }}">
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Expired
    </span>
@endif
