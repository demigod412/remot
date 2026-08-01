<?php $__env->startSection('title', 'Submissions'); ?>
<?php $__env->startSection('page-title', 'Moderation Queue'); ?>

<?php $__env->startSection('content'); ?>


<?php if($filterWork): ?>
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(47,84,235,0.08);border:1px solid rgba(47,84,235,0.2);margin-bottom:16px;font-size:13px;">
    <i data-lucide="briefcase" style="width:15px;height:15px;color:var(--accent);flex-shrink:0;"></i>
    <span style="color:var(--fg-2);">Filtered by: <strong style="color:var(--fg);"><?php echo e($filterWork->title); ?></strong></span>
    <a href="<?php echo e(route('admin.submissions.index')); ?>" style="margin-left:auto;font-size:11.5px;color:var(--fg-3);text-decoration:none;">Clear ×</a>
</div>
<?php endif; ?>


<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;"><?php echo e(number_format($stats['total'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">all time</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Pending review</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#F59E0B;"><?php echo e(number_format($stats['pending'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">need moderation</div>
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
        <form method="GET" action="<?php echo e(route('admin.submissions.index')); ?>" style="display:flex;gap:10px;align-items:center;flex:1;flex-wrap:wrap;">
            <?php if($filterWork): ?>
                <input type="hidden" name="work_id" value="<?php echo e($filterWork->id); ?>">
            <?php endif; ?>

            
            <div style="position:relative;flex:1;min-width:200px;max-width:340px;">
                <i data-lucide="search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search by worker, work title…"
                       style="padding-left:34px;width:100%;font-size:12.5px;">
            </div>

            
            <select name="status" style="width:auto;padding:7px 10px;font-size:12px;">
                <option value="">All statuses</option>
                <option value="0" <?php echo e(request('status')=='0' ? 'selected' : ''); ?>>Applied</option>
                <option value="1" <?php echo e(request('status')=='1' ? 'selected' : ''); ?>>Under Review</option>
                <option value="2" <?php echo e(request('status')=='2' ? 'selected' : ''); ?>>Approved</option>
                <option value="3" <?php echo e(request('status')=='3' ? 'selected' : ''); ?>>Rejected</option>
            </select>

            <button type="submit" class="btn btn-sm">Filter</button>
            <?php if(request()->hasAny(['search','status'])): ?>
            <a href="<?php echo e(route('admin.submissions.index', $filterWork ? ['work_id'=>$filterWork->id] : [])); ?>" class="btn btn-sm btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    
    <div style="display:grid;grid-template-columns:100px 1fr 130px 130px 90px 90px 110px;gap:14px;padding:11px 18px;font-size:10.5px;color:var(--fg-3);text-transform:uppercase;letter-spacing:0.06em;font-weight:500;background:var(--bg-2);border-bottom:1px solid var(--border);">
        <span>ID</span>
        <span>Work</span>
        <span>Worker</span>
        <span>Reward</span>
        <span>Submitted</span>
        <span>Status</span>
        <span></span>
    </div>

    
    <?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $statusMap = [0=>'pending',1=>'pending',2=>'approved',3=>'rejected'];
        $statusLabel = [0=>'Applied',1=>'Under Review',2=>'Approved',3=>'Rejected'];
        $ini = strtoupper(substr($sub->worker?->username ?? 'U', 0, 1));
        $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
        $clr  = $clrs[ord($ini) % count($clrs)];
    ?>
    <div style="display:grid;grid-template-columns:100px 1fr 130px 130px 90px 90px 110px;gap:14px;padding:13px 18px;align-items:center;border-bottom:1px solid var(--border);font-size:12.5px;">

        <span class="mono" style="font-size:11px;color:var(--fg-3);">SB-<?php echo e(str_pad($sub->id, 5, '0', STR_PAD_LEFT)); ?></span>

        <div style="min-width:0;">
            <div style="font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($sub->work?->title ?? '—'); ?></div>
            <div style="font-size:10.5px;color:var(--fg-3);"><?php echo e(coinSymbol()); ?><?php echo e(number_format($sub->work?->coins_per_worker ?? 0)); ?> reward</div>
        </div>

        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
            <div style="width:22px;height:22px;border-radius:50%;background:<?php echo e($clr); ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:10px;font-weight:600;flex-shrink:0;"><?php echo e($ini); ?></div>
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11.5px;"><?php echo e($sub->worker?->username ?? '—'); ?></span>
        </div>

        <span class="mono" style="color:var(--coin);"><?php echo e(coinSymbol()); ?><?php echo e(number_format($sub->work?->coins_per_worker ?? 0)); ?></span>

        <span style="font-size:11px;color:var(--fg-3);"><?php echo e($sub->created_at->diffForHumans(null, true)); ?></span>

        <span class="status-pill status-<?php echo e($statusMap[$sub->status] ?? 'draft'); ?>" style="width:fit-content;">
            <?php echo e($statusLabel[$sub->status] ?? 'Unknown'); ?>

        </span>

        <div style="display:flex;gap:6px;justify-content:flex-end;">
            <a href="<?php echo e(route('admin.submissions.show', $sub->id)); ?>" class="btn btn-sm">Review</a>
        </div>

    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div style="padding:48px;text-align:center;color:var(--fg-3);">
        <i data-lucide="inbox" style="width:32px;height:32px;margin:0 auto 12px;display:block;opacity:0.3;"></i>
        <div style="font-size:13px;">No submissions found.</div>
    </div>
    <?php endif; ?>

</div>


<?php if($submissions->hasPages()): ?>
<div style="display:flex;justify-content:flex-end;margin-top:16px;font-size:12.5px;">
    <?php echo e($submissions->withQueryString()->links()); ?>

</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/submissions/index.blade.php ENDPATH**/ ?>