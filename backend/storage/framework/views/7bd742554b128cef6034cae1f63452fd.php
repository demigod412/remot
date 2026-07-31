<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>

<?php
// Inline queries for sections not passed by controller
$pendingSubs     = \App\Models\WorkSubmission::where('status', 1)->count();
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
?>


<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Total users</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;"><?php echo e(number_format($stats['total_users'])); ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
            <span style="font-size:11.5px;color:#22C55E;"><?php echo e(number_format($stats['active_users'])); ?> active</span>
            <svg width="80" height="26" fill="none" viewBox="0 0 80 26"><polyline points="<?php echo e($uPts); ?>" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Coins volume (30d)</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;"><?php echo e(coinSymbol()); ?><?php echo e(number_format($coinData->sum())); ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
            <span style="font-size:11.5px;color:var(--coin);">+<?php echo e(coinSymbol()); ?><?php echo e(number_format($coinData->last())); ?> today</span>
            <svg width="80" height="26" fill="none" viewBox="0 0 80 26"><polyline points="<?php echo e($cPts); ?>" stroke="var(--coin)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Submissions queued</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;"><?php echo e(number_format($pendingSubs)); ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
            <span style="font-size:11.5px;color:<?php echo e($pendingSubs > 0 ? '#F59E0B' : 'var(--fg-3)'); ?>;"><?php echo e($pendingSubs > 0 ? 'needs review' : 'all clear'); ?></span>
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:8px;">Withdrawals queued</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;"><?php echo e(number_format($pendingCashouts)); ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
            <span style="font-size:11.5px;color:<?php echo e($pendingCashouts > 0 ? '#EF4444' : 'var(--fg-3)'); ?>;"><?php echo e($pendingCashouts > 0 ? 'pending payout' : 'all clear'); ?></span>
        </div>
    </div>

</div>


<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:14px;margin-bottom:18px;">

    
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
            <span><?php echo e($dates->first()); ?></span>
            <span><?php echo e($dates->get(7)); ?></span>
            <span><?php echo e($dates->get(15)); ?></span>
            <span><?php echo e($dates->get(22)); ?></span>
            <span>Today</span>
        </div>
    </div>

    
    <div class="jobstation-card" style="padding:22px;">
        <h3 style="font-size:14px;font-weight:600;margin:0 0 14px;">Needs attention</h3>

        <?php
        $attention = [
            ['t' => $pendingSubs.' submissions awaiting review', 'sub' => 'Pending moderation', 'icon' => 'file-check', 'c' => '#F59E0B', 'href' => route('admin.submissions.index', ['status' => 1])],
            ['t' => $pendingKyc.' KYC applications pending', 'sub' => 'Identity verification queue', 'icon' => 'shield-check', 'c' => '#60A5FA', 'href' => route('admin.users.kyc')],
            ['t' => $stats['pending_topups'].' top-ups awaiting review', 'sub' => 'Deposit requests', 'icon' => 'arrow-down-circle', 'c' => 'var(--accent)', 'href' => route('admin.topups.index', ['status' => 'pending'])],
            ['t' => $pendingCashouts.' withdrawals queued', 'sub' => 'Payout requests', 'icon' => 'arrow-up-circle', 'c' => '#EF4444', 'href' => route('admin.cashouts.index', ['status' => 'pending'])],
        ];
        ?>

        <?php $__currentLoopData = $attention; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e($n['href']); ?>" style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:<?php echo e($idx < 3 ? '1px solid var(--border)' : 'none'); ?>;text-decoration:none;">
            <span style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.05);color:<?php echo e($n['c']); ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="<?php echo e($n['icon']); ?>" style="width:15px;height:15px;"></i>
            </span>
            <div style="flex:1;">
                <div style="font-size:12.5px;font-weight:500;color:var(--fg);"><?php echo e($n['t']); ?></div>
                <div style="font-size:10.5px;color:var(--fg-3);"><?php echo e($n['sub']); ?></div>
            </div>
            <i data-lucide="arrow-right" style="width:14px;height:14px;color:var(--fg-3);flex-shrink:0;"></i>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

</div>


<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">

    
    <div class="jobstation-card" style="padding:20px;">
        <h3 style="font-size:13px;font-weight:600;margin:0 0 14px;">Top categories</h3>
        <?php $__empty_1 = true; $__currentLoopData = $topCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                <span><?php echo e($cat->name); ?></span>
                <span class="mono" style="color:var(--fg-3);"><?php echo e(number_format($cat->works_count)); ?></span>
            </div>
            <div style="height:4px;background:var(--surface-3);border-radius:2px;">
                <div style="width:<?php echo e(min(($cat->works_count / $maxCatCount) * 100, 100)); ?>%;height:100%;background:var(--accent);border-radius:2px;"></div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="font-size:12px;color:var(--fg-3);">No categories yet.</div>
        <?php endif; ?>
    </div>

    
    <div class="jobstation-card" style="padding:20px;">
        <h3 style="font-size:13px;font-weight:600;margin:0 0 14px;">Top balances</h3>
        <?php $__empty_1 = true; $__currentLoopData = $topUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $ini = strtoupper(substr($u->username ?? $u->firstname ?? 'U', 0, 1));
            $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
            $clr  = $clrs[ord($ini) % count($clrs)];
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:<?php echo e($idx < 4 ? '1px solid var(--border)' : 'none'); ?>;">
            <span class="mono" style="font-size:11px;color:var(--fg-4);width:16px;"><?php echo e(str_pad($idx+1,2,'0',STR_PAD_LEFT)); ?></span>
            <div style="width:26px;height:26px;border-radius:50%;background:<?php echo e($clr); ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:600;flex-shrink:0;"><?php echo e($ini); ?></div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($u->fullname); ?></div>
                <div style="font-size:10.5px;color:var(--fg-3);"><?php echo e('@' . $u->username); ?></div>
            </div>
            <span class="mono" style="font-size:11.5px;color:var(--coin);"><?php echo e(coinSymbol()); ?><?php echo e(number_format($u->coin_balance)); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="font-size:12px;color:var(--fg-3);">No users yet.</div>
        <?php endif; ?>
    </div>

    
    <div class="jobstation-card" style="padding:20px;">
        <h3 style="font-size:13px;font-weight:600;margin:0 0 14px;">Recent activity</h3>
        <?php $__empty_1 = true; $__currentLoopData = $recentSubmissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:<?php echo e($idx < count($recentSubmissions)-1 ? '1px solid var(--border)' : 'none'); ?>;font-size:12px;">
            <span style="width:4px;height:4px;border-radius:2px;background:var(--accent);flex-shrink:0;"></span>
            <span style="font-weight:500;width:72px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--fg);"><?php echo e($sub->worker?->username ?? '—'); ?></span>
            <span style="flex:1;color:var(--fg-2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e(Str::limit($sub->work?->title ?? 'N/A', 28)); ?></span>
            <span class="mono" style="color:var(--fg-4);font-size:10.5px;flex-shrink:0;"><?php echo e($sub->created_at->diffForHumans(null, true)); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="font-size:12px;color:var(--fg-3);">No recent activity.</div>
        <?php endif; ?>
    </div>

</div>


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
        <span><?php echo e($dates->first()); ?></span>
        <span><?php echo e($dates->get(7)); ?></span>
        <span><?php echo e($dates->get(15)); ?></span>
        <span><?php echo e($dates->get(22)); ?></span>
        <span>Today</span>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels      = <?php echo json_encode($dates->values(), 15, 512) ?>;
const coinVals    = <?php echo json_encode($coinData->values(), 15, 512) ?>;
const userVals    = <?php echo json_encode($userData->values(), 15, 512) ?>;
const cashoutVals = <?php echo json_encode($cashoutData->values(), 15, 512) ?>;

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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>