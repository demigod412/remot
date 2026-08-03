<?php $__env->startSection('title', 'Withdraw Earnings'); ?>
<?php $__env->startSection('page-title', 'Withdraw Earnings'); ?>

<?php $__env->startSection('content'); ?>
<?php
    // Withdrawals draw on the USD earnings balance. This read coin_balance, which
    // gated the form on the wrong number entirely: a worker with plenty of coins
    // and no earnings was shown the form and would fail server-side validation.
    $minCashout  = gs()->min_cashout ?? 50;
    $userBalance = auth()->user()->usd_balance;
?>


<?php if($userBalance < $minCashout): ?>
<div style="display:flex; align-items:center; gap:12px; padding:14px 18px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:12px; margin-bottom:20px;">
    <i data-lucide="lock" style="width:18px; height:18px; color:#EF4444; flex-shrink:0;"></i>
    <div>
        <div style="font-size:13px; font-weight:600; color:#EF4444; margin-bottom:2px;">Insufficient balance</div>
        <div style="font-size:12px; color:var(--fg-3);">Minimum cashout is <strong class="mono" style="color:var(--fg);"><?php echo e(formatUsd($minCashout)); ?></strong>. You have <strong class="mono" style="color:var(--fg);"><?php echo e(formatUsd($userBalance)); ?></strong>. Keep earning to unlock.</div>
    </div>
</div>
<?php else: ?>
<div style="display:flex; align-items:center; gap:10px; padding:11px 16px; background:rgba(34,197,94,0.07); border:1px solid rgba(34,197,94,0.18); border-radius:10px; margin-bottom:20px; font-size:12.5px; color:var(--fg-3);">
    <i data-lucide="check-circle-2" style="width:14px; height:14px; color:#22C55E; flex-shrink:0;"></i>
    Minimum cashout: <strong class="mono" style="color:var(--fg); margin-left:4px;"><?php echo e(formatUsd($minCashout)); ?></strong>
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 300px; gap:20px;" class="cashout-grid">

    
    <div>
        <?php if($methods->isEmpty()): ?>
        <div class="card" style="padding:60px 24px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">💳</div>
            <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:8px;">No withdrawal methods available</div>
            <p style="font-size:13px; color:var(--fg-3); margin:0;">Please check back later or contact support.</p>
        </div>
        <?php else: ?>
        <form method="POST" action="<?php echo e(route('user.wallet.cashout.preview')); ?>">
            <?php echo csrf_field(); ?>

            
            <div class="card" style="padding:20px; margin-bottom:14px;">
                <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Select withdrawal method</h4>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php $__currentLoopData = $methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="cashout-method" style="display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:10px; border:1px solid var(--border); cursor:pointer; transition:border-color .15s;">
                        <input type="radio" name="payout_method_id" value="<?php echo e($method->id); ?>" required
                               style="accent-color:var(--accent); width:16px; height:16px; flex-shrink:0;">
                        <div style="flex:1;">
                            <div style="font-size:13px; font-weight:500; color:var(--fg);"><?php echo e($method->name); ?></div>
                            <div style="font-size:11.5px; color:var(--fg-3); margin-top:2px;">
                                <?php echo e($method->currency); ?>

                                · Min: <span class="mono"><?php echo e(formatUsd($method->min_coins)); ?></span>
                                <?php if($method->max_coins): ?>
                                · Max: <span class="mono"><?php echo e(formatUsd($method->max_coins)); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php $__errorArgs = ['payout_method_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <div style="font-size:11.5px; color:#EF4444; margin-top:8px;"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="card" style="padding:20px; margin-bottom:16px;">
                <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Withdrawal amount</h4>
                <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Coin amount</label>
                <input type="number" name="coin_amount" value="<?php echo e(old('coin_amount')); ?>" step="1" min="1"
                       style="width:100%; background:var(--surface-2); border:1px solid <?php echo e($errors->has('coin_amount') ? '#EF4444' : 'var(--border)'); ?>; border-radius:8px; padding:10px 12px; color:var(--fg); font-size:15px; font-family:ui-monospace,monospace; outline:none; letter-spacing:-0.5px;"
                       placeholder="e.g. 1000" required>
                <?php $__errorArgs = ['coin_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <div style="font-size:11.5px; color:#EF4444; margin-top:6px;"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-size:13px; display:inline-flex; align-items:center; gap:7px;">
                <i data-lucide="eye" style="width:14px; height:14px;"></i> Preview withdrawal
            </button>
        </form>
        <?php endif; ?>
    </div>

    
    <div>
        
        <div class="card" style="padding:22px; margin-bottom:14px; background:linear-gradient(135deg,rgba(47,84,235,0.08),transparent); border-color:rgba(47,84,235,0.2);">
            <div style="font-size:11px; color:var(--fg-3); margin-bottom:8px; text-transform:uppercase; letter-spacing:.08em;">Available balance</div>
            
            <div style="display:flex; align-items:baseline; gap:6px; margin-bottom:14px;">
                <span class="mono" style="font-size:34px; font-weight:600; letter-spacing:-1.5px; line-height:1;"><?php echo e(formatUsd(auth()->user()->usd_balance)); ?></span>
            </div>
            <a href="<?php echo e(route('user.wallet.cashout.history')); ?>" style="font-size:12px; color:var(--accent); text-decoration:none;">View history →</a>
        </div>

        
        <div class="card" style="padding:20px;">
            <div style="font-size:12.5px; font-weight:600; color:var(--fg); margin-bottom:14px;">How withdrawals work</div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php $__currentLoopData = [
                    ['icon'=>'clock','text'=>'Requests are processed within 1–3 business days'],
                    ['icon'=>'shield-check','text'=>'KYC verification required for first withdrawal'],
                    ['icon'=>'percent','text'=>'A small fee may apply depending on the method'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <i data-lucide="<?php echo e($tip['icon']); ?>" style="width:14px; height:14px; color:var(--fg-3); margin-top:1px; flex-shrink:0;"></i>
                    <span style="font-size:12px; color:var(--fg-3); line-height:1.55;"><?php echo e($tip['text']); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>

<style>
.cashout-method:has(input:checked) { border-color: var(--accent) !important; background: rgba(47,84,235,0.05); }
.cashout-method:hover { border-color: rgba(255,255,255,0.14) !important; }
@media (max-width: 860px) { .cashout-grid { grid-template-columns: 1fr !important; } }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/wallet/cashout.blade.php ENDPATH**/ ?>