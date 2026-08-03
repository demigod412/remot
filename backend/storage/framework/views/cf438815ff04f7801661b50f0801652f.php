<?php $__env->startSection('title', 'Withdrawal History'); ?>
<?php $__env->startSection('page-title', 'Withdrawal History'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
    <div style="font-size:13px; color:var(--fg-3);">All your withdrawal requests</div>
    <a href="<?php echo e(route('user.wallet.cashout')); ?>" class="btn btn-primary" style="font-size:12.5px; padding:8px 16px; display:inline-flex; align-items:center; gap:6px;">
        <i data-lucide="plus" style="width:13px; height:13px;"></i> New withdrawal
    </a>
</div>


<div class="card" style="padding:0; overflow:hidden;">
    <div style="display:grid; grid-template-columns:1fr 110px 120px 90px 100px 38px; gap:0; border-bottom:1px solid var(--border); padding:11px 18px;">
        <div style="font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600;">Reference / Method</div>
        <div style="font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600; text-align:right;">Coins</div>
        <div style="font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600; text-align:right;">Payout</div>
        <div style="font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600; text-align:center;">Status</div>
        <div style="font-size:11px; color:var(--fg-4); text-transform:uppercase; letter-spacing:.06em; font-weight:600; text-align:right;">Date</div>
        <div></div>
    </div>

    <?php $__empty_1 = true; $__currentLoopData = $cashouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cashout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $stData = [
            0 => ['Pending',  '#F59E0B', 'rgba(245,158,11,0.1)'],
            1 => ['Approved', '#22C55E', 'rgba(34,197,94,0.08)'],
            2 => ['Rejected', '#EF4444', 'rgba(239,68,68,0.08)'],
        ];
        [$stLabel, $stColor, $stBg] = $stData[$cashout->status] ?? ['—', 'var(--fg-3)', 'transparent'];
    ?>
    <div class="hist-row" style="display:grid; grid-template-columns:1fr 110px 120px 90px 100px 38px; gap:0; padding:13px 18px; border-bottom:1px solid var(--border); transition:background .1s;">
        <div style="min-width:0;">
            <div style="font-size:13px; font-weight:500; color:var(--fg); margin-bottom:2px;"><?php echo e($cashout->payoutMethod->name ?? '—'); ?></div>
            <div class="mono" style="font-size:10.5px; color:var(--fg-4);"><?php echo e($cashout->reference); ?></div>
            <?php if($cashout->admin_note): ?>
            <div style="font-size:11.5px; color:var(--fg-3); margin-top:5px; display:flex; gap:5px; align-items:flex-start;">
                <i data-lucide="message-square" style="width:11px; height:11px; margin-top:1.5px; flex-shrink:0; color:var(--fg-4);"></i>
                <?php echo e($cashout->admin_note); ?>

            </div>
            <?php endif; ?>
        </div>
        <div class="mono" style="font-size:13px; font-weight:500; color:#EF4444; text-align:right; align-self:center;">
            
            −<?php echo e(formatUsd($cashout->net_coins_deducted)); ?>

        </div>
        <div style="font-size:13px; color:var(--fg-2); text-align:right; align-self:center;">
            <?php echo e($cashout->payout_currency); ?> <?php echo e(number_format($cashout->payout_amount, 2)); ?>

        </div>
        <div style="text-align:center; align-self:center;">
            <span style="font-size:11px; padding:3px 9px; border-radius:999px; background:<?php echo e($stBg); ?>; color:<?php echo e($stColor); ?>; border:1px solid <?php echo e($stColor); ?>40; font-weight:500; white-space:nowrap;">
                <?php echo e($stLabel); ?>

            </span>
        </div>
        <div style="font-size:11.5px; color:var(--fg-4); text-align:right; align-self:center;">
            <?php echo e($cashout->created_at->format('M d, Y')); ?>

        </div>
        <div style="align-self:center; text-align:right;">
            <a href="<?php echo e(route('user.wallet.cashout.receipt', $cashout->id)); ?>" target="_blank"
               title="View receipt"
               style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; color:var(--fg-3); transition:.15s;"
               onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'" onmouseout="this.style.background='';this.style.color='var(--fg-3)'">
                <i data-lucide="receipt" style="width:13px; height:13px;"></i>
            </a>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div style="padding:60px 24px; text-align:center;">
        <div style="font-size:32px; margin-bottom:12px;">📤</div>
        <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:8px;">No withdrawals yet</div>
        <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;">Your withdrawal history will appear here once you submit a request.</p>
        <a href="<?php echo e(route('user.wallet.cashout')); ?>" class="btn btn-primary" style="font-size:13px;">Request a withdrawal →</a>
    </div>
    <?php endif; ?>
</div>

<div style="margin-top:16px;"><?php echo e($cashouts->links()); ?></div>

<style>
.hist-row:hover { background: var(--surface-2); }
@media (max-width: 700px) {
    .hist-row, .hist-row + .hist-row { grid-template-columns: 1fr auto !important; }
    .hist-row > div:nth-child(3),
    .hist-row > div:nth-child(5) { display: none !important; }
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/wallet/cashout-history.blade.php ENDPATH**/ ?>