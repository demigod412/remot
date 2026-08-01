<?php $__env->startSection('title', 'Pending Works'); ?>
<?php $__env->startSection('page-title', 'Pending Approval'); ?>

<?php $__env->startSection('content'); ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <span style="font-size:13px;color:var(--fg-3);"><?php echo e($works->total()); ?> work<?php echo e($works->total() != 1 ? 's' : ''); ?> awaiting review</span>
    <a href="<?php echo e(route('admin.works.index')); ?>" class="btn" style="padding:7px 16px;font-size:13px;">
        <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> All Works
    </a>
</div>

<?php $__empty_1 = true; $__currentLoopData = $works; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $work): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<div class="jobstation-card" style="margin-bottom:14px;overflow:hidden;">
    <div style="display:flex;gap:16px;padding:20px;">
        
        <?php if($work->cover_image): ?>
        <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.work_cover'), $work->cover_image)); ?>"
             style="width:120px;height:110px;object-fit:cover;border-radius:10px;border:1px solid var(--border);flex-shrink:0;" alt="">
        <?php else: ?>
        <div style="width:120px;height:110px;border-radius:10px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i data-lucide="briefcase" style="width:32px;height:32px;color:var(--fg-4);opacity:.4;"></i>
        </div>
        <?php endif; ?>

        
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px;">
                <h3 style="font-size:16px;font-weight:600;color:var(--fg);line-height:1.3;margin:0;"><?php echo e($work->title); ?></h3>
                <span style="display:inline-flex;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:500;background:rgba(245,158,11,0.12);color:#F59E0B;flex-shrink:0;">Pending</span>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:12px;color:var(--fg-3);margin-bottom:10px;">
                <span style="display:flex;align-items:center;gap:5px;"><i data-lucide="tag" style="width:12px;height:12px;"></i><?php echo e($work->category?->name ?? 'Uncategorized'); ?></span>
                <span style="display:flex;align-items:center;gap:5px;"><i data-lucide="users" style="width:12px;height:12px;"></i><?php echo e($work->worker_slots); ?> slots</span>
                <span style="display:flex;align-items:center;gap:5px;"><i data-lucide="coins" style="width:12px;height:12px;"></i><?php echo e(formatCoins($work->coins_per_worker)); ?> / worker</span>
                <?php if($work->avg_minutes): ?>
                <span style="display:flex;align-items:center;gap:5px;"><i data-lucide="clock" style="width:12px;height:12px;"></i>~<?php echo e($work->avg_minutes); ?> min</span>
                <?php endif; ?>
                <span>By <strong style="color:var(--fg-2);"><?php echo e($work->poster_type == 1 ? 'Admin' : ($work->poster?->username ?? '?')); ?></strong> · <?php echo e($work->created_at->diffForHumans()); ?></span>
            </div>

            <div style="font-size:13px;color:var(--fg-3);line-height:1.6;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                <?php echo nl2br(e(Str::limit(strip_tags($work->description), 200))); ?>

            </div>
        </div>
    </div>

    
    <div style="display:flex;align-items:center;gap:10px;padding:14px 20px;border-top:1px solid var(--border);">
        <form method="POST" action="<?php echo e(route('admin.works.approve', $work->id)); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary" style="padding:8px 20px;font-size:13px;"
                    onclick="return confirm('Approve this work?')">
                <i data-lucide="check" style="width:14px;height:14px;"></i> Approve
            </button>
        </form>

        <div x-data="{ open: false }" style="position:relative;">
            <button @click="open = !open"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#EF4444;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:.12s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.18)'"
                    onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                <i data-lucide="x" style="width:14px;height:14px;"></i> Reject
            </button>
            <div x-show="open" x-cloak x-transition
                 style="position:absolute;top:calc(100% + 8px);left:0;width:320px;z-index:30;">
                <div class="jobstation-card" style="padding:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                    <form method="POST" action="<?php echo e(route('admin.works.reject', $work->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <div style="font-size:12px;color:var(--fg-3);margin-bottom:8px;font-weight:500;">Rejection Reason</div>
                        <textarea name="rejection_reason" rows="3" placeholder="Explain why this work was rejected…"
                                  style="width:100%;font-size:13px;resize:none;margin-bottom:10px;" required></textarea>
                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;padding:8px;font-size:13px;">
                            Confirm Rejection
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <a href="<?php echo e(route('admin.works.show', $work->id)); ?>"
           style="margin-left:auto;font-size:12.5px;color:var(--fg-3);text-decoration:none;transition:.12s;"
           onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">
            Full details →
        </a>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="jobstation-card" style="padding:60px;text-align:center;">
    <i data-lucide="check-circle" style="width:40px;height:40px;margin:0 auto 12px;display:block;color:#22C55E;opacity:.3;"></i>
    <div style="font-size:15px;font-weight:500;color:var(--fg-2);">All caught up!</div>
    <div style="font-size:13px;color:var(--fg-3);margin-top:4px;">No works pending approval.</div>
    <a href="<?php echo e(route('admin.works.index')); ?>" class="btn" style="margin-top:16px;display:inline-flex;padding:8px 20px;font-size:13px;">Back to Works</a>
</div>
<?php endif; ?>

<?php if($works->hasPages()): ?>
<div style="margin-top:16px;display:flex;justify-content:flex-end;gap:6px;">
    <?php if(!$works->onFirstPage()): ?>
    <a href="<?php echo e($works->previousPageUrl()); ?>" class="btn" style="padding:7px 16px;font-size:12.5px;">Prev</a>
    <?php endif; ?>
    <?php if($works->hasMorePages()): ?>
    <a href="<?php echo e($works->nextPageUrl()); ?>" class="btn" style="padding:7px 16px;font-size:12.5px;">Next</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/works/pending.blade.php ENDPATH**/ ?>