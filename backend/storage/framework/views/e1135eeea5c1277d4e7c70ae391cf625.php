<?php $__env->startSection('title', __('My Submissions')); ?>
<?php $__env->startSection('page-title', __('Submissions')); ?>

<?php $__env->startSection('content'); ?>
<?php
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
?>


<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px;" class="sub-stat-grid">
    <div class="card" style="padding:16px;">
        <div class="label" style="margin-bottom:8px;"><?php echo e(__('Pending')); ?></div>
        <div class="mono" style="font-size:24px; font-weight:600; color:#F59E0B; letter-spacing:-0.5px;"><?php echo e($counts['pending']); ?></div>
        <div style="font-size:11px; color:var(--fg-3); margin-top:4px;"><?php echo e(__('Avg review ~24h')); ?></div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="label" style="margin-bottom:8px;"><?php echo e(__('Approved')); ?></div>
        <div class="mono" style="font-size:24px; font-weight:600; color:#22C55E; letter-spacing:-0.5px;"><?php echo e($counts['approved']); ?></div>
        <div style="font-size:11px; color:var(--fg-3); margin-top:4px;">
            <?php if($counts['all'] > 0): ?><?php echo e(round($counts['approved'] / $counts['all'] * 100)); ?>% <?php echo e(__('rate')); ?><?php else: ?>—<?php endif; ?>
        </div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="label" style="margin-bottom:8px;"><?php echo e(__('Rejected')); ?></div>
        <div class="mono" style="font-size:24px; font-weight:600; color:#EF4444; letter-spacing:-0.5px;"><?php echo e($counts['rejected']); ?></div>
        <div style="font-size:11px; color:var(--fg-3); margin-top:4px;"><?php echo e(__('Rework available')); ?></div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="label" style="margin-bottom:8px;"><?php echo e(__('Earnings cleared')); ?></div>
        <div style="display:flex; align-items:baseline; gap:3px;">
            <span class="mono" style="font-size:22px; font-weight:600; letter-spacing:-0.5px; color:var(--coin);"><?php echo e(formatUsd($totalEarned)); ?></span>
        </div>
    </div>
</div>


<div style="display:flex; gap:2px; margin-bottom:18px; border-bottom:1px solid var(--border);">
    <?php $__currentLoopData = ['' => __('All'), '0' => __('Applied'), '1' => __('In review'), '2' => __('Approved'), '3' => __('Rejected')]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $active = $currentStatus === (string)$val;
        $cnt    = match((string)$val) {
            ''  => $counts['all'],
            '0', '1' => $val === '0' ? $user->workSubmissions()->where('status', 0)->count() : $user->workSubmissions()->where('status', 1)->count(),
            '2' => $counts['approved'],
            '3' => $counts['rejected'],
            default => 0
        };
    ?>
    <a href="<?php echo e(route('user.submissions.index', $val !== '' ? ['status' => $val] : [])); ?>"
       style="padding:10px 16px; text-decoration:none;
              color:<?php echo e($active ? 'var(--fg)' : 'var(--fg-3)'); ?>;
              font-weight:<?php echo e($active ? '600' : '500'); ?>;
              font-size:13px; white-space:nowrap; display:inline-block; margin-bottom:-1px;
              border-bottom:<?php echo e($active ? '2px solid var(--accent)' : '2px solid transparent'); ?>;">
        <?php echo e($label); ?>

        <span class="mono" style="font-size:11px; color:var(--fg-4); margin-left:4px;"><?php echo e($cnt); ?></span>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div style="display:flex; flex-direction:column; gap:10px;">
<?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<?php
    $work = $sub->work;
    $statusColors = [0 => '#F59E0B', 1 => '#F59E0B', 2 => '#22C55E', 3 => '#EF4444'];
    $statusLabels = [0 => __('Applied'), 1 => __('In review'), 2 => __('Approved'), 3 => __('Rejected')];
    $statusBg     = [0 => 'rgba(245,158,11,0.1)', 1 => 'rgba(245,158,11,0.1)', 2 => 'rgba(34,197,94,0.08)', 3 => 'rgba(239,68,68,0.08)'];
    $sc = $statusColors[$sub->status] ?? 'var(--fg-3)';
    $sl = $statusLabels[$sub->status] ?? '';
    $sbg = $statusBg[$sub->status] ?? 'transparent';
?>
<div class="card" style="padding:18px;">
    <div style="display:flex; align-items:flex-start; gap:16px;">
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px; flex-wrap:wrap;">
                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:500; background:<?php echo e($sbg); ?>; color:<?php echo e($sc); ?>; border:1px solid <?php echo e($sc); ?>33;">
                    <?php if($sub->status == 2): ?><i data-lucide="check-circle" style="width:11px;height:11px;"></i><?php endif; ?>
                    <?php if($sub->status == 3): ?><i data-lucide="x-circle" style="width:11px;height:11px;"></i><?php endif; ?>
                    <?php if($sub->status <= 1): ?><i data-lucide="clock" style="width:11px;height:11px;"></i><?php endif; ?>
                    <?php echo e($sl); ?>

                </span>
                <span style="font-size:11.5px; color:var(--fg-3);"><?php echo e($sub->created_at->diffForHumans()); ?></span>
                <?php if($sub->status <= 1 && $sub->deadline_at): ?>
                <span style="font-size:11.5px; color:<?php echo e($sub->deadline_at->diffInHours(now()) <= 2 ? 'var(--warn)' : 'var(--fg-3)'); ?>;">·
                    <?php echo e(__('Auto-approves in')); ?> <span class="sub-countdown mono"
                        data-expires="<?php echo e($sub->deadline_at->timestamp); ?>"
                        style="font-weight:600;"><?php echo e($sub->deadline_at->diffForHumans()); ?></span>
                </span>
                <?php endif; ?>
            </div>

            <div style="font-size:14px; font-weight:500; color:var(--fg); margin-bottom:<?php echo e(($sub->rejection_reason || $sub->proof_files) ? '10px' : '0'); ?>;">
                <?php echo e($work->title ?? 'Deleted Work'); ?>

                <?php if($work): ?>
                <span style="font-size:12px; color:var(--fg-3); font-weight:400; margin-left:8px;"><?php echo e($work->category?->name ?? ''); ?></span>
                <?php endif; ?>
            </div>

            <?php if($sub->rejection_reason && $sub->status == 3): ?>
            <div style="font-size:12.5px; color:#FCA5A5; padding:10px 12px; background:rgba(239,68,68,0.08); border-radius:8px; border-left:2px solid #EF4444; margin-bottom:10px;">
                <?php echo e($sub->rejection_reason); ?>

            </div>
            <?php endif; ?>

            <?php if($sub->proof_note && $sub->status != 0): ?>
            <div style="font-size:12.5px; color:var(--fg-2); padding:10px 12px; background:var(--surface-2); border-radius:8px; margin-bottom:10px;">
                <?php echo e(Str::limit($sub->proof_note, 120)); ?>

            </div>
            <?php endif; ?>

            <?php if($sub->proof_files && count($sub->proof_files)): ?>
            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                <?php $__currentLoopData = $sub->proof_files; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="chip mono" style="font-size:10.5px; display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="file" style="width:10px;height:10px;"></i>
                    <?php echo e(basename($f)); ?>

                </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
        </div>

        <div style="text-align:right; flex-shrink:0; display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
            <?php if($work && $sub->status == 2): ?>
            <div style="display:flex; align-items:baseline; gap:3px;">
                <span style="font-size:13px; color:var(--coin); font-family:ui-monospace,monospace;"><?php echo e(coinSymbol()); ?></span>
                <span class="mono" style="font-size:20px; font-weight:600; color:var(--coin);"><?php echo e(formatUsd($work->payout_usd)); ?></span>
            </div>
            <?php elseif($work): ?>
            <div style="display:flex; align-items:baseline; gap:3px; opacity:0.5;">
                <span style="font-size:13px; color:var(--coin); font-family:ui-monospace,monospace;"><?php echo e(coinSymbol()); ?></span>
                <span class="mono" style="font-size:20px; font-weight:600; color:var(--coin);"><?php echo e(formatUsd($work->payout_usd)); ?></span>
            </div>
            <?php endif; ?>

            <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">
                <?php if($work && $sub->status == 0): ?>
                <a href="<?php echo e(route('user.submissions.proof', $sub->id)); ?>" class="btn btn-primary" style="font-size:12px; padding:6px 12px;"><?php echo e(__('Submit proof')); ?></a>
                <?php endif; ?>
                <?php if($work): ?>
                <a href="<?php echo e(route('user.browse.works.show', $work->slug)); ?>" class="btn" style="font-size:12px; padding:6px 10px;" target="_blank">View</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <?php if($sub->status === 2 && $work && $work->poster_type === 2): ?>
    <?php
        $posterRated = \App\Models\Rating::where('rater_id', auth()->id())
            ->where('ratable_id', $work->id)
            ->where('ratable_type', \App\Models\Work::class)
            ->where('ratee_id', $work->poster_id)
            ->first();
    ?>
    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border);"
         x-data="{ open: false, starHover: 0, starPick: <?php echo e($posterRated ? $posterRated->rating : 0); ?> }">
        <?php if($posterRated): ?>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span style="font-size:11.5px; color:var(--fg-3);">Your rating for employer:</span>
            <div style="display:flex; gap:1px;">
                <?php for($i = 1; $i <= 5; $i++): ?>
                <span style="font-size:14px;line-height:1;color:<?php echo e($i <= $posterRated->rating ? '#F59E0B' : 'rgba(255,255,255,0.15)'); ?>;">★</span>
                <?php endfor; ?>
            </div>
            <a href="<?php echo e(route('user.public-profile', $work->poster->username)); ?>"
               style="font-size:11.5px; color:var(--accent); text-decoration:none; margin-left:auto;">View employer →</a>
        </div>
        <?php else: ?>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <button @click="open = !open"
                    style="display:inline-flex; align-items:center; gap:5px; font-size:12px; padding:5px 11px; border-radius:7px; background:rgba(245,158,11,0.1); color:#F59E0B; border:1px solid rgba(245,158,11,0.2); cursor:pointer;">
                <i data-lucide="star" style="width:12px;height:12px;"></i> Rate employer
            </button>
            <?php if($work->poster): ?>
            <a href="<?php echo e(route('user.public-profile', $work->poster->username)); ?>"
               style="font-size:11.5px; color:var(--fg-3); text-decoration:none; margin-left:auto;">View employer →</a>
            <?php endif; ?>
        </div>
        <div x-show="open" x-transition style="margin-top:12px; padding:14px; background:var(--surface-2); border-radius:10px;">
            <form method="POST" action="<?php echo e(route('user.ratings.store')); ?>" style="display:flex; flex-direction:column; gap:10px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ratable_type" value="work">
                <input type="hidden" name="ratable_id" value="<?php echo e($work->id); ?>">
                <input type="hidden" name="ratee_id" value="<?php echo e($work->poster_id); ?>">
                <input type="hidden" name="rating" :value="starPick">
                <div style="display:flex; gap:2px; align-items:center;">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <button type="button"
                            @mouseover="starHover = <?php echo e($i); ?>" @mouseleave="starHover = starPick" @click="starPick = <?php echo e($i); ?>"
                            style="font-size:24px;line-height:1;background:none;border:none;cursor:pointer;padding:0 1px;transition:transform .1s;"
                            :style="(starHover >= <?php echo e($i); ?> || starPick >= <?php echo e($i); ?>) ? 'color:#F59E0B;transform:scale(1.2)' : 'color:rgba(255,255,255,0.15)'">★</button>
                    <?php endfor; ?>
                    <span style="font-size:12px; color:var(--fg-3); margin-left:8px;" x-text="['','Poor','Fair','Good','Great','Excellent'][starPick] || ''"></span>
                </div>
                <textarea name="review" rows="2" style="width:100%; font-size:12px; resize:none;" class="input" placeholder="<?php echo e(__('Short review (optional)…')); ?>"></textarea>
                <div style="display:flex; gap:8px;">
                    <button type="submit" :disabled="starPick === 0" :class="starPick === 0 ? 'opacity-40' : ''" class="btn btn-primary" style="font-size:12px; padding:7px 14px;"><?php echo e(__('Submit')); ?></button>
                    <button type="button" @click="open = false" class="btn" style="font-size:12px; padding:7px 14px;"><?php echo e(__('Cancel')); ?></button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="card" style="padding:60px 24px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">📋</div>
    <div style="font-size:15px; font-weight:500; color:var(--fg); margin-bottom:8px;"><?php echo e(__('No submissions yet')); ?></div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;"><?php echo e(__('Browse instant jobs and start applying to earn coins.')); ?></p>
    <a href="<?php echo e(route('works.index')); ?>" class="btn btn-primary" style="font-size:13px;"><?php echo e(__('Browse instant jobs →')); ?></a>
</div>
<?php endif; ?>
</div>

<div style="margin-top:20px;"><?php echo e($submissions->withQueryString()->links()); ?></div>

<style>
@media (max-width: 768px) {
    .sub-stat-grid { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width: 480px) {
    .sub-stat-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/submissions/index.blade.php ENDPATH**/ ?>