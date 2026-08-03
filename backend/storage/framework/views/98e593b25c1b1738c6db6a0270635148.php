<?php $__env->startSection('title', 'Wallet'); ?>
<?php $__env->startSection('page-title', 'Wallet'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px;" class="wallet-balance-pair">
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Spending balance</div>
        <div class="mono" style="font-size:26px; font-weight:600; letter-spacing:-0.8px;"><?php echo e(formatCoins(auth('web')->user()->coin_balance, 2)); ?></div>
        <div style="font-size:11.5px; color:var(--fg-3); margin-top:5px;">JC coins. Used for task application fees. Not withdrawable.</div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Earnings balance</div>
        <div class="mono" style="font-size:26px; font-weight:600; letter-spacing:-0.8px;">$<?php echo e(number_format(auth('web')->user()->usd_balance, 2)); ?></div>
        <div style="font-size:11.5px; color:var(--fg-3); margin-top:5px;">USD from approved work. This is what you withdraw.</div>
    </div>
</div>
<style>@media (max-width:640px){ .wallet-balance-pair{ grid-template-columns:1fr !important; } }</style>


<div style="display:grid; grid-template-columns:1fr 340px; gap:20px;" class="wallet-grid">

    
    <div>

        
        <?php
            $walletCurrencies = gs()->currencies ?? [];
            $walletDefaultCode = gs()->default_currency ?? ($walletCurrencies[0]['code'] ?? null);
        ?>
        <div class="card" style="padding:28px; background:linear-gradient(135deg,#1a1533,var(--surface)); border-color:rgba(47,84,235,0.25); margin-bottom:16px; position:relative; overflow:hidden;"
             x-data="{
                currencies: <?php echo e(json_encode($walletCurrencies)); ?>,
                sel: '<?php echo e($walletDefaultCode); ?>',
                balance: <?php echo e((float) $user->coin_balance); ?>,
                get cur() { return this.currencies.find(c => c.code === this.sel) || null },
                get fiat() { return this.cur ? (this.balance * this.cur.rate).toLocaleString('en-US', {minimumFractionDigits:0,maximumFractionDigits:0}) : null }
             }">
            <div style="position:absolute; right:-40px; top:-40px; width:200px; height:200px; border-radius:50%; background:radial-gradient(circle,rgba(47,84,235,0.25),transparent 70%); pointer-events:none;"></div>
            <div style="position:relative;">
                <div style="font-size:11.5px; color:var(--fg-3); margin-bottom:10px; text-transform:uppercase; letter-spacing:0.08em;">Available balance</div>
                <div style="display:flex; align-items:baseline; gap:8px; margin-bottom:8px;">
                    <span class="mono" style="font-size:clamp(36px,5vw,64px); font-weight:600; letter-spacing:-2.5px; line-height:1;"><?php echo e(formatCoins($user->coin_balance)); ?></span>
                </div>
                
                <template x-if="cur && currencies.length > 0">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                        <span style="font-size:14px; color:var(--fg-2);">≈ <span x-text="(cur ? cur.symbol + ' ' : '') + fiat" style="font-weight:600; color:var(--fg);"></span></span>
                        <select x-model="sel" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:3px 8px; color:var(--fg); font-size:12px; font-family:ui-monospace,monospace; outline:none; cursor:pointer;">
                            <template x-for="c in currencies" :key="c.code">
                                <option :value="c.code" x-text="c.code"></option>
                            </template>
                        </select>
                    </div>
                </template>
                <div style="font-size:13px; color:var(--fg-2); margin-bottom:24px;">Lifetime earned: <span class="mono" style="color:var(--fg);"><?php echo e(formatUsd($stats['total_earned'])); ?></span></div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="<?php echo e(route('user.wallet.cashout')); ?>" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="download" style="width:14px; height:14px;"></i> Withdraw
                    </a>
                    <a href="<?php echo e(route('user.wallet.topup')); ?>" class="btn" style="display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="upload" style="width:14px; height:14px;"></i> Top up
                    </a>
                    <a href="<?php echo e(route('user.wallet.ledger')); ?>" class="btn" style="display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="list" style="width:14px; height:14px;"></i> Full history
                    </a>
                </div>
            </div>
        </div>

        
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px;" class="wallet-stats">
            <div class="card" style="padding:16px;">
                <div class="label" style="margin-bottom:8px;">Total topped up</div>
                <div style="display:flex; align-items:baseline; gap:3px;">
                    <span class="mono" style="font-size:20px; font-weight:600; color:var(--fg);"><?php echo e(formatCoins($stats['total_topup'])); ?></span>
                </div>
            </div>
            <div class="card" style="padding:16px;">
                <div class="label" style="margin-bottom:8px;">Total withdrawn</div>
                <div style="display:flex; align-items:baseline; gap:3px;">
                    <span class="mono" style="font-size:20px; font-weight:600; color:var(--fg);"><?php echo e(formatUsd($stats['total_cashout'])); ?></span>
                </div>
            </div>
            <div class="card" style="padding:16px;">
                <div class="label" style="margin-bottom:8px;">Total earned</div>
                <div style="display:flex; align-items:baseline; gap:3px;">
                    <span class="mono" style="font-size:20px; font-weight:600; color:#22C55E;"><?php echo e(formatUsd($stats['total_earned'])); ?></span>
                </div>
            </div>
        </div>

        
        <div class="card" style="padding:0; overflow:hidden;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border);">
                <div style="font-size:13px; font-weight:600;">Recent transactions</div>
                <a href="<?php echo e(route('user.wallet.ledger')); ?>" style="font-size:12px; color:var(--accent); text-decoration:none;">View all →</a>
            </div>

            
            <?php $__currentLoopData = $recentTopups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex; align-items:center; gap:14px; padding:12px 18px; border-bottom:1px solid var(--border);">
                <div style="width:32px; height:32px; border-radius:8px; background:rgba(34,197,94,0.12); color:#22C55E; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i data-lucide="arrow-down" style="width:14px; height:14px;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:500; color:var(--fg);">Coin top-up</div>
                    <div style="font-size:11px; color:var(--fg-3);"><?php echo e($tx->created_at->format('M d, Y')); ?></div>
                </div>
                <span class="mono" style="font-size:14px; font-weight:500; color:#22C55E; flex-shrink:0;">+<?php echo e(formatCoins($tx->coins_credited)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            
            <?php $__currentLoopData = $recentCashouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex; align-items:center; gap:14px; padding:12px 18px; border-bottom:1px solid var(--border);">
                <div style="width:32px; height:32px; border-radius:8px; background:rgba(239,68,68,0.10); color:#EF4444; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i data-lucide="arrow-up" style="width:14px; height:14px;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:500; color:var(--fg);">Withdrawal
                        <?php if($tx->payoutMethod): ?>— <?php echo e($tx->payoutMethod->name ?? ''); ?><?php endif; ?>
                    </div>
                    <div style="font-size:11px; color:var(--fg-3);"><?php echo e($tx->created_at->format('M d, Y')); ?>

                        <?php if($tx->status == 0): ?> · <span style="color:#F59E0B;">Pending</span>
                        <?php elseif($tx->status == 1): ?> · <span style="color:#22C55E;">Approved</span>
                        <?php elseif($tx->status == 2): ?> · <span style="color:#EF4444;">Rejected</span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="mono" style="font-size:14px; font-weight:500; color:var(--fg); flex-shrink:0;">−<?php echo e(formatUsd($tx->net_coins_deducted)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($recentTopups->isEmpty() && $recentCashouts->isEmpty()): ?>
            <div style="padding:40px 24px; text-align:center; color:var(--fg-3);">
                <div style="font-size:28px; margin-bottom:10px;">💰</div>
                <div style="font-size:13.5px; font-weight:500; color:var(--fg); margin-bottom:6px;">No transactions yet</div>
                <a href="<?php echo e(route('user.wallet.topup')); ?>" style="font-size:12.5px; color:var(--accent); text-decoration:none;">Top up your balance →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div>

        
        <div class="card" style="padding:20px; margin-bottom:14px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Quick actions</h4>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <a href="<?php echo e(route('user.wallet.cashout')); ?>" class="btn btn-primary" style="justify-content:center; font-size:13px; padding:10px;">
                    <i data-lucide="download" style="width:14px; height:14px;"></i> Withdraw funds
                </a>
                <a href="<?php echo e(route('user.wallet.topup')); ?>" class="btn" style="justify-content:center; font-size:13px; padding:10px;">
                    <i data-lucide="upload" style="width:14px; height:14px;"></i> Top up balance
                </a>
                <a href="<?php echo e(route('user.wallet.cashout.history')); ?>" class="btn" style="justify-content:center; font-size:13px; padding:10px;">
                    <i data-lucide="history" style="width:14px; height:14px;"></i> Withdrawal history
                </a>
            </div>
        </div>

        
        <?php $kycStatus = $user->kyc_status ?? 0; ?>
        <?php if($kycStatus == 0): ?>
        <div class="card" style="padding:16px; margin-bottom:14px; border-color:rgba(245,158,11,0.3); background:rgba(245,158,11,0.05);">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <i data-lucide="shield-alert" style="width:16px; height:16px; color:#F59E0B; flex-shrink:0;"></i>
                <div style="font-size:13px; font-weight:600; color:#F59E0B;">KYC verification required</div>
            </div>
            <div style="font-size:12px; color:var(--fg-2); margin-bottom:12px; line-height:1.5;">Complete identity verification to withdraw your earnings.</div>
            <a href="<?php echo e(route('user.profile.kyc')); ?>" class="btn" style="font-size:12px; padding:7px 14px; border-color:rgba(245,158,11,0.4); color:#F59E0B; width:100%; justify-content:center;">
                Verify now →
            </a>
        </div>
        <?php elseif($kycStatus == 1): ?>
        <div class="card" style="padding:14px; margin-bottom:14px; border-color:rgba(34,197,94,0.25); background:rgba(34,197,94,0.05);">
            <div style="display:flex; align-items:center; gap:8px;">
                <i data-lucide="shield-check" style="width:15px; height:15px; color:#22C55E; flex-shrink:0;"></i>
                <div style="font-size:13px; font-weight:500; color:#22C55E;">KYC verified — withdrawals enabled</div>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="card" style="padding:20px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Balance breakdown</h4>
            <?php
                $earnedFromWork = $stats['total_earned'];
                $total = max($stats['total_topup'] + $earnedFromWork, 1);
            ?>
            <?php $__currentLoopData = [
                ['label' => 'From tasks', 'value' => $earnedFromWork, 'color' => 'var(--urgent)', 'pct' => round($earnedFromWork / $total * 100)],
                ['label' => 'Topped up', 'value' => $stats['total_topup'], 'color' => '#60A5FA', 'pct' => round($stats['total_topup'] / $total * 100)],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:5px; color:var(--fg-2);">
                    <span><?php echo e($r['label']); ?></span>
                    <span class="mono" style="color:var(--fg);"><?php echo e(coinSymbol()); ?> <?php echo e(number_format($r['value'])); ?></span>
                </div>
                <div style="height:5px; background:var(--surface-3); border-radius:3px; overflow:hidden;">
                    <div style="width:<?php echo e($r['pct']); ?>%; height:100%; background:<?php echo e($r['color']); ?>; border-radius:3px;"></div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

    </div>
</div>

<style>
@media (max-width: 900px) {
    .wallet-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 640px) {
    .wallet-stats { grid-template-columns: 1fr !important; }
}
</style>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/wallet/overview.blade.php ENDPATH**/ ?>