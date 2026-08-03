<?php $__env->startSection('title', 'Confirm Withdrawal'); ?>
<?php $__env->startSection('page-title', 'Confirm Withdrawal'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:grid; grid-template-columns:1fr 340px; gap:20px;" class="co-preview-grid"
     x-data="{ useSaved: <?php echo e($savedAccounts->isNotEmpty() ? 'true' : 'false'); ?>, selectedAccountId: '<?php echo e($savedAccounts->where('is_default',true)->first()?->id ?? $savedAccounts->first()?->id ?? ''); ?>' }">

    
    <div>
        <div style="margin-bottom:16px;">
            <a href="<?php echo e(route('user.wallet.cashout')); ?>"
               style="font-size:12.5px; color:var(--accent); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-left" style="width:13px; height:13px;"></i> Back
            </a>
        </div>

        
        <?php if($savedAccounts->isNotEmpty()): ?>
        <div class="card" style="padding:20px; margin-bottom:14px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:14px;">Payout account</div>

            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:14px;">
                <?php $__currentLoopData = $savedAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $saved): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="saved-acc-opt"
                       style="display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:10px; border:1px solid var(--border); cursor:pointer; transition:border-color .15s;">
                    <input type="radio" name="_saved_account_radio"
                           value="<?php echo e($saved->id); ?>"
                           x-model="selectedAccountId"
                           @change="useSaved = true"
                           <?php echo e(($saved->is_default || ($savedAccounts->count() === 1)) ? 'checked' : ''); ?>

                           style="accent-color:var(--accent); width:15px; height:15px; flex-shrink:0;">
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:13px; font-weight:500; color:var(--fg); display:flex; align-items:center; gap:7px;">
                            <?php echo e($saved->label ?: $saved->payoutMethod->name); ?>

                            <?php if($saved->is_default): ?>
                            <span style="font-size:9.5px; padding:1px 6px; border-radius:999px; background:rgba(47,84,235,0.12); color:var(--accent); border:1px solid rgba(47,84,235,0.2); font-weight:600;">DEFAULT</span>
                            <?php endif; ?>
                        </div>
                        <div class="mono" style="font-size:11.5px; color:var(--fg-3); margin-top:2px;"><?php echo e($saved->primary_detail); ?></div>
                    </div>
                </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <label class="saved-acc-opt"
                       style="display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:10px; border:1px dashed var(--border); cursor:pointer; transition:border-color .15s;">
                    <input type="radio" name="_saved_account_radio" value=""
                           @change="useSaved = false; selectedAccountId = ''"
                           style="accent-color:var(--accent); width:15px; height:15px; flex-shrink:0;">
                    <div style="font-size:13px; color:var(--fg-2);">Enter different account details</div>
                </label>
            </div>

            
            <div x-show="useSaved && selectedAccountId">
                <?php $__currentLoopData = $savedAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $saved): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <template x-if="selectedAccountId == '<?php echo e($saved->id); ?>'">
                    <div>
                        <?php $__currentLoopData = $saved->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <input type="hidden" name="payout_details[<?php echo e($key); ?>]" value="<?php echo e($val); ?>">
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </template>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="card" style="padding:20px; margin-bottom:14px;"
             x-show="!useSaved || !selectedAccountId">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:14px;">Enter payout details</div>

            <?php if($method->description): ?>
            <div style="font-size:12.5px; color:var(--fg-3); margin-bottom:14px; line-height:1.6; padding:10px 12px; background:var(--surface-2); border-radius:8px;">
                <?php echo richBody($method->description); ?>

            </div>
            <?php endif; ?>

            <?php if($method->form && count($method->form->form_data ?? []) > 0): ?>
                <?php $__currentLoopData = $method->form->form_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="margin-bottom:12px;">
                    <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">
                        <?php echo e($field['label'] ?? $field['name']); ?>

                        <?php echo e(($field['required'] ?? false) ? '*' : ''); ?>

                    </label>
                    <input type="text" name="payout_details[<?php echo e($field['name']); ?>]"
                           style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none;"
                           placeholder="<?php echo e($field['placeholder'] ?? ''); ?>"
                           <?php echo e(($field['required'] ?? false) ? 'required' : ''); ?>>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <div style="margin-bottom:12px;">
                    <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Account number / address <span style="color:#EF4444;">*</span></label>
                    <input type="text" name="payout_details[account]" required
                           style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13.5px; outline:none; font-family:ui-monospace,monospace;"
                           placeholder="Your <?php echo e($method->name); ?> account">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:7px;">Account name</label>
                    <input type="text" name="payout_details[name]" value="<?php echo e(auth()->user()->fullname); ?>"
                           style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--fg); font-size:13px; outline:none;">
                </div>
            <?php endif; ?>

            
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-top:4px;" x-data>
                <input type="checkbox" name="save_account" value="1"
                       style="accent-color:var(--accent); width:14px; height:14px;">
                <span style="font-size:12px; color:var(--fg-3);">Save this account for future withdrawals</span>
            </label>
            <div style="margin-top:8px;" x-data="{ checked: false }">
                <input type="text" name="save_label"
                       style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:var(--fg); font-size:12.5px; outline:none;"
                       placeholder="Account nickname (optional)">
            </div>
        </div>

        
        <form method="POST" action="<?php echo e(route('user.wallet.cashout.submit')); ?>" id="cashout-form">
            <?php echo csrf_field(); ?>
            
        </form>

        <?php $confirmMsg = 'Confirm withdrawal of ' . formatCoins($preview['net_coins_deducted']) . '?'; ?>
        <button type="submit" form="cashout-form" class="btn btn-primary"
                style="padding:11px 28px; font-size:13.5px; display:inline-flex; align-items:center; gap:7px;"
                onclick="return confirm('<?php echo e($confirmMsg); ?>')">
            <i data-lucide="send" style="width:14px; height:14px;"></i> Confirm withdrawal
        </button>
        <a href="<?php echo e(route('user.wallet.cashout')); ?>" class="btn"
           style="padding:11px 18px; font-size:13.5px; margin-left:8px;">Cancel</a>
    </div>

    
    <div>
        <div class="card" style="padding:22px; margin-bottom:14px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:16px;">Withdrawal summary</div>

            <?php $rows = [
                ['label' => 'Method',              'value' => $method->name,                                 'mono' => false, 'color' => 'var(--fg)'],
                ['label' => 'Coins requested',     'value' => formatCoins($preview['coin_amount']), 'mono' => true,  'color' => 'var(--fg)'],
                ['label' => 'Fee (' . $method->percent_fee . '% + ' . number_format($method->fixed_fee, 0) . ' fixed)',
                                                   'value' => '−' . formatCoins($preview['fee'], 2),     'mono' => true,  'color' => '#EF4444'],
                ['label' => 'Total deducted',      'value' => formatCoins($preview['net_coins_deducted']), 'mono' => true, 'color' => 'var(--fg)'],
                ['label' => 'Exchange rate',       'value' => '1 ' . coinSymbol() . ' = ' . $method->coin_to_currency_rate . ' ' . $method->currency, 'mono' => true, 'color' => 'var(--fg-3)'],
            ]; ?>

            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--fg-3);"><?php echo e($row['label']); ?></span>
                <span class="<?php echo e($row['mono'] ? 'mono' : ''); ?>" style="font-size:13px; font-weight:500; color:<?php echo e($row['color']); ?>;"><?php echo e($row['value']); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            
            <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0 0;">
                <span style="font-size:13px; font-weight:600; color:var(--fg);">You'll receive</span>
                <span class="mono" style="font-size:20px; font-weight:700; color:#22C55E; letter-spacing:-0.5px;">
                    <?php echo e($method->currency); ?> <?php echo e(number_format($preview['payout_amount'], 2)); ?>

                </span>
            </div>
        </div>

        <div class="card" style="padding:16px; border-color:rgba(245,158,11,0.25); background:rgba(245,158,11,0.04);">
            <div style="display:flex; gap:10px; align-items:flex-start;">
                <i data-lucide="clock" style="width:14px; height:14px; color:#F59E0B; flex-shrink:0; margin-top:1px;"></i>
                <span style="font-size:12px; color:var(--fg-3); line-height:1.55;">Withdrawals are processed within <strong style="color:var(--fg);">24–72 hours</strong> after submission.</span>
            </div>
        </div>

        <div style="margin-top:12px;">
            <a href="<?php echo e(route('user.wallet.payout-accounts')); ?>"
               style="font-size:12px; color:var(--accent); text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
                <i data-lucide="settings" style="width:12px; height:12px;"></i> Manage saved accounts
            </a>
        </div>
    </div>
</div>

<style>
.saved-acc-opt:has(input:checked) { border-color: var(--accent) !important; background: rgba(47,84,235,0.05); }
.saved-acc-opt:hover { border-color: rgba(255,255,255,0.14) !important; }
@media (max-width: 860px) { .co-preview-grid { grid-template-columns: 1fr !important; } }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/wallet/cashout-preview.blade.php ENDPATH**/ ?>