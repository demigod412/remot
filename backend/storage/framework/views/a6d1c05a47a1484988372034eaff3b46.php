<?php $__env->startSection('title', 'Cashouts'); ?>
<?php $__env->startSection('page-title', 'Cashout Requests'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total cashouts</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;"><?php echo e(number_format($stats['total'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">all time</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Queued</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#F59E0B;"><?php echo e(number_format($stats['pending'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">awaiting payout</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Approved</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#22C55E;"><?php echo e(number_format($stats['approved'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">paid out</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Rejected</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#EF4444;"><?php echo e(number_format($stats['rejected'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">declined</div>
    </div>

</div>


<div class="jobstation-card" style="padding:0;overflow:hidden;">

    
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="<?php echo e(route('admin.cashouts.index')); ?>" style="display:flex;gap:10px;align-items:center;flex:1;flex-wrap:wrap;">

            <div style="position:relative;flex:1;min-width:200px;max-width:340px;">
                <i data-lucide="search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search by reference, username…"
                       style="padding-left:34px;width:100%;font-size:12.5px;">
            </div>

            <select name="status" style="width:auto;padding:7px 10px;font-size:12px;">
                <option value="">All statuses</option>
                <option value="0" <?php echo e(request('status')=='0' ? 'selected' : ''); ?>>Pending</option>
                <option value="1" <?php echo e(request('status')=='1' ? 'selected' : ''); ?>>Approved</option>
                <option value="2" <?php echo e(request('status')=='2' ? 'selected' : ''); ?>>Rejected</option>
            </select>

            <button type="submit" class="btn btn-sm">Filter</button>
            <?php if(request()->hasAny(['search','status'])): ?>
            <a href="<?php echo e(route('admin.cashouts.index')); ?>" class="btn btn-sm btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
        <a href="<?php echo e(route('admin.cashouts.export', request()->only(['search','status']))); ?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;font-size:12px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);color:var(--fg-2);text-decoration:none;transition:.15s;white-space:nowrap;flex-shrink:0;"
           onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-2)'">
            <i data-lucide="download" style="width:13px;height:13px;"></i> Export CSV
        </a>
    </div>

    
    <div style="display:grid;grid-template-columns:120px 1.5fr 130px 90px 90px 100px 90px 80px;gap:12px;padding:11px 18px;font-size:10.5px;color:var(--fg-3);text-transform:uppercase;letter-spacing:0.06em;font-weight:500;background:var(--bg-2);border-bottom:1px solid var(--border);">
        <span>Reference</span>
        <span>User</span>
        <span>Method</span>
        <span>Coins</span>
        <span>Payout</span>
        <span>Requested</span>
        <span>Status</span>
        <span></span>
    </div>

    
    <?php $__empty_1 = true; $__currentLoopData = $cashouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cashout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $statusMap   = [0=>'status-pending', 1=>'status-approved', 2=>'status-rejected'];
        $statusLabel = [0=>'Pending', 1=>'Approved', 2=>'Rejected'];
        $ini  = strtoupper(substr($cashout->user?->username ?? 'U', 0, 1));
        $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
        $clr  = $clrs[ord($ini) % count($clrs)];
    ?>
    <div style="display:grid;grid-template-columns:120px 1.5fr 130px 90px 90px 100px 90px 80px;gap:12px;padding:13px 18px;align-items:center;border-bottom:1px solid var(--border);font-size:12.5px;">

        <a href="<?php echo e(route('admin.cashouts.show', $cashout->id)); ?>"
           class="mono" style="font-size:11px;color:var(--accent);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?php echo e(Str::limit($cashout->reference ?? ('CX-'.str_pad($cashout->id,6,'0',STR_PAD_LEFT)), 16)); ?>

        </a>

        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
            <div style="width:22px;height:22px;border-radius:50%;background:<?php echo e($clr); ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:10px;font-weight:600;flex-shrink:0;"><?php echo e($ini); ?></div>
            <div style="min-width:0;">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($cashout->user?->username ?? '—'); ?></div>
                <div style="font-size:10.5px;color:var(--fg-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($cashout->user?->email ?? ''); ?></div>
            </div>
        </div>

        <span style="font-size:11.5px;color:var(--fg-2);"><?php echo e($cashout->payoutMethod?->name ?? '—'); ?></span>

        <span class="mono" style="color:var(--coin);"><?php echo e(formatCoins($cashout->coin_amount)); ?></span>

        <span class="mono"><?php echo e($cashout->payout_currency ?? ''); ?> <?php echo e(number_format($cashout->payout_amount ?? 0, 2)); ?></span>

        <span style="font-size:11px;color:var(--fg-3);"><?php echo e($cashout->created_at->format('M j, Y')); ?></span>

        <span class="status-pill <?php echo e($statusMap[$cashout->status] ?? 'status-draft'); ?>" style="font-size:10.5px;width:fit-content;">
            <?php echo e($statusLabel[$cashout->status] ?? '—'); ?>

        </span>

        <div style="display:flex;justify-content:flex-end;">
            <a href="<?php echo e(route('admin.cashouts.show', $cashout->id)); ?>" class="btn btn-sm">Review</a>
        </div>

    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div style="padding:48px;text-align:center;color:var(--fg-3);">
        <i data-lucide="arrow-up-circle" style="width:32px;height:32px;margin:0 auto 12px;display:block;opacity:0.3;"></i>
        <div style="font-size:13px;">No cashout requests found.</div>
    </div>
    <?php endif; ?>

</div>


<?php if($cashouts->hasPages()): ?>
<div style="display:flex;justify-content:flex-end;margin-top:16px;font-size:12.5px;">
    <?php echo e($cashouts->withQueryString()->links()); ?>

</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/cashouts/index.blade.php ENDPATH**/ ?>