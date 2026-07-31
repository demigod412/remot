<?php $__env->startSection('title', $user->fullname . ' — User Detail'); ?>
<?php $__env->startSection('page-title', 'User Detail'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-3);margin-bottom:20px;">
    <a href="<?php echo e(route('admin.users.index')); ?>" style="color:var(--fg-3);text-decoration:none;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Users</a>
    <i data-lucide="chevron-right" style="width:12px;height:12px;"></i>
    <span style="color:var(--fg-2);"><?php echo e($user->fullname); ?></span>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start;">

    
    <div style="display:flex;flex-direction:column;gap:16px;">

        
        <div class="jobstation-card" style="padding:20px;">
            
            <?php
                $ini  = strtoupper(substr($user->firstname ?? $user->username ?? 'U', 0, 1));
                $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
                $clr  = $clrs[ord($ini) % count($clrs)];
            ?>
            <div style="text-align:center;margin-bottom:16px;">
                <div style="width:72px;height:72px;border-radius:50%;overflow:hidden;margin:0 auto 12px;border:2px solid rgba(255,255,255,0.1);">
                    <?php if($user->image): ?>
                        <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.avatars'), $user->image)); ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                    <?php else: ?>
                        <div style="width:100%;height:100%;background:<?php echo e($clr); ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;font-weight:600;"><?php echo e($ini); ?></div>
                    <?php endif; ?>
                </div>
                <h2 style="font-size:16px;font-weight:600;margin:0 0 3px;"><?php echo e($user->fullname); ?></h2>
                <div style="font-size:12px;color:var(--fg-3);"><?php echo e('@' . $user->username); ?></div>
                <div style="display:flex;justify-content:center;gap:6px;margin-top:8px;">
                    <span class="status-pill <?php echo e($user->status == 1 ? 'status-approved' : 'status-rejected'); ?>" style="font-size:10.5px;">
                        <?php echo e($user->status == 1 ? 'Active' : 'Suspended'); ?>

                    </span>
                    <?php if($user->kyc_status == 1): ?>
                    <span class="chip" style="font-size:10px;background:rgba(34,197,94,0.12);color:#22C55E;border-color:rgba(34,197,94,0.25);">KYC Verified</span>
                    <?php elseif($user->kyc_status == 2): ?>
                    <span class="chip" style="font-size:10px;background:rgba(245,158,11,0.12);color:#F59E0B;border-color:rgba(245,158,11,0.25);">KYC Pending</span>
                    <?php endif; ?>
                </div>
            </div>

            
            <div style="border-top:1px solid var(--border);padding-top:14px;display:flex;flex-direction:column;gap:9px;">
                <?php $__currentLoopData = [
                    ['Email',    $user->email, false],
                    ['Mobile',   $user->mobile ?? '—', false],
                    ['Country',  $user->country_code ?? '—', false],
                    ['Verified', $user->email_verified ? 'Yes' : 'No', $user->email_verified],
                    ['Joined',   $user->created_at->format('M d, Y'), false],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$k,$v,$good]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;">
                    <span style="color:var(--fg-3);"><?php echo e($k); ?></span>
                    <span style="color:<?php echo e($good ? '#22C55E' : 'var(--fg-2)'); ?>;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;"><?php echo e($v); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if($user->referred_by): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;">
                    <span style="color:var(--fg-3);">Referred by</span>
                    <a href="<?php echo e(route('admin.users.show', $user->referred_by)); ?>" style="color:var(--accent);text-decoration:none;font-size:11.5px;">#<?php echo e($user->referred_by); ?></a>
                </div>
                <?php endif; ?>
                <?php if($user->status == 0 && $user->ban_reason): ?>
                <div style="margin-top:4px;padding:10px 12px;border-radius:8px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);">
                    <div style="font-size:11px;color:#EF4444;font-weight:500;margin-bottom:2px;">Ban reason</div>
                    <div style="font-size:12px;color:var(--fg-2);"><?php echo e($user->ban_reason); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="jobstation-card" style="padding:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <h3 style="font-size:13px;font-weight:600;margin:0;">Coin balance</h3>
                <i data-lucide="coins" style="width:15px;height:15px;color:var(--coin);"></i>
            </div>
            <div class="mono" style="font-size:28px;font-weight:600;color:var(--coin);letter-spacing:-0.8px;"><?php echo e(coinSymbol()); ?><?php echo e(number_format($user->coin_balance)); ?></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;">
                <div style="padding:10px;border-radius:8px;background:rgba(34,197,94,0.08);text-align:center;">
                    <div class="mono" style="font-size:13px;font-weight:600;color:#22C55E;"><?php echo e(coinSymbol()); ?><?php echo e(number_format($stats['total_earned'])); ?></div>
                    <div style="font-size:10.5px;color:var(--fg-3);margin-top:2px;">Earned</div>
                </div>
                <div style="padding:10px;border-radius:8px;background:rgba(239,68,68,0.08);text-align:center;">
                    <div class="mono" style="font-size:13px;font-weight:600;color:#EF4444;"><?php echo e(coinSymbol()); ?><?php echo e(number_format($stats['total_spent'])); ?></div>
                    <div style="font-size:10.5px;color:var(--fg-3);margin-top:2px;">Spent</div>
                </div>
            </div>

            
            <div x-data="{ open: false }" style="margin-top:14px;">
                <button @click="open = !open" class="btn" style="width:100%;justify-content:center;">
                    <i data-lucide="sliders-horizontal" style="width:13px;height:13px;"></i> Adjust balance
                </button>
                <div x-show="open" x-cloak x-transition style="margin-top:10px;padding:14px;border-radius:10px;background:var(--surface-2);border:1px solid var(--border);">
                    <form method="POST" action="<?php echo e(route('admin.users.balance', $user->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <div style="margin-bottom:8px;">
                            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Type</label>
                            <select name="type" style="width:100%;font-size:12.5px;">
                                <option value="add">Add coins</option>
                                <option value="deduct">Deduct coins</option>
                            </select>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Amount</label>
                            <input type="number" name="amount" min="1" step="1" placeholder="0" style="width:100%;font-size:12.5px;" required>
                        </div>
                        <div style="margin-bottom:10px;">
                            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Reason</label>
                            <input type="text" name="reason" placeholder="Reason for adjustment" style="width:100%;font-size:12.5px;" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Apply</button>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="jobstation-card" style="padding:16px;">
            <h3 style="font-size:13px;font-weight:600;margin:0 0 10px;">Quick actions</h3>

            <button x-data @click="$dispatch('open-edit-modal')"
                    style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--fg-2);background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;margin-bottom:2px;"
                    onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'" onmouseout="this.style.background='transparent';this.style.color='var(--fg-2)'">
                <i data-lucide="edit-3" style="width:14px;height:14px;"></i> Edit user info
            </button>

            <a href="<?php echo e(route('admin.users.notify', $user->id)); ?>"
               style="display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--fg-2);text-decoration:none;margin-bottom:2px;"
               onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'" onmouseout="this.style.background='transparent';this.style.color='var(--fg-2)'">
                <i data-lucide="send" style="width:14px;height:14px;"></i> Send notification
            </a>

            <a href="<?php echo e(route('admin.users.login-as', $user->id)); ?>"
               onclick="return confirm('Login as <?php echo e($user->username); ?>?')"
               style="display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--fg-2);text-decoration:none;margin-bottom:6px;"
               onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'" onmouseout="this.style.background='transparent';this.style.color='var(--fg-2)'">
                <i data-lucide="log-in" style="width:14px;height:14px;"></i> Login as user
            </a>

            <div style="height:1px;background:var(--border);margin:4px 0;"></div>

            <?php if($user->status == 1): ?>
            <div x-data="{ open: false }">
                <button @click="open = !open"
                        style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 10px;border-radius:8px;font-size:13px;color:#EF4444;background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;margin-top:4px;"
                        onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
                    <i data-lucide="ban" style="width:14px;height:14px;"></i> Ban user
                </button>
                <div x-show="open" x-cloak x-transition style="margin-top:8px;padding:12px;border-radius:10px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);">
                    <form method="POST" action="<?php echo e(route('admin.users.status', $user->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="text" name="ban_reason" placeholder="Ban reason (optional)" style="width:100%;font-size:12.5px;margin-bottom:8px;">
                        <button type="submit" style="width:100%;padding:8px;border-radius:8px;background:#EF4444;color:white;border:none;font-size:13px;font-weight:500;cursor:pointer;">
                            Confirm ban
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <form method="POST" action="<?php echo e(route('admin.users.status', $user->id)); ?>" style="margin-top:4px;">
                <?php echo csrf_field(); ?>
                <button type="submit" onclick="return confirm('Unban <?php echo e($user->username); ?>?')"
                        style="display:flex;align-items:center;gap:8px;width:100%;padding:9px 10px;border-radius:8px;font-size:13px;color:#22C55E;background:transparent;border:none;cursor:pointer;font-family:inherit;text-align:left;"
                        onmouseover="this.style.background='rgba(34,197,94,0.08)'" onmouseout="this.style.background='transparent'">
                    <i data-lucide="check-circle" style="width:14px;height:14px;"></i> Unban user
                </button>
            </form>
            <?php endif; ?>
        </div>

        
        <?php if($user->kyc_status == 2 && $user->kyc_data): ?>
        <div class="jobstation-card" style="padding:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h3 style="font-size:13px;font-weight:600;margin:0;">KYC submission</h3>
                <span class="chip" style="font-size:10px;background:rgba(245,158,11,0.12);color:#F59E0B;border-color:rgba(245,158,11,0.25);">Pending review</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;">
                <?php $__currentLoopData = $user->kyc_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($key !== 'rejection_reason'): ?>
                    <div>
                        <div style="font-size:11px;color:var(--fg-3);margin-bottom:4px;"><?php echo e(ucwords(str_replace('_', ' ', $key))); ?></div>
                        <?php if(in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg','jpeg','png','gif','webp'])): ?>
                            <a href="<?php echo e(route('secure.kyc', ['user' => $user->id, 'side' => $key === 'back_image' ? 'back' : 'front'])); ?>" target="_blank">
                                <img src="<?php echo e(route('secure.kyc', ['user' => $user->id, 'side' => $key === 'back_image' ? 'back' : 'front'])); ?>"
                                     style="height:100px;border-radius:8px;object-fit:cover;border:1px solid var(--border);display:block;" alt="<?php echo e($key); ?>">
                            </a>
                        <?php else: ?>
                            <div style="font-size:13px;color:var(--fg-2);"><?php echo e($value); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <form method="POST" action="<?php echo e(route('admin.users.kyc.approve', $user->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" onclick="return confirm('Approve KYC for <?php echo e($user->username); ?>?')"
                            style="width:100%;padding:8px;border-radius:8px;background:#22C55E;color:white;border:none;font-size:13px;font-weight:500;cursor:pointer;">
                        <i data-lucide="check" style="width:13px;height:13px;display:inline;"></i> Approve
                    </button>
                </form>
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                            style="width:100%;padding:8px;border-radius:8px;background:rgba(239,68,68,0.9);color:white;border:none;font-size:13px;font-weight:500;cursor:pointer;">
                        <i data-lucide="x" style="width:13px;height:13px;display:inline;"></i> Reject
                    </button>
                    <div x-show="open" x-cloak x-transition style="margin-top:8px;">
                        <form method="POST" action="<?php echo e(route('admin.users.kyc.reject', $user->id)); ?>">
                            <?php echo csrf_field(); ?>
                            <textarea name="rejection_reason" rows="2" placeholder="Rejection reason…"
                                      style="width:100%;font-size:12.5px;margin-bottom:6px;resize:none;" required></textarea>
                            <button type="submit" style="width:100%;padding:7px;border-radius:8px;background:#EF4444;color:white;border:none;font-size:12.5px;font-weight:500;cursor:pointer;">Confirm reject</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    
    <div style="display:flex;flex-direction:column;gap:16px;">

        
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
            <?php $__currentLoopData = [
                ['Submissions',  $stats['total_submissions'],    'var(--fg)'],
                ['Approved',     $stats['approved_submissions'],  '#22C55E'],
                ['Works posted', $stats['works_posted'],         'var(--accent)'],
                ['Referrals',    $stats['referrals'],            '#60A5FA'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$lbl, $val, $clr]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="jobstation-card" style="padding:16px;text-align:center;">
                <div class="mono" style="font-size:22px;font-weight:600;color:<?php echo e($clr); ?>;"><?php echo e(number_format($val)); ?></div>
                <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;"><?php echo e($lbl); ?></div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="jobstation-card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:600;margin:0;">Recent ledger</h3>
                <span style="font-size:11.5px;color:var(--fg-3);">Last 10</span>
            </div>
            <?php $__empty_1 = true; $__currentLoopData = $recentLedger; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div style="display:flex;align-items:center;gap:12px;padding:13px 20px;border-bottom:<?php echo e($idx < count($recentLedger)-1 ? '1px solid var(--border)' : 'none'); ?>;">
                <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?php echo e($entry->entry_type === '+' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)'); ?>;">
                    <i data-lucide="<?php echo e($entry->entry_type === '+' ? 'arrow-down-left' : 'arrow-up-right'); ?>"
                       style="width:13px;height:13px;color:<?php echo e($entry->entry_type === '+' ? '#22C55E' : '#EF4444'); ?>;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12.5px;color:var(--fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($entry->description); ?></div>
                    <div style="font-size:10.5px;color:var(--fg-3);"><?php echo e($entry->created_at->format('M d, Y H:i')); ?></div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div class="mono" style="font-size:12.5px;font-weight:600;color:<?php echo e($entry->entry_type === '+' ? '#22C55E' : '#EF4444'); ?>;">
                        <?php echo e($entry->entry_type); ?><?php echo e(coinSymbol()); ?><?php echo e(number_format($entry->coins)); ?>

                    </div>
                    <div class="mono" style="font-size:10px;color:var(--fg-3);">bal <?php echo e(coinSymbol()); ?><?php echo e(number_format($entry->balance_after)); ?></div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div style="padding:40px;text-align:center;color:var(--fg-3);font-size:13px;">No ledger entries yet.</div>
            <?php endif; ?>
        </div>

        
        <div class="jobstation-card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:14px;font-weight:600;margin:0;">Recent submissions</h3>
                <a href="<?php echo e(route('admin.submissions.index', ['search' => $user->username])); ?>"
                   style="font-size:12px;color:var(--accent);text-decoration:none;">View all →</a>
            </div>
            <?php $__empty_1 = true; $__currentLoopData = $recentSubmissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $sMap = [0=>'status-pending',1=>'status-pending',2=>'status-approved',3=>'status-rejected'];
                $sLbl = [0=>'Applied',1=>'In Review',2=>'Approved',3=>'Rejected'];
            ?>
            <div style="display:flex;align-items:center;gap:12px;padding:13px 20px;border-bottom:<?php echo e($idx < count($recentSubmissions)-1 ? '1px solid var(--border)' : 'none'); ?>;">
                <div style="padding:8px;border-radius:8px;background:var(--surface-2);flex-shrink:0;">
                    <i data-lucide="file-text" style="width:14px;height:14px;color:var(--fg-3);display:block;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12.5px;color:var(--fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($sub->work?->title ?? 'Deleted Work'); ?></div>
                    <div style="font-size:10.5px;color:var(--fg-3);"><?php echo e($sub->created_at->format('M d, Y')); ?></div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <span class="status-pill <?php echo e($sMap[$sub->status] ?? 'status-draft'); ?>" style="font-size:10.5px;"><?php echo e($sLbl[$sub->status] ?? '—'); ?></span>
                    <?php if($sub->status == 2): ?>
                    <div class="mono" style="font-size:10.5px;color:#22C55E;margin-top:2px;">+<?php echo e(coinSymbol()); ?><?php echo e(number_format($sub->work?->coins_per_worker ?? 0)); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div style="padding:40px;text-align:center;color:var(--fg-3);font-size:13px;">No submissions yet.</div>
            <?php endif; ?>
        </div>

    </div>
</div>


<div x-data="{ open: false }" @open-edit-modal.window="open = true">
    <div x-show="open" x-cloak x-transition
         style="position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div @click.outside="open = false" class="jobstation-card" style="width:100%;max-width:440px;padding:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h3 style="font-size:15px;font-weight:600;margin:0;">Edit user info</h3>
                <button @click="open = false"
                        style="padding:5px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);"
                        onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='transparent'">
                    <i data-lucide="x" style="width:16px;height:16px;display:block;"></i>
                </button>
            </div>
            <form method="POST" action="<?php echo e(route('admin.users.update', $user->id)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                    <div>
                        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">First Name</label>
                        <input type="text" name="firstname" value="<?php echo e(old('firstname', $user->firstname)); ?>" style="width:100%;" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Last Name</label>
                        <input type="text" name="lastname" value="<?php echo e(old('lastname', $user->lastname)); ?>" style="width:100%;" required>
                    </div>
                </div>
                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Username</label>
                    <input type="text" name="username" value="<?php echo e(old('username', $user->username)); ?>" style="width:100%;" required>
                </div>
                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Email</label>
                    <input type="email" name="email" value="<?php echo e(old('email', $user->email)); ?>" style="width:100%;" required>
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:4px;">Mobile</label>
                    <input type="text" name="mobile" value="<?php echo e(old('mobile', $user->mobile)); ?>" style="width:100%;">
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">Save changes</button>
                    <button type="button" @click="open = false" class="btn" style="padding:8px 16px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if($errors->any()): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.dispatchEvent(new CustomEvent('open-edit-modal'));
    });
</script>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/users/show.blade.php ENDPATH**/ ?>