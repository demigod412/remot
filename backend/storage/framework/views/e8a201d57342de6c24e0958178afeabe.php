<?php $__env->startSection('title', 'My Job Listings'); ?>
<?php $__env->startSection('page-title', 'My Listings'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <div style="display:flex; gap:2px; border-bottom:1px solid var(--border);">
        <?php $__currentLoopData = ['' => 'All', '0' => 'Pending', '1' => 'Active', '2' => 'Closed', '3' => 'Rejected']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $active = request('status', '') === (string)$val; ?>
        <a href="<?php echo e(route('user.jobs.listings.index', $val !== '' ? ['status' => $val] : [])); ?>"
           style="padding:9px 14px; text-decoration:none; font-size:13px; white-space:nowrap; display:inline-block; margin-bottom:-1px;
                  color:<?php echo e($active ? 'var(--fg)' : 'var(--fg-3)'); ?>;
                  font-weight:<?php echo e($active ? '600' : '500'); ?>;
                  border-bottom:<?php echo e($active ? '2px solid var(--accent)' : '2px solid transparent'); ?>;">
            <?php echo e($label); ?>

        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <a href="<?php echo e(route('user.jobs.listings.create')); ?>" class="btn btn-primary"
       style="font-size:12.5px; padding:8px 16px; display:inline-flex; align-items:center; gap:6px; flex-shrink:0; margin-bottom:1px;">
        <i data-lucide="plus" style="width:13px; height:13px;"></i> Post a job
    </a>
</div>


<div style="display:flex; flex-direction:column; gap:10px;">
<?php $__empty_1 = true; $__currentLoopData = $listings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<?php
    $sdMap = [
        0 => ['Pending',  '#F59E0B', 'rgba(245,158,11,0.1)'],
        1 => ['Active',   '#22C55E', 'rgba(34,197,94,0.08)'],
        2 => ['Closed',   '#9CA3AF', 'rgba(156,163,175,0.08)'],
        3 => ['Rejected', '#EF4444', 'rgba(239,68,68,0.08)'],
    ];
    [$sdLabel, $sdColor, $sdBg] = $sdMap[$listing->status] ?? $sdMap[0];
?>
<div class="card" style="padding:18px;">
    <div style="display:flex; align-items:flex-start; gap:14px;">
        <?php if($listing->cover_image): ?>
        <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image)); ?>"
             alt="<?php echo e($listing->title); ?>" style="width:56px; height:56px; border-radius:10px; object-fit:cover; flex-shrink:0;">
        <?php else: ?>
        <div style="width:56px; height:56px; border-radius:10px; background:rgba(47,84,235,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="briefcase" style="width:22px; height:22px; color:var(--accent); opacity:.5;"></i>
        </div>
        <?php endif; ?>

        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:7px; flex-wrap:wrap;">
                <h3 style="font-size:14.5px; font-weight:600; color:var(--fg); margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:420px;"><?php echo e($listing->title); ?></h3>
                <span style="font-size:11px; padding:3px 9px; border-radius:999px; background:<?php echo e($sdBg); ?>; color:<?php echo e($sdColor); ?>; border:1px solid <?php echo e($sdColor); ?>33; font-weight:500; flex-shrink:0;">
                    <?php echo e($sdLabel); ?>

                </span>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:5px; margin-bottom:10px; align-items:center;">
                <?php if($listing->category): ?>
                <span style="font-size:11px; color:var(--fg-3); display:inline-flex; align-items:center; gap:3px;">
                    <i data-lucide="tag" style="width:10px; height:10px;"></i> <?php echo e($listing->category->name); ?>

                </span>
                <span style="font-size:11px; color:var(--fg-4);">·</span>
                <?php endif; ?>
                <span style="font-size:11px; color:var(--fg-3); display:inline-flex; align-items:center; gap:3px;">
                    <i data-lucide="map-pin" style="width:10px; height:10px;"></i> <?php echo e($listing->locationTypeLabel); ?>

                </span>
                <span style="font-size:11px; color:var(--fg-4);">·</span>
                <span style="font-size:11px; color:var(--fg-3); display:inline-flex; align-items:center; gap:3px;">
                    <i data-lucide="clock" style="width:10px; height:10px;"></i> <?php echo e($listing->employmentTypeLabel); ?>

                </span>
                <span style="font-size:11px; color:var(--fg-4);">·</span>
                <span style="font-size:11px; color:var(--fg-2); font-weight:500; display:inline-flex; align-items:center; gap:3px;">
                    <i data-lucide="users" style="width:10px; height:10px;"></i>
                    <?php echo e($listing->application_count); ?> applicant<?php echo e($listing->application_count !== 1 ? 's' : ''); ?>

                </span>
                <?php if($listing->closes_at): ?>
                <span style="font-size:11px; color:var(--fg-4);">·</span>
                <span style="font-size:11px; color:var(--fg-3); display:inline-flex; align-items:center; gap:3px;">
                    <i data-lucide="calendar" style="width:10px; height:10px;"></i> Closes <?php echo e($listing->closes_at->format('M d, Y')); ?>

                </span>
                <?php endif; ?>
            </div>

            <?php if($listing->status === 3 && $listing->rejection_reason): ?>
            <div style="font-size:12px; color:#FCA5A5; padding:8px 12px; background:rgba(239,68,68,0.08); border-radius:8px; border-left:2px solid #EF4444; margin-bottom:10px; display:flex; gap:6px; align-items:flex-start;">
                <i data-lucide="alert-circle" style="width:12px; height:12px; color:#EF4444; margin-top:1.5px; flex-shrink:0;"></i>
                <?php echo e($listing->rejection_reason); ?>

            </div>
            <?php endif; ?>

            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="<?php echo e(route('user.jobs.listings.show', $listing->id)); ?>" class="btn btn-primary" style="font-size:12px; padding:6px 14px;">View details</a>
                <?php if(in_array($listing->status, [0, 3])): ?>
                <a href="<?php echo e(route('user.jobs.listings.edit', $listing->id)); ?>" class="btn" style="font-size:12px; padding:6px 12px;">Edit</a>
                <?php endif; ?>
                <?php if($listing->status === 1): ?>
                <form method="POST" action="<?php echo e(route('user.jobs.listings.close', $listing->id)); ?>" style="display:inline;"
                      onsubmit="return confirm('Close this listing?')">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn" style="font-size:12px; padding:6px 12px; color:#F59E0B; border-color:rgba(245,158,11,0.3);">
                        Close listing
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('user.jobs.listings.delete', $listing->id)); ?>" style="display:inline;"
                      onsubmit="return confirm('Delete this listing? This cannot be undone.')">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn" style="font-size:12px; padding:6px 12px; color:#EF4444; border-color:rgba(239,68,68,0.25);">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="card" style="padding:60px 24px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">💼</div>
    <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:8px;">No job listings yet</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;">Post a job to find talented people for your next project.</p>
    <a href="<?php echo e(route('user.jobs.listings.create')); ?>" class="btn btn-primary" style="font-size:13px;">Post your first job</a>
</div>
<?php endif; ?>
</div>

<div style="margin-top:20px;"><?php echo e($listings->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/jobs/listings/index.blade.php ENDPATH**/ ?>