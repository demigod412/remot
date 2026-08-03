<?php $__env->startSection('title', 'Withdrawal Accounts'); ?>
<?php $__env->startSection('page-title', 'Withdrawal Accounts'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:grid; grid-template-columns:1fr 360px; gap:20px;" class="pa-grid">

    
    <div>
        <div style="font-size:13px; color:var(--fg-3); margin-bottom:16px;">
            Save your payout account details so you don't have to re-enter them every time you withdraw.
        </div>

        <?php if($accounts->isEmpty()): ?>
        <div class="card" style="padding:50px 24px; text-align:center; margin-bottom:14px;">
            <div style="font-size:28px; margin-bottom:10px;">🏦</div>
            <div style="font-size:14px; font-weight:500; color:var(--fg); margin-bottom:6px;">No saved accounts yet</div>
            <p style="font-size:13px; color:var(--fg-3); margin:0;">Add a withdrawal account using the form on the right.</p>
        </div>
        <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="card" style="padding:16px 18px;">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <div style="width:38px; height:38px; border-radius:10px; background:rgba(47,84,235,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i data-lucide="credit-card" style="width:17px; height:17px; color:var(--accent);"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                            <div style="font-size:13.5px; font-weight:500; color:var(--fg);">
                                <?php echo e($account->label ?: ($account->payoutMethod->name ?? '—')); ?>

                            </div>
                            <?php if($account->is_default): ?>
                            <span style="font-size:10px; padding:2px 7px; border-radius:999px; background:rgba(47,84,235,0.12); color:var(--accent); border:1px solid rgba(47,84,235,0.2); font-weight:600;">DEFAULT</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:12px; color:var(--fg-3); margin-bottom:8px;">
                            <?php echo e($account->payoutMethod->name ?? '—'); ?>

                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px;">
                            <?php $__currentLoopData = $account->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="mono" style="font-size:11.5px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-2);">
                                <?php echo e(ucfirst(str_replace('_',' ',$key))); ?>: <?php echo e($val); ?>

                            </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <?php if(! $account->is_default): ?>
                            <form method="POST" action="<?php echo e(route('user.wallet.payout-accounts.default', $account->id)); ?>" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn" style="font-size:11.5px; padding:5px 12px;">Set as default</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo e(route('user.wallet.payout-accounts.delete', $account->id)); ?>" style="display:inline;"
                                  onsubmit="return confirm('Remove this account?')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn" style="font-size:11.5px; padding:5px 12px; color:#EF4444; border-color:rgba(239,68,68,0.25);">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
    </div>

    
    
    <div x-data="{ selectedMethodId: '<?php echo e(old('payout_method_id', '')); ?>' }">

        <?php if($methods->isEmpty()): ?>
        <div class="card" style="padding:40px 24px; text-align:center; margin-bottom:12px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(47,84,235,0.08); border:1px solid rgba(47,84,235,0.18); display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
                <i data-lucide="banknote" style="width:22px; height:22px; color:var(--accent);"></i>
            </div>
            <div style="font-size:13.5px; font-weight:600; color:var(--fg); margin-bottom:6px;">No withdrawal methods configured</div>
            <p style="font-size:12px; color:var(--fg-3); margin:0; line-height:1.6;">The platform hasn't set up any payout methods yet. Please check back later or contact support.</p>
        </div>
        <?php else: ?>
        <div class="card" style="padding:22px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:16px;">Add new account</div>

            <form method="POST" action="<?php echo e(route('user.wallet.payout-accounts.store')); ?>">
                <?php echo csrf_field(); ?>
                <div style="display:flex; flex-direction:column; gap:14px;">

                    <div>
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Withdrawal method <span style="color:#EF4444;">*</span></label>
                        <select name="payout_method_id" required
                                @change="selectedMethodId = $event.target.value"
                                style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 10px; color:var(--fg); font-size:13px; outline:none;">
                            <option value="">Select method…</option>
                            <?php $__currentLoopData = $methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($m->id); ?>" <?php echo e(old('payout_method_id') == $m->id ? 'selected' : ''); ?>><?php echo e($m->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['payout_method_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div style="font-size:11.5px; color:#EF4444; margin-top:4px;"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Label <span style="font-size:10.5px; color:var(--fg-4);">(optional)</span></label>
                        <input type="text" name="label" value="<?php echo e(old('label')); ?>" maxlength="60"
                               style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none;"
                               placeholder="e.g. My M-Pesa, Business Account">
                    </div>

                    
                    <?php $__currentLoopData = $methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <template x-if="selectedMethodId === '<?php echo e($m->id); ?>'">
                            <div style="display:flex; flex-direction:column; gap:14px;">
                                <?php $mFields = $m->form->form_data ?? []; ?>

                                <?php $__empty_1 = true; $__currentLoopData = $mFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $fName  = $field['name'] ?? 'account';
                                        $fLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fName));
                                        $fReq   = (bool) ($field['required'] ?? false);
                                    ?>
                                    <div>
                                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">
                                            <?php echo e($fLabel); ?><?php if($fReq): ?> <span style="color:#EF4444;">*</span><?php endif; ?>
                                        </label>
                                        <input type="text" name="details[<?php echo e($fName); ?>]"
                                               value="<?php echo e(old('details.' . $fName)); ?>"
                                               <?php echo e($fReq ? 'required' : ''); ?>

                                               autocomplete="off" spellcheck="false"
                                               style="width:100%; background:var(--surface-2); border:1px solid <?php echo e($errors->has('details.' . $fName) ? '#EF4444' : 'var(--border)'); ?>; border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none; font-family:ui-monospace,monospace;"
                                               placeholder="<?php echo e($field['placeholder'] ?? ''); ?>">
                                        <?php $__errorArgs = ['details.' . $fName];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div style="font-size:11.5px; color:#EF4444; margin-top:4px;"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    
                                    <?php $field = null; ?>
                                    <div>
                                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Account number / address <span style="color:#EF4444;">*</span></label>
                                        <input type="text" name="details[account]" value="<?php echo e(old('details.account')); ?>" required
                                               style="width:100%; background:var(--surface-2); border:1px solid <?php echo e($errors->has('details.account') ? '#EF4444' : 'var(--border)'); ?>; border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none;"
                                               placeholder="Account number, phone, or wallet address">
                                        <?php $__errorArgs = ['details.account'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div style="font-size:11.5px; color:#EF4444; margin-top:4px;"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div>
                                        <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Account name</label>
                                        <input type="text" name="details[name]" value="<?php echo e(old('details.name', auth()->user()->fullname)); ?>"
                                               style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none;"
                                               placeholder="Name on the account">
                                    </div>
                                <?php endif; ?>

                                <?php if(str_contains(strtolower($m->name), 'crypto') || str_contains(strtolower($m->name), 'usdt')): ?>
                                <div style="font-size:11px; color:#b45309; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:8px; padding:9px 11px; line-height:1.5;">
                                    Check the address character by character. Crypto transfers cannot be reversed or recovered.
                                </div>
                                <?php endif; ?>
                            </div>
                        </template>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <template x-if="!selectedMethodId">
                        <div style="font-size:11.5px; color:var(--fg-4); padding:2px 0;">
                            Choose a withdrawal method to see the details it needs.
                        </div>
                    </template>

                    <button type="submit" class="btn btn-primary" style="padding:10px; justify-content:center; font-size:13px; width:100%; display:flex; align-items:center; gap:7px;">
                        <i data-lucide="plus-circle" style="width:14px; height:14px;"></i> Save account
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card" style="padding:18px; margin-top:12px;">
            <div style="font-size:12.5px; font-weight:600; color:var(--fg); margin-bottom:10px;">How saved accounts work</div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php $__currentLoopData = [
                    ['icon'=>'zap','text'=>'Saved accounts appear as quick-select options when you withdraw'],
                    ['icon'=>'shield','text'=>'Your details are stored securely and only used for payouts'],
                    ['icon'=>'star','text'=>'Set one account as default for fastest withdrawals'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <i data-lucide="<?php echo e($tip['icon']); ?>" style="width:13px; height:13px; color:var(--fg-3); margin-top:1.5px; flex-shrink:0;"></i>
                    <span style="font-size:12px; color:var(--fg-3); line-height:1.55;"><?php echo e($tip['text']); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 860px) { .pa-grid { grid-template-columns: 1fr !important; } }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/wallet/payout-accounts.blade.php ENDPATH**/ ?>