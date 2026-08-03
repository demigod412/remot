@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@php
// Inline queries for sections not passed by controller
// Count off the two live axes, not the derived legacy `status` mirror. The
// mirror is lossy by design, and reading it here undercounted the queue: an
// application still Awaiting Review maps to legacy status 0, so the tile only
// ever showed submitted deliveries and never the applications waiting on admin.
$pendingApplications = \App\Models\WorkSubmission::awaitingApplicationReview()->count();
$pendingDeliveries   = \App\Models\WorkSubmission::awaitingDeliveryReview()->count();
$pendingSubs         = $pendingApplications + $pendingDeliveries;
$pendingKyc      = \App\Models\User::where('kyc_status', 2)->count();
$pendingCashouts = \App\Models\Cashout::where('status', 0)->count();

$topCategories = \App\Models\WorkCategory::withCount('works')
    ->orderByDesc('works_count')->limit(5)->get();
$maxCatCount = $topCategories->max('works_count') ?: 1;

$topUsers = \App\Models\User::where('coin_balance', '>', 0)
    ->orderByDesc('coin_balance')->limit(5)->get();

// Sparkline arrays (last 10 of 30d)
$uArr   = array_values($userData->toArray());
$uLast  = array_slice($uArr, -10);
$uMax   = max(array_merge($uLast, [1]));
$uPts   = collect($uLast)->map(fn($v,$i) => round(($i/(count($uLast)-1))*80,1).','.round(26-($v/$uMax)*22,1))->implode(' ');

$cArr   = array_values($coinData->toArray());
$cLast  = array_slice($cArr, -10);
$cMax   = max(array_merge($cLast, [1]));
$cPts   = collect($cLast)->map(fn($v,$i) => round(($i/(count($cLast)-1))*80,1).','.round(26-($v/$cMax)*22,1))->implode(' ');
@endphp

{{-- 4 KPI Tiles --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Total users</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;">{{ number_format($stats['total_users']) }}</div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
            <span style="font-size:11.5px;color:#22C55E;">{{ number_format($stats['active_users']) }} active</span>
            <svg width="80" height="26" fill="none" viewBox="0 0 80 26"><polyline points="{{ $uPts }}" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Coins volume (30d)</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;">{{ formatCoins($coinData->sum()) }}</div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
            <span style="font-size:11.5px;color:var(--coin);">+{{ formatCoins($coinData->last()) }} today</span>
            <svg width="80" height="26" fill="none" viewBox="0 0 80 26"><polyline points="{{ $cPts }}" stroke="var(--coin)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Submissions queued</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;">{{ number_format($pendingSubs) }}</div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
            <span style="font-size:11.5px;color:{{ $pendingSubs > 0 ? '#F59E0B' : 'var(--fg-3)' }};">{{ $pendingSubs > 0 ? 'needs review' : 'all clear' }}</span>
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Withdrawals queued</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;">{{ number_format($pendingCashouts) }}</div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
            <span style="font-size:11.5px;color:{{ $pendingCashouts > 0 ? '#EF4444' : 'var(--fg-3)' }};">{{ $pendingCashouts > 0 ? 'pending payout' : 'all clear' }}</span>
        </div>
    </div>

</div>

{{-- Charts Row --}}
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:14px;margin-bottom:18px;">

    {{-- Volume Chart --}}
    <div class="jobstation-card" style="padding:22px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <div>
                <h3 style="font-size:14px;font-weight:600;margin:0;">Platform volume · last 30 days</h3>
                <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;">Coins credited &amp; new users</div>
            </div>
            <div style="display:flex;gap:14px;font-size:11.5px;color:var(--fg-2);">
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:8px;height:8px;border-radius:2px;background:var(--coin);display:inline-block;"></span>Coins
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:8px;height:8px;border-radius:2px;background:var(--accent);display:inline-block;"></span>Users
                </div>
            </div>
        </div>
        <canvas id="volumeChart" height="160"></canvas>
        <div style="display:flex;justify-content:space-between;font-size:10.5px;color:var(--fg-3);margin-top:10px;">
            <span>{{ $dates->first() }}</span>
            <span>{{ $dates->get(7) }}</span>
            <span>{{ $dates->get(15) }}</span>
            <span>{{ $dates->get(22) }}</span>
            <span>Today</span>
        </div>
    </div>

    {{-- Needs Attention --}}
    <div class="jobstation-card" style="padding:22px;">
        <h3 style="font-size:14px;font-weight:600;margin:0 0 14px;">Needs attention</h3>

        @php
        $attention = [
            ['t' => $pendingSubs.' submissions awaiting review', 'sub' => $pendingApplications.' applications, '.$pendingDeliveries.' deliveries', 'icon' => 'file-check', 'c' => '#F59E0B', 'href' => route('admin.task-review.index')],
            ['t' => $pendingKyc.' KYC applications pending', 'sub' => 'Identity verification queue', 'icon' => 'shield-check', 'c' => '#60A5FA', 'href' => route('admin.users.kyc')],
            ['t' => $stats['pending_topups'].' top-ups awaiting review', 'sub' => 'Deposit requests', 'icon' => 'arrow-down-circle', 'c' => 'var(--accent)', 'href' => route('admin.topups.index', ['status' => 'pending'])],
            ['t' => $pendingCashouts.' withdrawals queued', 'sub' => 'Payout requests', 'icon' => 'arrow-up-circle', 'c' => '#EF4444', 'href' => route('admin.cashouts.index', ['status' => 'pending'])],
        ];
        @endphp

        @foreach($attention as $idx => $n)
        <a href="{{ $n['href'] }}" style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:{{ $idx < 3 ? '1px solid var(--border)' : 'none' }};text-decoration:none;">
            <span style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.05);color:{{ $n['c'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="{{ $n['icon'] }}" style="width:15px;height:15px;"></i>
            </span>
            <div style="flex:1;">
                <div style="font-size:12.5px;font-weight:500;color:var(--fg);">{{ $n['t'] }}</div>
                <div style="font-size:10.5px;color:var(--fg-3);">{{ $n['sub'] }}</div>
            </div>
            <i data-lucide="arrow-right" style="width:14px;height:14px;color:var(--fg-3);flex-shrink:0;"></i>
        </a>
        @endforeach
    </div>

</div>

{{-- Bottom 3-col --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">

    {{-- Top Categories --}}
    <div class="jobstation-card" style="padding:20px;">
        <h3 style="font-size:13px;font-weight:600;margin:0 0 14px;">Top categories</h3>
        @forelse($topCategories as $cat)
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                <span>{{ $cat->name }}</span>
                <span class="mono" style="color:var(--fg-3);">{{ number_format($cat->works_count) }}</span>
            </div>
            <div style="height:4px;background:var(--surface-3);border-radius:2px;">
                <div style="width:{{ min(($cat->works_count / $maxCatCount) * 100, 100) }}%;height:100%;background:var(--accent);border-radius:2px;"></div>
            </div>
        </div>
        @empty
        <div style="font-size:12px;color:var(--fg-3);">No categories yet.</div>
        @endforelse
    </div>

    {{-- Top Users by Balance --}}
    <div class="jobstation-card" style="padding:20px;">
        <h3 style="font-size:13px;font-weight:600;margin:0 0 14px;">Top balances</h3>
        @forelse($topUsers as $idx => $u)
        @php
            $ini = strtoupper(substr($u->username ?? $u->firstname ?? 'U', 0, 1));
            $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
            $clr  = $clrs[ord($ini) % count($clrs)];
        @endphp
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:{{ $idx < 4 ? '1px solid var(--border)' : 'none' }};">
            <span class="mono" style="font-size:11px;color:var(--fg-4);width:16px;">{{ str_pad($idx+1,2,'0',STR_PAD_LEFT) }}</span>
            <div style="width:26px;height:26px;border-radius:50%;background:{{ $clr }};display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:600;flex-shrink:0;">{{ $ini }}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $u->fullname }}</div>
                <div style="font-size:10.5px;color:var(--fg-3);">{{ '@' . $u->username }}</div>
            </div>
            <span class="mono" style="font-size:11.5px;color:var(--coin);">{{ formatCoins($u->coin_balance) }}</span>
        </div>
        @empty
        <div style="font-size:12px;color:var(--fg-3);">No users yet.</div>
        @endforelse
    </div>

    {{-- Recent Submissions Activity --}}
    <div class="jobstation-card" style="padding:20px;">
        <h3 style="font-size:13px;font-weight:600;margin:0 0 14px;">Recent activity</h3>
        @forelse($recentSubmissions as $idx => $sub)
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:{{ $idx < count($recentSubmissions)-1 ? '1px solid var(--border)' : 'none' }};font-size:12px;">
            <span style="width:4px;height:4px;border-radius:2px;background:var(--accent);flex-shrink:0;"></span>
            <span style="font-weight:500;width:72px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--fg);">{{ $sub->worker?->username ?? '—' }}</span>
            <span style="flex:1;color:var(--fg-2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit($sub->work?->title ?? 'N/A', 28) }}</span>
            <span class="mono" style="color:var(--fg-4);font-size:10.5px;flex-shrink:0;">{{ $sub->created_at->diffForHumans(null, true) }}</span>
        </div>
        @empty
        <div style="font-size:12px;color:var(--fg-3);">No recent activity.</div>
        @endforelse
    </div>

</div>

{{-- Cashout trend chart --}}
<div class="jobstation-card" style="padding:22px;margin-top:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="font-size:14px;font-weight:600;margin:0;">Withdrawal volume · last 30 days</h3>
            <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;">Coins requested for cashout per day</div>
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--fg-2);">
            <span style="width:8px;height:8px;border-radius:2px;background:#EF4444;display:inline-block;"></span>Coins out
        </div>
    </div>
    <canvas id="cashoutChart" height="80"></canvas>
    <div style="display:flex;justify-content:space-between;font-size:10.5px;color:var(--fg-3);margin-top:10px;">
        <span>{{ $dates->first() }}</span>
        <span>{{ $dates->get(7) }}</span>
        <span>{{ $dates->get(15) }}</span>
        <span>{{ $dates->get(22) }}</span>
        <span>Today</span>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels      = @json($dates->values());
const coinVals    = @json($coinData->values());
const userVals    = @json($userData->values());
const cashoutVals = @json($cashoutData->values());

new Chart(document.getElementById('volumeChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'Coins Credited',
                data: coinVals,
                backgroundColor: 'rgba(245,213,71,0.65)',
                borderRadius: 3,
                order: 1,
            },
            {
                label: 'New Users',
                data: userVals,
                backgroundColor: 'rgba(47,84,235,0.65)',
                borderRadius: 3,
                order: 2,
            },
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'var(--surface)',
                borderColor: 'var(--border)',
                borderWidth: 1,
                titleColor: '#fff',
                bodyColor: 'rgba(255,255,255,0.6)',
            }
        },
        scales: {
            x: {
                stacked: true,
                ticks: { color: 'rgba(255,255,255,0.25)', maxTicksLimit: 5 },
                grid: { color: 'rgba(255,255,255,0.04)' }
            },
            y: {
                stacked: true,
                ticks: { color: 'rgba(255,255,255,0.25)' },
                grid: { color: 'rgba(255,255,255,0.04)' },
                beginAtZero: true,
            }
        }
    }
});

new Chart(document.getElementById('cashoutChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Coins Out',
            data: cashoutVals,
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239,68,68,0.08)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
        }]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'var(--surface)',
                borderColor: 'var(--border)',
                borderWidth: 1,
                titleColor: '#fff',
                bodyColor: 'rgba(255,255,255,0.6)',
            }
        },
        scales: {
            x: { ticks: { color: 'rgba(255,255,255,0.25)', maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.04)' } },
            y: { ticks: { color: 'rgba(255,255,255,0.25)' }, grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true }
        }
    }
});
</script>
@endpush
