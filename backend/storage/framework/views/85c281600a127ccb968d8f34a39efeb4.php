<?php $__env->startSection('title', 'Sent Contracts'); ?>
<?php $__env->startSection('page-title', 'Sent Contracts'); ?>

<?php $__env->startSection('content'); ?>

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
        <?php $__currentLoopData = ['' => 'All', '0' => 'Offered', '1' => 'Accepted', '2' => 'Submitted', '3' => 'Completed', '4' => 'Declined', '5' => 'Cancelled', '6' => 'Disputed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('user.contracts.sent', $val !== '' ? ['status' => $val] : [])); ?>"
           style="font-size:12px; font-weight:500; padding:5px 11px; border-radius:7px; text-decoration:none; transition:all .14s;
                  <?php echo e(request('status') === $val ? 'background:var(--accent); color:white; border:1px solid var(--accent);' : 'background:var(--surface); color:var(--fg-2); border:1px solid var(--border);'); ?>">
            <?php echo e($label); ?>

        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <a href="<?php echo e(route('user.contracts.create')); ?>" class="btn btn-primary" style="font-size:13px; padding:7px 14px; text-decoration:none;">
        <i data-lucide="plus" style="width:14px; height:14px;"></i> New Contract
    </a>
</div>

<?php $__empty_1 = true; $__currentLoopData = $contracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<div class="card" style="padding:16px 18px; margin-bottom:10px; transition:box-shadow .14s;"
     onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.07)'" onmouseout="this.style.boxShadow=''">
    <div style="display:flex; align-items:flex-start; gap:14px;">

        <div style="width:38px; height:38px; border-radius:10px; background:var(--accent-soft); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="file-text" style="width:18px; height:18px; color:var(--accent);"></i>
        </div>

        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:6px;">
                <div style="font-size:14px; font-weight:600; color:var(--fg); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($contract->title); ?></div>
                <span class="badge badge-<?php echo e($contract->statusColor); ?>" style="flex-shrink:0; font-size:11px;"><?php echo e($contract->statusLabel); ?></span>
            </div>

            <div style="font-size:12px; color:var(--fg-3); margin-bottom:4px; display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                <span>To: <span style="color:var(--fg-2);"><?php echo e($contract->worker->fullname ?: $contract->worker->username); ?></span></span>
                <?php echo $__env->make('user.partials.rating-stars', ['user' => $contract->worker], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <span style="color:var(--border-strong);">·</span>
                <span class="mono" style="color:#E6C400; font-weight:600;"><?php echo e(coinSymbol()); ?><?php echo e(number_format($contract->amount)); ?></span>
                <?php if($contract->status === 3 && $contract->commission_amount > 0): ?>
                <span style="color:var(--fg-4);">(fee: <?php echo e(coinSymbol()); ?><?php echo e(number_format($contract->commission_amount)); ?>)</span>
                <?php endif; ?>
                <?php if($contract->deadline_at): ?>
                <span style="color:var(--border-strong);">·</span>
                <span>Due <?php echo e($contract->deadline_at->format('M d, Y')); ?></span>
                <?php endif; ?>
                <?php if($contract->isOverdue): ?>
                <span style="color:var(--danger); display:inline-flex; align-items:center; gap:3px;">
                    <i data-lucide="alert-circle" style="width:11px; height:11px;"></i> Overdue
                </span>
                <?php endif; ?>
            </div>

            <div style="font-size:11px; color:var(--fg-4); margin-bottom:10px;">
                Ref: <?php echo e($contract->reference); ?> · <?php echo e($contract->created_at->diffForHumans()); ?>

            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="<?php echo e(route('user.contracts.show', $contract->id)); ?>"
                   style="font-size:12px; font-weight:500; padding:5px 12px; border-radius:7px; background:var(--accent-soft); color:var(--accent); text-decoration:none; border:1px solid rgba(47,84,235,0.2); transition:all .14s;"
                   onmouseover="this.style.background='rgba(47,84,235,0.18)'" onmouseout="this.style.background='var(--accent-soft)'">
                    View
                </a>

                <?php if($contract->status === 2): ?>
                <form method="POST" action="<?php echo e(route('user.contracts.approve', $contract->id)); ?>" style="display:inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                            style="font-size:12px; font-weight:500; padding:5px 12px; border-radius:7px; background:rgba(34,197,94,0.1); color:#22C55E; border:1px solid rgba(34,197,94,0.2); cursor:pointer; font-family:inherit; transition:all .14s;"
                            onmouseover="this.style.background='rgba(34,197,94,0.18)'" onmouseout="this.style.background='rgba(34,197,94,0.1)'"
                            onclick="return confirm('Approve and release payment?')">
                        Approve & Pay
                    </button>
                </form>
                <?php endif; ?>

                <?php if(in_array($contract->status, [0, 1])): ?>
                <form method="POST" action="<?php echo e(route('user.contracts.cancel', $contract->id)); ?>" style="display:inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                            style="font-size:12px; font-weight:500; padding:5px 12px; border-radius:7px; background:rgba(239,68,68,0.08); color:var(--danger); border:1px solid rgba(239,68,68,0.2); cursor:pointer; font-family:inherit; transition:all .14s;"
                            onmouseover="this.style.background='rgba(239,68,68,0.14)'" onmouseout="this.style.background='rgba(239,68,68,0.08)'"
                            onclick="return confirm('Cancel this contract?<?php echo e($contract->status === 1 ? ' Coins will be refunded.' : ''); ?>')">
                        Cancel
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="card" style="padding:56px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">📄</div>
    <div style="font-size:15px; font-weight:500; color:var(--fg); margin-bottom:6px;">No contracts sent yet</div>
    <div style="font-size:13px; color:var(--fg-3); margin-bottom:20px;">Send a contract offer to hire someone for a task.</div>
    <a href="<?php echo e(route('user.contracts.create')); ?>" class="btn btn-primary" style="font-size:13px; text-decoration:none;">
        Send Your First Offer
    </a>
</div>
<?php endif; ?>

<?php echo e($contracts->withQueryString()->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/contracts/sent.blade.php ENDPATH**/ ?>