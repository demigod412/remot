<?php $__env->startSection('title', $work->title); ?>
<?php $__env->startSection('page-title', 'Task Details'); ?>

<?php $__env->startSection('content'); ?>

<div style="margin-bottom:16px;">
    <a href="<?php echo e(route('user.browse.works')); ?>"
       style="font-size:12.5px; color:var(--accent); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
        <i data-lucide="arrow-left" style="width:13px; height:13px;"></i> Back to Find Work
    </a>
</div>

<div style="display:grid; grid-template-columns:1fr 320px; gap:24px; align-items:start;" class="work-detail-grid">

    
    <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
            <span style="font-size:10.5px; text-transform:uppercase; font-weight:600; padding:3px 9px; border-radius:20px; background:rgba(255,122,89,0.1); color:var(--urgent); border:1px solid rgba(255,122,89,0.2);">⚡ Instant</span>
            <?php if($work->category): ?>
            <span style="font-size:11px; padding:3px 9px; border-radius:20px; background:var(--surface-2); color:var(--fg-2); border:1px solid var(--border);"><?php echo e($work->category->name); ?></span>
            <?php endif; ?>
            <?php if($work->time_limit): ?>
            <span style="font-size:11px; padding:3px 9px; border-radius:20px; background:var(--surface-2); color:var(--fg-2); border:1px solid var(--border);">~<?php echo e($work->time_limit); ?> min</span>
            <?php endif; ?>
        </div>

        <h1 style="font-size:24px; font-weight:600; letter-spacing:-0.5px; line-height:1.2; margin:0 0 10px; color:var(--fg);"><?php echo e($work->title); ?></h1>

        <div style="display:flex; gap:12px; align-items:center; margin-bottom:22px; font-size:12.5px; color:var(--fg-3); flex-wrap:wrap;">
            <?php if($poster = $work->poster): ?>
            <span>by <?php echo e($poster->username); ?></span>
            <span style="color:var(--border-strong);">·</span>
            <?php endif; ?>
            <span>Posted <?php echo e($work->created_at->diffForHumans()); ?></span>
        </div>

        
        <div class="card" style="padding:22px; margin-bottom:16px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px;">What you'll do</div>
            <div class="work-prose" style="font-size:14px; color:var(--fg-2); line-height:1.65;">
                <?php echo richBody($work->description); ?>

            </div>
        </div>

        
        <?php if($work->requirements): ?>
        <div class="card" style="padding:22px; margin-bottom:16px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px;">Requirements</div>
            <div style="display:flex; flex-direction:column; gap:9px; font-size:13.5px;">
                <?php $__currentLoopData = explode("\n", $work->requirements); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(trim($req)): ?>
                <div style="display:flex; gap:9px; align-items:flex-start;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="#22C55E" stroke-width="1.8" stroke-linecap="round" style="flex-shrink:0; margin-top:2px;"><path d="M3 9l4 4 8-8"/></svg>
                    <span style="color:var(--fg-2);"><?php echo e(trim($req)); ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        
        <?php if($work->instructions): ?>
        <div class="card" style="padding:22px; margin-bottom:16px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px;">Proof required</div>
            <div class="work-prose" style="font-size:13.5px; color:var(--fg-2); line-height:1.6;">
                <?php echo richBody($work->instructions); ?>

            </div>
        </div>
        <?php endif; ?>

        
        <?php if($similar->isNotEmpty()): ?>
        <div style="margin-top:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:12px;">Similar tasks</div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php $__currentLoopData = $similar->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('user.browse.works.show', $s->slug)); ?>" style="text-decoration:none; display:block;">
                    <div class="card" style="padding:13px 16px; display:flex; align-items:center; gap:14px;">
                        <div style="width:28px; height:28px; border-radius:7px; background:rgba(255,122,89,0.12); color:var(--urgent); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px;">⚡</div>
                        <div style="flex:1; font-size:13.5px; font-weight:500; color:var(--fg);"><?php echo e(Str::limit($s->title, 60)); ?></div>
                        <span class="mono" style="font-size:13px; font-weight:600; color:#E6C400; flex-shrink:0;"><?php echo e(coinSymbol()); ?><?php echo e(number_format($s->coins_per_worker)); ?></span>
                    </div>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    
    <aside style="position:sticky; top:20px;">
        <div class="card" style="padding:22px;">
            <div style="text-align:center; padding-bottom:18px; border-bottom:1px solid var(--border); margin-bottom:18px;">
                <div style="font-size:11px; color:var(--fg-3); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.08em;">Reward</div>
                <div class="mono" style="font-size:34px; font-weight:600; letter-spacing:-1px; color:var(--fg);"><?php echo e(coinSymbol()); ?><?php echo e(number_format($work->coins_per_worker, 2)); ?></div>
                <div style="font-size:12px; color:var(--fg-3); margin-top:6px;">paid instantly on approval</div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; margin-bottom:18px;">
                <span style="color:var(--fg-3);">Spots available</span>
                <span style="font-weight:600; color:<?php echo e($slotsRemaining < 5 ? 'var(--urgent)' : 'var(--fg)'); ?>;"><?php echo e($slotsRemaining); ?> <span style="color:var(--fg-3); font-weight:400;">of <?php echo e($work->worker_slots); ?></span></span>
            </div>

            <?php if($work->requires_kyc && auth('web')->user()->kyc_status !== 1): ?>
            <div style="font-size:12px; color:#b45309; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:9px; padding:11px 13px; margin-bottom:14px; line-height:1.5;">
                🔒 This task requires KYC verification. <a href="<?php echo e(route('user.kyc')); ?>" style="color:#b45309; text-decoration:underline;">Verify now →</a>
            </div>
            <?php endif; ?>

            <?php if($alreadyApplied && $userSubmission): ?>
            <div style="text-align:center; padding:11px; background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.25); border-radius:8px; font-size:13px; color:#22C55E; font-weight:500; margin-bottom:10px;">✓ Already started</div>
            <a href="<?php echo e(route('user.submissions.proof', $userSubmission->id)); ?>" class="btn btn-primary" style="width:100%; justify-content:center; padding:11px; font-size:13.5px;">Submit / view proof →</a>
            <?php elseif($slotsRemaining <= 0): ?>
            <button disabled class="btn" style="width:100%; justify-content:center; padding:12px; font-size:14px; opacity:0.5; cursor:not-allowed;">No spots remaining</button>
            <?php else: ?>
            <form method="POST" action="<?php echo e(route('user.browse.works.start', $work->slug)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-size:14px;">
                    ⚡ <?php echo e($canReapply ? 'Do it again' : 'Start task now'); ?>

                </button>
            </form>
            <div style="text-align:center; font-size:11.5px; color:var(--fg-3); margin-top:10px;">Timer starts when you click. Submit before the deadline.</div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<style>
.work-prose p { margin: 0 0 10px; }
.work-prose p:last-child { margin-bottom: 0; }
.work-prose h2, .work-prose h3 { font-size: 14px; font-weight: 600; color: var(--fg); margin: 14px 0 8px; }
.work-prose ol, .work-prose ul { padding-left: 18px; margin: 8px 0 10px; }
.work-prose li { margin-bottom: 5px; line-height: 1.55; }
.work-prose strong { font-weight: 600; color: var(--fg); }
.work-prose a { color: var(--accent); }
@media (max-width:980px) { .work-detail-grid { grid-template-columns: 1fr !important; } }
</style>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/works/detail.blade.php ENDPATH**/ ?>