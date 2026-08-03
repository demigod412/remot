<?php $__env->startSection('title', __('My tasks')); ?>

<?php $__env->startSection('content'); ?>
<div style="max-width:1000px;margin:0 auto;padding:32px 20px;">

    <h1 style="font-size:26px;font-weight:600;margin:0 0 6px;"><?php echo e(__('My tasks')); ?></h1>
    <p style="font-size:14px;color:var(--muted);margin:0 0 26px;">
        <?php echo e(__('Applications you have made and tasks assigned to you.')); ?>

    </p>

    <?php if(session('success')): ?>
        <div style="padding:13px 15px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;margin-bottom:20px;">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div style="padding:13px 15px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:20px;">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="border:1px solid var(--border);border-radius:10px;padding:18px;margin-bottom:12px;display:flex;align-items:center;gap:16px;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:15.5px;font-weight:600;margin-bottom:3px;"><?php echo e($s->work->title ?? __('Task removed')); ?></div>
                <div style="font-size:12.5px;color:var(--muted);">
                    <?php echo e(__('Applied')); ?> <?php echo e($s->created_at?->diffForHumans()); ?>

                    <?php if((float) $s->fee_paid > 0): ?> · <?php echo e(__('fee')); ?> <?php echo e(formatCoins($s->fee_paid)); ?> <?php endif; ?>
                    <?php if($s->deadline_at && $s->isOpenForWorker()): ?>
                        · <span style="color:<?php echo e($s->deadline_at->isPast() ? '#dc2626' : 'inherit'); ?>;">
                            <?php echo e(__('due')); ?> <?php echo e($s->deadline_at->diffForHumans()); ?>

                          </span>
                    <?php endif; ?>
                </div>
            </div>

            <span style="display:inline-block;padding:4px 11px;border-radius:99px;font-size:12px;font-weight:600;white-space:nowrap;
                  background:rgba(120,120,120,0.1);color:var(--muted);">
                <?php echo e($s->lifecycle_label); ?>

            </span>

            <?php if($s->isApprovedToWork()): ?>
                <a href="<?php echo e(route('user.tasks.show', $s->id)); ?>"
                   style="padding:8px 16px;border-radius:8px;background:var(--accent);color:#fff;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
                    <?php if($s->isOpenForWorker()): ?> <?php echo e(__('Open task')); ?> <?php else: ?> <?php echo e(__('View')); ?> <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="border:1px dashed var(--border);border-radius:10px;padding:48px;text-align:center;color:var(--muted);">
            <?php echo e(__('You have not applied to any tasks yet.')); ?>

        </div>
    <?php endif; ?>

    <div style="margin-top:20px;"><?php echo e($submissions->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/tasks/index.blade.php ENDPATH**/ ?>