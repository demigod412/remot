<?php $__env->startSection('title', 'My Applications'); ?>
<?php $__env->startSection('page-title', 'My Applications'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <div style="display:flex; gap:2px; border-bottom:1px solid var(--border);">
        <?php $__currentLoopData = ['' => 'All', '0' => 'Pending', '1' => 'Reviewed', '2' => 'Shortlisted', '3' => 'Accepted', '4' => 'Rejected']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $active = request('status', '') === (string)$val; ?>
        <a href="<?php echo e(route('user.jobs.my-applications', $val !== '' ? ['status' => $val] : [])); ?>"
           style="padding:9px 13px; text-decoration:none; font-size:12.5px; white-space:nowrap; display:inline-block; margin-bottom:-1px;
                  color:<?php echo e($active ? 'var(--fg)' : 'var(--fg-3)'); ?>;
                  font-weight:<?php echo e($active ? '600' : '500'); ?>;
                  border-bottom:<?php echo e($active ? '2px solid var(--accent)' : '2px solid transparent'); ?>;">
            <?php echo e($label); ?>

        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <a href="<?php echo e(route('user.jobs.browse')); ?>" class="btn"
       style="font-size:12.5px; padding:7px 14px; display:inline-flex; align-items:center; gap:6px; flex-shrink:0; margin-bottom:1px;">
        <i data-lucide="search" style="width:13px; height:13px;"></i> Browse jobs
    </a>
</div>


<div style="display:flex; flex-direction:column; gap:10px;">
<?php $__empty_1 = true; $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $app): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<?php
    $adMap = [
        0 => ['Pending',     '#F59E0B', 'rgba(245,158,11,0.1)'],
        1 => ['Reviewed',    '#60A5FA', 'rgba(96,165,250,0.1)'],
        2 => ['Shortlisted', '#A78BFA', 'rgba(167,139,250,0.1)'],
        3 => ['Accepted',    '#22C55E', 'rgba(34,197,94,0.08)'],
        4 => ['Rejected',    '#EF4444', 'rgba(239,68,68,0.08)'],
    ];
    [$adLabel, $adColor, $adBg] = $adMap[$app->status] ?? $adMap[0];
?>
<div class="card" style="padding:18px;">
    <div style="display:flex; align-items:flex-start; gap:14px;">
        <?php if($app->listing->cover_image ?? null): ?>
        <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.work_cover'), $app->listing->cover_image)); ?>"
             alt="" style="width:52px; height:52px; border-radius:10px; object-fit:cover; flex-shrink:0;">
        <?php else: ?>
        <div style="width:52px; height:52px; border-radius:10px; background:rgba(47,84,235,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="briefcase" style="width:20px; height:20px; color:var(--accent); opacity:.5;"></i>
        </div>
        <?php endif; ?>

        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                <h3 style="font-size:14.5px; font-weight:600; color:var(--fg); margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:380px;">
                    <?php echo e($app->listing->title ?? 'Listing removed'); ?>

                </h3>
                <span style="font-size:11px; padding:3px 9px; border-radius:999px; background:<?php echo e($adBg); ?>; color:<?php echo e($adColor); ?>; border:1px solid <?php echo e($adColor); ?>33; font-weight:500; flex-shrink:0;">
                    <?php echo e($adLabel); ?>

                </span>
            </div>

            <?php if($app->listing): ?>
            <div style="font-size:12px; color:var(--fg-3); margin-bottom:7px; display:flex; flex-wrap:wrap; gap:5px; align-items:center;">
                <span><?php echo e($app->listing->employer->fullname ?? ''); ?></span>
                <?php if($app->listing->employer ?? null): ?>
                    <?php echo $__env->make('user.partials.rating-stars', ['user' => $app->listing->employer], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                <span style="opacity:.4;">·</span>
                <span><?php echo e($app->listing->locationTypeLabel); ?></span>
                <span style="opacity:.4;">·</span>
                <span><?php echo e($app->listing->employmentTypeLabel); ?></span>
            </div>
            <?php endif; ?>

            <div style="font-size:11.5px; color:var(--fg-4); margin-bottom:<?php echo e($app->employer_note ? '10px' : '6px'); ?>;">
                Applied <?php echo e($app->created_at->diffForHumans()); ?>

            </div>

            <?php if($app->employer_note): ?>
            <div style="font-size:12.5px; color:var(--fg-2); padding:10px 12px; background:var(--surface-2); border-radius:8px; border-left:2px solid var(--border); margin-bottom:10px;">
                <span style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em;">Employer's note</span>
                <?php echo e($app->employer_note); ?>

            </div>
            <?php endif; ?>

            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <?php if($app->listing): ?>
                <a href="<?php echo e(route('user.jobs.show', $app->listing->id)); ?>" class="btn" style="font-size:12px; padding:5px 12px;">View job</a>
                <?php endif; ?>
                <a href="<?php echo e(route('user.jobs.applications.thread', $app->id)); ?>"
                   style="display:inline-flex; align-items:center; gap:5px; font-size:12px; padding:5px 12px; border-radius:8px; background:rgba(96,165,250,0.07); color:#60A5FA; text-decoration:none; border:1px solid rgba(96,165,250,0.2); transition:background .15s;"
                   onmouseover="this.style.background='rgba(96,165,250,0.14)'" onmouseout="this.style.background='rgba(96,165,250,0.07)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Message
                </a>
                <?php if($app->status === 0 && $app->listing): ?>
                <form method="POST" action="<?php echo e(route('user.jobs.applications.withdraw', $app->id)); ?>" style="display:inline;"
                      onsubmit="return confirm('Withdraw this application?')">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn" style="font-size:12px; padding:5px 12px; color:#EF4444; border-color:rgba(239,68,68,0.25);">
                        Withdraw
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="card" style="padding:60px 24px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">📨</div>
    <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:8px;">No applications yet</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;">Browse available jobs and apply to ones that match your skills.</p>
    <a href="<?php echo e(route('user.jobs.browse')); ?>" class="btn btn-primary" style="font-size:13px;">Browse jobs →</a>
</div>
<?php endif; ?>
</div>

<div style="margin-top:20px;"><?php echo e($applications->withQueryString()->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/jobs/my-applications.blade.php ENDPATH**/ ?>