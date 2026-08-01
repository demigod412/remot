<?php $__env->startSection('title', 'Works'); ?>
<?php $__env->startSection('page-title', 'Works Management'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total works</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;"><?php echo e(number_format($stats['total'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">all time</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Pending approval</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#F59E0B;"><?php echo e(number_format($stats['pending'])); ?></div>
        <div style="font-size:11.5px;margin-top:4px;">
            <?php if($stats['pending'] > 0): ?>
            <a href="<?php echo e(route('admin.works.pending')); ?>" style="color:var(--accent);text-decoration:none;">Review →</a>
            <?php else: ?>
            <span style="color:var(--fg-3);">all clear</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Live &amp; active</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:var(--urgent);"><?php echo e(number_format($stats['active'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">accepting workers</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Rejected</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#EF4444;"><?php echo e(number_format($stats['rejected'])); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">declined</div>
    </div>

</div>


<div class="jobstation-card" style="padding:0;overflow:hidden;">

    
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="<?php echo e(route('admin.works.index')); ?>" style="display:flex;gap:10px;align-items:center;flex:1;flex-wrap:wrap;">

            <div style="position:relative;flex:1;min-width:200px;max-width:340px;">
                <i data-lucide="search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search by title…"
                       style="padding-left:34px;width:100%;font-size:12.5px;">
            </div>

            <select name="approval" style="width:auto;padding:7px 10px;font-size:12px;">
                <option value="">All approvals</option>
                <option value="0" <?php echo e(request('approval')=='0' ? 'selected' : ''); ?>>Pending</option>
                <option value="1" <?php echo e(request('approval')=='1' ? 'selected' : ''); ?>>Approved</option>
                <option value="2" <?php echo e(request('approval')=='2' ? 'selected' : ''); ?>>Rejected</option>
            </select>

            <select name="category" style="width:auto;padding:7px 10px;font-size:12px;">
                <option value="">All categories</option>
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($cat->id); ?>" <?php echo e(request('category')==$cat->id ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <button type="submit" class="btn btn-sm">Filter</button>
            <?php if(request()->hasAny(['search','approval','category','status'])): ?>
            <a href="<?php echo e(route('admin.works.index')); ?>" class="btn btn-sm btn-ghost">Clear</a>
            <?php endif; ?>
        </form>

        <a href="<?php echo e(route('admin.works.create')); ?>" class="btn btn-sm btn-primary">
            <i data-lucide="plus" style="width:12px;height:12px;"></i> New work
        </a>
    </div>

    
    <div style="display:grid;grid-template-columns:90px 2fr 120px 80px 110px 100px 100px 80px;gap:12px;padding:11px 18px;font-size:10.5px;color:var(--fg-3);text-transform:uppercase;letter-spacing:0.06em;font-weight:500;background:var(--bg-2);border-bottom:1px solid var(--border);">
        <span>ID</span>
        <span>Title</span>
        <span>Category</span>
        <span>Reward</span>
        <span>Fill rate</span>
        <span>Posted</span>
        <span>Approval</span>
        <span></span>
    </div>

    
    <?php $__empty_1 = true; $__currentLoopData = $works; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $work): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $filled  = $work->submissions()->where('status', 2)->count();
        $slots   = max($work->worker_slots, 1);
        $pct     = min(($filled / $slots) * 100, 100);
        $approvalMap   = [0=>'pending', 1=>'approved', 2=>'rejected'];
        $approvalLabel = [0=>'Pending', 1=>'Approved', 2=>'Rejected'];
    ?>
    <div style="display:grid;grid-template-columns:90px 2fr 120px 80px 110px 100px 100px 80px;gap:12px;padding:13px 18px;align-items:center;border-bottom:1px solid var(--border);font-size:12.5px;">

        <span class="mono" style="font-size:11px;color:var(--fg-3);">WK-<?php echo e(str_pad($work->id, 4, '0', STR_PAD_LEFT)); ?></span>

        <div style="min-width:0;">
            <div style="font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($work->title); ?></div>
            <?php if($work->work_status == 1 && $work->approval_status == 1): ?>
                <span class="chip chip-instant" style="font-size:9.5px;padding:1px 6px;margin-top:2px;display:inline-block;">LIVE</span>
            <?php elseif($work->work_status == 2): ?>
                <span class="chip" style="font-size:9.5px;padding:1px 6px;margin-top:2px;display:inline-block;">CLOSED</span>
            <?php endif; ?>
        </div>

        <span style="font-size:11.5px;color:var(--fg-2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($work->category?->name ?? '—'); ?></span>

        <span class="mono" style="color:var(--coin);"><?php echo e(coinSymbol()); ?><?php echo e(number_format($work->coins_per_worker)); ?></span>

        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:50px;height:4px;background:var(--surface-3);border-radius:2px;flex-shrink:0;">
                <div style="width:<?php echo e($pct); ?>%;height:100%;background:var(--accent);border-radius:2px;"></div>
            </div>
            <span class="mono" style="font-size:10.5px;color:var(--fg-3);"><?php echo e($filled); ?>/<?php echo e($slots); ?></span>
        </div>

        <span style="font-size:11px;color:var(--fg-3);"><?php echo e($work->created_at->format('M j, Y')); ?></span>

        <span class="status-pill status-<?php echo e($approvalMap[$work->approval_status] ?? 'draft'); ?>" style="width:fit-content;">
            <?php echo e($approvalLabel[$work->approval_status] ?? '—'); ?>

        </span>

        <div style="display:flex;gap:6px;justify-content:flex-end;">
            <a href="<?php echo e(route('admin.works.show', $work->id)); ?>" class="btn btn-sm">View</a>
        </div>

    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div style="padding:48px;text-align:center;color:var(--fg-3);">
        <i data-lucide="briefcase" style="width:32px;height:32px;margin:0 auto 12px;display:block;opacity:0.3;"></i>
        <div style="font-size:13px;">No works found.</div>
    </div>
    <?php endif; ?>

</div>


<?php if($works->hasPages()): ?>
<div style="display:flex;justify-content:flex-end;margin-top:16px;font-size:12.5px;">
    <?php echo e($works->withQueryString()->links()); ?>

</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/works/index.blade.php ENDPATH**/ ?>