<?php $__env->startSection('title', $work->title); ?>
<?php $__env->startSection('page-title', 'Work Detail'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-3);margin-bottom:20px;">
    <a href="<?php echo e(route('admin.works.index')); ?>" style="color:var(--fg-3);text-decoration:none;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Works</a>
    <i data-lucide="chevron-right" style="width:12px;height:12px;"></i>
    <span style="color:var(--fg-2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($work->title); ?></span>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    
    <div style="display:flex;flex-direction:column;gap:16px;">

        
        <div class="jobstation-card" style="overflow:hidden;">
            <?php if($work->cover_image): ?>
            <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.work_cover'), $work->cover_image)); ?>"
                 style="width:100%;height:200px;object-fit:cover;display:block;" alt="">
            <?php endif; ?>
            <div style="padding:24px;">
                <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;">
                    <h2 style="font-size:20px;font-weight:600;flex:1;line-height:1.3;"><?php echo e($work->title); ?></h2>
                    <div style="display:flex;gap:6px;flex-shrink:0;">
                        <?php
                            $apLabel = [0=>'Pending', 1=>'Approved', 2=>'Rejected'];
                            $apClass = [0=>'status-pending', 1=>'status-approved', 2=>'status-rejected'];
                        ?>
                        <span class="status-pill <?php echo e($apClass[$work->approval_status] ?? 'status-draft'); ?>">
                            <?php echo e($apLabel[$work->approval_status] ?? '—'); ?>

                        </span>
                        <?php if($work->work_status == 1 && $work->approval_status == 1): ?>
                        <span class="chip chip-instant" style="font-size:10px;">LIVE</span>
                        <?php elseif($work->work_status == 2): ?>
                        <span class="chip" style="font-size:10px;">CLOSED</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:14px;font-size:11.5px;color:var(--fg-3);margin-bottom:16px;">
                    <span style="display:flex;align-items:center;gap:5px;">
                        <i data-lucide="tag" style="width:13px;height:13px;"></i>
                        <?php echo e($work->category?->name ?? '—'); ?><?php echo e($work->subcategory ? ' · '.$work->subcategory->name : ''); ?>

                    </span>
                    <span style="display:flex;align-items:center;gap:5px;">
                        <i data-lucide="user" style="width:13px;height:13px;"></i>
                        Posted by <strong style="color:var(--fg-2);margin-left:3px;"><?php echo e($work->poster_type == 1 ? 'Admin' : ($work->poster?->username ?? '?')); ?></strong>
                    </span>
                    <span style="display:flex;align-items:center;gap:5px;">
                        <i data-lucide="calendar" style="width:13px;height:13px;"></i>
                        <?php echo e($work->created_at->format('M d, Y H:i')); ?>

                    </span>
                    <?php if($work->avg_minutes): ?>
                    <span style="display:flex;align-items:center;gap:5px;">
                        <i data-lucide="clock" style="width:13px;height:13px;"></i>
                        ~<?php echo e($work->avg_minutes); ?> min
                    </span>
                    <?php endif; ?>
                </div>

                <div style="font-size:13.5px;color:var(--fg-2);line-height:1.65;">
                    <?php echo richBody($work->description); ?>

                </div>

                <?php if($work->approval_status == 2 && $work->rejection_reason): ?>
                <div style="margin-top:16px;padding:12px 16px;border-radius:10px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);">
                    <div style="font-size:11.5px;color:#EF4444;font-weight:500;margin-bottom:4px;">Rejection reason</div>
                    <div style="font-size:13px;color:var(--fg-2);"><?php echo e($work->rejection_reason); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="jobstation-card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:600;margin:0;">Recent Submissions</h3>
                <a href="<?php echo e(route('admin.submissions.index', ['work_id' => $work->id])); ?>"
                   style="font-size:12px;color:var(--accent);text-decoration:none;">View all →</a>
            </div>

            <?php
                $statusMap = [0=>'pending',1=>'pending',2=>'approved',3=>'rejected'];
                $statusLabel = [0=>'Applied',1=>'Under Review',2=>'Approved',3=>'Rejected'];
            ?>

            <?php $__empty_1 = true; $__currentLoopData = $recentSubmissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $ini = strtoupper(substr($sub->worker?->username ?? 'U', 0, 1));
                $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
                $clr  = $clrs[ord($ini) % count($clrs)];
            ?>
            <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:<?php echo e($idx < count($recentSubmissions)-1 ? '1px solid var(--border)' : 'none'); ?>;">
                <div style="width:30px;height:30px;border-radius:50%;background:<?php echo e($clr); ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:600;flex-shrink:0;"><?php echo e($ini); ?></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:500;color:var(--fg);"><?php echo e($sub->worker?->username ?? 'Unknown'); ?></div>
                    <div style="font-size:11px;color:var(--fg-3);"><?php echo e($sub->created_at->format('M d, Y H:i')); ?></div>
                </div>
                <span class="status-pill status-<?php echo e($statusMap[$sub->status] ?? 'draft'); ?>" style="font-size:10.5px;"><?php echo e($statusLabel[$sub->status] ?? '—'); ?></span>
                <a href="<?php echo e(route('admin.submissions.show', $sub->id)); ?>"
                   style="padding:5px;border-radius:6px;color:var(--fg-3);text-decoration:none;"
                   onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'" onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                    <i data-lucide="arrow-right" style="width:14px;height:14px;display:block;"></i>
                </a>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div style="padding:40px;text-align:center;color:var(--fg-3);font-size:13px;">No submissions yet.</div>
            <?php endif; ?>
        </div>

    </div>

    
    <div style="display:flex;flex-direction:column;gap:16px;">

        
        <div class="jobstation-card" style="padding:20px;">
            <h3 style="font-size:13px;font-weight:600;margin:0 0 16px;">Submission stats</h3>
            <?php $__currentLoopData = [
                ['Total',   $submissionStats['total'],    'var(--fg)'],
                ['Pending', $submissionStats['pending'],  '#F59E0B'],
                ['Approved',$submissionStats['approved'], '#22C55E'],
                ['Rejected',$submissionStats['rejected'], '#EF4444'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$lbl, $val, $clr]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px;color:var(--fg-2);"><?php echo e($lbl); ?></span>
                <span class="mono" style="font-weight:600;color:<?php echo e($clr); ?>;"><?php echo e(number_format($val)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php $pct = $work->worker_slots > 0 ? min(100, ($submissionStats['approved'] / $work->worker_slots) * 100) : 0 ?>
            <div style="margin-top:14px;">
                <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--fg-3);margin-bottom:6px;">
                    <span>Slots filled</span>
                    <span class="mono"><?php echo e($submissionStats['approved']); ?> / <?php echo e($work->worker_slots); ?></span>
                </div>
                <div style="height:6px;background:var(--surface-3);border-radius:3px;">
                    <div style="width:<?php echo e($pct); ?>%;height:100%;background:var(--accent);border-radius:3px;"></div>
                </div>
            </div>
        </div>

        
        <div class="jobstation-card" style="padding:20px;">
            <h3 style="font-size:13px;font-weight:600;margin:0 0 14px;">Reward details</h3>
            <?php $__currentLoopData = [
                ['Per worker',   coinSymbol().number_format($work->coins_per_worker), 'var(--coin)'],
                ['Total budget', coinSymbol().number_format($work->total_coins), 'var(--coin)'],
                ['Distributed',  coinSymbol().number_format($submissionStats['approved'] * $work->coins_per_worker), '#22C55E'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$lbl, $val, $clr]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px;color:var(--fg-2);"><?php echo e($lbl); ?></span>
                <span class="mono" style="font-weight:600;color:<?php echo e($clr); ?>;"><?php echo e($val); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="jobstation-card" style="padding:20px;">
            <h3 style="font-size:13px;font-weight:600;margin:0 0 12px;">Actions</h3>

            <a href="<?php echo e(route('admin.works.edit', $work->id)); ?>"
               style="display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--fg-2);text-decoration:none;margin-bottom:2px;"
               onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'" onmouseout="this.style.background='transparent';this.style.color='var(--fg-2)'">
                <i data-lucide="edit-3" style="width:15px;height:15px;"></i> Edit work
            </a>

            <?php if($work->approval_status == 0): ?>
            <form method="POST" action="<?php echo e(route('admin.works.approve', $work->id)); ?>" style="margin-bottom:2px;">
                <?php echo csrf_field(); ?>
                <button type="submit" onclick="return confirm('Approve this work?')"
                        style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 12px;border-radius:8px;font-size:13px;color:#22C55E;background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;"
                        onmouseover="this.style.background='rgba(34,197,94,0.08)'" onmouseout="this.style.background='transparent'">
                    <i data-lucide="check-circle" style="width:15px;height:15px;"></i> Approve work
                </button>
            </form>

            <div x-data="{ open: false }">
                <button @click="open = !open"
                        style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 12px;border-radius:8px;font-size:13px;color:#EF4444;background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;"
                        onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
                    <i data-lucide="x-circle" style="width:15px;height:15px;"></i> Reject work
                </button>
                <div x-show="open" x-cloak x-transition style="margin-top:8px;padding:14px;border-radius:10px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);">
                    <form method="POST" action="<?php echo e(route('admin.works.reject', $work->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <textarea name="rejection_reason" rows="2" placeholder="Rejection reason…"
                                  style="width:100%;font-size:12.5px;margin-bottom:8px;resize:none;" required></textarea>
                        <button type="submit" style="width:100%;padding:8px;border-radius:8px;background:#EF4444;color:white;border:none;font-size:13px;font-weight:500;cursor:pointer;">
                            Confirm reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div style="height:1px;background:var(--border);margin:8px 0;"></div>

            <?php if($work->approval_status == 1): ?>
            <form method="POST" action="<?php echo e(route('admin.works.feature', $work->id)); ?>" style="margin-bottom:2px;">
                <?php echo csrf_field(); ?>
                <button type="submit"
                        style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 12px;border-radius:8px;font-size:13px;color:#F59E0B;background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;"
                        onmouseover="this.style.background='rgba(245,158,11,0.08)'" onmouseout="this.style.background='transparent'">
                    <i data-lucide="zap" style="width:15px;height:15px;"></i>
                    <?php echo e($work->is_featured && $work->featured_until?->isFuture() ? 'Unfeature work' : 'Feature work'); ?>

                </button>
            </form>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('admin.works.delete', $work->id)); ?>"
                  onsubmit="return confirm('Delete this work permanently?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button type="submit"
                        style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 12px;border-radius:8px;font-size:13px;color:#EF4444;background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;opacity:0.7;"
                        onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.opacity='1'" onmouseout="this.style.background='transparent';this.style.opacity='0.7'">
                    <i data-lucide="trash-2" style="width:15px;height:15px;"></i> Delete work
                </button>
            </form>
        </div>

    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/works/show.blade.php ENDPATH**/ ?>