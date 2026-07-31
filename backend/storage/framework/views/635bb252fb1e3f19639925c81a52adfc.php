<?php $__env->startSection('title', 'Profile & Settings'); ?>
<?php $__env->startSection('page-title', 'Profile & KYC'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $user      = auth()->user();
    $uName     = $user->fullname ?? $user->username;
    $uInit     = strtoupper(substr($user->firstname ?? $user->username, 0, 1));
    $colors    = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
    $uColor    = $colors[ord($uInit) % count($colors)];
    $uColor2   = $colors[(ord($uInit) + 2) % count($colors)];
    $approvedCount = $user->workSubmissions()->where('status', 2)->count();
    $totalCount    = $user->workSubmissions()->count();
    $approvalRate  = $totalCount > 0 ? round($approvedCount / $totalCount * 100, 1) : 0;
    use App\Services\RankService;
    $next     = RankService::nextThreshold($rank['score']);
    $prev     = RankService::prevThreshold($rank['score']);
    $progress = $next > $prev ? min(100, round(($rank['score'] - $prev) / ($next - $prev) * 100)) : 100;
    $kyc      = $user->kyc_status ?? 0;
?>

<div style="display:grid; grid-template-columns:1fr 320px; gap:24px;" class="profile-grid">

    
    <div>

        
        <div class="card" style="padding:24px; margin-bottom:16px;">
            <div style="display:flex; align-items:center; gap:18px; margin-bottom:24px; flex-wrap:wrap;">
                <div style="width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg,<?php echo e($uColor); ?>,<?php echo e($uColor2); ?>); display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:28px; flex-shrink:0;">
                    <?php echo e($uInit); ?>

                </div>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px; flex-wrap:wrap;">
                        <h2 style="font-size:22px; font-weight:600; margin:0; letter-spacing:-0.4px; color:var(--fg);"><?php echo e($uName); ?></h2>
                        <?php if($kyc == 1): ?>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:500; background:rgba(34,197,94,0.1); color:#22C55E; border:1px solid rgba(34,197,94,0.25);">
                            <i data-lucide="shield-check" style="width:11px;height:11px;"></i> Verified
                        </span>
                        <?php endif; ?>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:500; background:rgba(47,84,235,0.1); color:var(--accent); border:1px solid rgba(47,84,235,0.25);">
                            <?php echo e($rank['icon']); ?> <?php echo e($rank['label']); ?>

                        </span>
                    </div>
                    <div style="font-size:13px; color:var(--fg-3);"><?php echo e('@' . $user->username); ?><?php if($user->country_code): ?> · <?php echo e($user->country_code); ?><?php endif; ?> · Joined <?php echo e($user->created_at->format('M Y')); ?></div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; padding:20px 0; border-top:1px solid var(--border); border-bottom:1px solid var(--border);" class="profile-stats-row">
                <div>
                    <div class="label" style="margin-bottom:4px;">Approval rate</div>
                    <div class="mono" style="font-size:18px; font-weight:600;"><?php echo e($approvalRate); ?>%</div>
                    <div style="font-size:10.5px; color:var(--fg-3);"><?php echo e($approvedCount); ?> approved</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Tasks done</div>
                    <div class="mono" style="font-size:18px; font-weight:600;"><?php echo e($approvedCount); ?></div>
                    <div style="font-size:10.5px; color:var(--fg-3);">Lifetime</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Balance</div>
                    <div style="display:flex; align-items:baseline; gap:2px;">
                        <span style="font-size:12px; color:var(--coin); font-family:ui-monospace,monospace; font-weight:600;"><?php echo e(coinSymbol()); ?></span>
                        <span class="mono" style="font-size:18px; font-weight:600;"><?php echo e(number_format($user->coin_balance)); ?></span>
                    </div>
                    <div style="font-size:10.5px; color:var(--fg-3);">Available</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:4px;">Rank progress</div>
                    <div class="mono" style="font-size:18px; font-weight:600;"><?php echo e($progress); ?>%</div>
                    <div style="font-size:10.5px; color:var(--fg-3);">To next level</div>
                </div>
            </div>

            <div style="margin-top:20px;">
                <div class="label" style="margin-bottom:10px;">Skills</div>
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <?php $__empty_1 = true; $__currentLoopData = $skills->whereIn('id', $userSkillIds); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <span class="chip" style="background:var(--accent-soft); color:var(--accent); border-color:rgba(47,84,235,0.3);"><?php echo e($skill->name); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <span style="font-size:12.5px; color:var(--fg-3);">No skills added yet — edit your profile below</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="card" style="padding:24px; margin-bottom:16px;">
            <h3 style="font-size:15px; font-weight:600; margin:0 0 20px; color:var(--fg);">Edit profile</h3>
            <form method="POST" action="<?php echo e(route('user.profile.update')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

                <?php if(session('success')): ?>
                <div style="padding:10px 14px; background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.25); border-radius:8px; font-size:13px; color:#22C55E; margin-bottom:16px;"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <?php if($errors->any()): ?>
                <div style="padding:10px 14px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:8px; font-size:13px; color:#FCA5A5; margin-bottom:16px;"><?php echo e($errors->first()); ?></div>
                <?php endif; ?>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;" class="form-2col">
                    <div>
                        <label style="display:block; font-size:12px; color:var(--fg-2); margin-bottom:6px; font-weight:500;">First name</label>
                        <input type="text" name="firstname" value="<?php echo e(old('firstname', $user->firstname)); ?>" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; color:var(--fg-2); margin-bottom:6px; font-weight:500;">Last name</label>
                        <input type="text" name="lastname" value="<?php echo e(old('lastname', $user->lastname)); ?>" required>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:12px; color:var(--fg-2); margin-bottom:6px; font-weight:500;">Username</label>
                    <input type="text" name="username" value="<?php echo e(old('username', $user->username)); ?>" required>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:12px; color:var(--fg-2); margin-bottom:6px; font-weight:500;">Email address</label>
                    <input type="email" value="<?php echo e($user->email); ?>" readonly
                           style="background:var(--surface-2); color:var(--fg-3); cursor:not-allowed;"
                           title="Email cannot be changed here. Contact support to update your email.">
                    <div style="font-size:11px; color:var(--fg-4); margin-top:4px;">To change your email address, contact support.</div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;" class="form-2col">
                    <div>
                        <label style="display:block; font-size:12px; color:var(--fg-2); margin-bottom:6px; font-weight:500;">Country code</label>
                        <input type="text" name="country_code" value="<?php echo e(old('country_code', $user->country_code)); ?>" placeholder="e.g. TZ">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; color:var(--fg-2); margin-bottom:6px; font-weight:500;">Mobile</label>
                        <input type="text" name="mobile" value="<?php echo e(old('mobile', $user->mobile)); ?>" placeholder="+255...">
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:12px; color:var(--fg-2); margin-bottom:10px; font-weight:500;">Skills</label>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;" id="skill-list">
                        <?php $__currentLoopData = $skills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $checked = in_array($skill->id, $userSkillIds); ?>
                        <label style="cursor:pointer; user-select:none;">
                            <input type="checkbox" name="skills[]" value="<?php echo e($skill->id); ?>" <?php echo e($checked ? 'checked' : ''); ?> style="display:none;" class="skill-cb">
                            <span class="chip skill-chip <?php echo e($checked ? 'skill-active' : ''); ?>" style="<?php echo e($checked ? 'background:var(--accent-soft);color:var(--accent);border-color:rgba(47,84,235,0.35);' : ''); ?>"><?php echo e($skill->name); ?></span>
                        </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:9px 20px;">Save changes</button>
                </div>
            </form>
        </div>

        
        <div class="card" style="padding:24px;">
            <h3 style="font-size:15px; font-weight:600; margin:0 0 16px; color:var(--fg);">KYC Verification</h3>

            <?php if($kyc == 1): ?>
            <div style="display:flex; align-items:center; gap:14px; padding:14px 16px; background:rgba(34,197,94,0.08); border-radius:10px; border:1px solid rgba(34,197,94,0.2); margin-bottom:20px;">
                <span style="width:32px;height:32px;border-radius:50%;background:#22C55E;color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i data-lucide="check" style="width:16px;height:16px;"></i></span>
                <div style="flex:1;"><div style="font-size:13px;font-weight:600;color:#86EFAC;">Fully verified</div><div style="font-size:11.5px;color:var(--fg-2);">Access to all instant jobs, hiring, and contracts</div></div>
            </div>
            <?php elseif($kyc == 2): ?>
            <div style="display:flex; align-items:center; gap:14px; padding:14px 16px; background:rgba(245,158,11,0.08); border-radius:10px; border:1px solid rgba(245,158,11,0.2); margin-bottom:20px;">
                <span style="width:32px;height:32px;border-radius:50%;background:rgba(245,158,11,0.2);color:#F59E0B;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i data-lucide="clock" style="width:16px;height:16px;"></i></span>
                <div style="flex:1;"><div style="font-size:13px;font-weight:600;color:#FCD34D;">Under review</div><div style="font-size:11.5px;color:var(--fg-2);">Usually 24–48 hours. We'll notify you.</div></div>
            </div>
            <?php else: ?>
            <div style="display:flex; align-items:center; gap:14px; padding:14px 16px; background:rgba(239,68,68,0.06); border-radius:10px; border:1px solid rgba(239,68,68,0.2); margin-bottom:20px;">
                <span style="width:32px;height:32px;border-radius:50%;background:rgba(239,68,68,0.15);color:#EF4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i data-lucide="shield-alert" style="width:16px;height:16px;"></i></span>
                <div style="flex:1;"><div style="font-size:13px;font-weight:600;color:#FCA5A5;">Not verified</div><div style="font-size:11.5px;color:var(--fg-2);">Required to withdraw earnings</div></div>
                <a href="<?php echo e(route('user.profile.kyc')); ?>" class="btn btn-primary" style="font-size:12px;padding:7px 14px;flex-shrink:0;">Verify →</a>
            </div>
            <?php endif; ?>

            <div style="display:flex; flex-direction:column;">
                <?php
                    $kycRows = [
                        [
                            'title'  => 'Email address',
                            'done'   => $user->email_verified ?? false,
                            'detail' => $user->email,
                            'label'  => ($user->email_verified ?? false) ? 'Verified' : 'Not verified',
                            'icon'   => ($user->email_verified ?? false) ? 'check' : 'x',
                        ],
                        [
                            'title'  => 'Phone number',
                            'done'   => $user->phone_verified ?? false,
                            'detail' => $user->mobile ?: 'Not added',
                            'label'  => ($user->phone_verified ?? false) ? 'Verified' : ($user->mobile ? 'Not verified' : 'Not added'),
                            'icon'   => ($user->phone_verified ?? false) ? 'check' : 'x',
                        ],
                        [
                            'title'  => 'Government ID',
                            'done'   => $kyc == 1,
                            'detail' => $kyc == 1 ? 'Submitted & approved' : ($kyc >= 2 ? 'Submitted — awaiting review' : 'Not submitted'),
                            'label'  => $kyc == 1 ? 'Approved' : ($kyc >= 2 ? 'Submitted' : 'Not submitted'),
                            'icon'   => $kyc == 1 ? 'check' : ($kyc >= 2 ? 'clock' : 'x'),
                        ],
                        [
                            'title'  => 'KYC documents',
                            'done'   => $kyc == 1,
                            'detail' => $kyc == 1 ? 'Approved' : ($kyc == 2 ? 'Under review — usually 24–48h' : 'Not submitted'),
                            'label'  => $kyc == 1 ? 'Verified' : ($kyc == 2 ? 'Under review' : 'Not submitted'),
                            'icon'   => $kyc == 1 ? 'check' : ($kyc == 2 ? 'clock' : 'x'),
                        ],
                    ];
                ?>
                <?php $__currentLoopData = $kycRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex; align-items:center; gap:14px; padding:12px 0; border-bottom:<?php echo e($i < 3 ? '1px solid var(--border)' : 'none'); ?>;">
                    <span style="width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;
                                 background:<?php echo e($row['done'] ? 'rgba(34,197,94,0.12)' : ($row['icon'] === 'clock' ? 'rgba(245,158,11,0.1)' : 'var(--surface-2)')); ?>;
                                 color:<?php echo e($row['done'] ? '#22C55E' : ($row['icon'] === 'clock' ? '#F59E0B' : 'var(--fg-4)')); ?>;">
                        <i data-lucide="<?php echo e($row['icon']); ?>" style="width:13px; height:13px;"></i>
                    </span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:13px; font-weight:500; color:var(--fg);"><?php echo e($row['title']); ?></div>
                        <div style="font-size:11.5px; color:var(--fg-3);"><?php echo e($row['detail']); ?></div>
                    </div>
                    <span style="font-size:11px; flex-shrink:0;
                                 color:<?php echo e($row['done'] ? '#22C55E' : ($row['icon'] === 'clock' ? '#F59E0B' : 'var(--fg-4)')); ?>;">
                        <?php echo e($row['label']); ?>

                    </span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    
    <div>
        <div class="card" style="padding:20px; margin-bottom:14px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 14px; color:var(--fg);">Rank progress</h4>
            <div style="text-align:center; padding:8px 0 16px;">
                <div style="font-size:32px; margin-bottom:4px;"><?php echo e($rank['icon']); ?></div>
                <div style="font-size:18px; font-weight:600; color:var(--fg);"><?php echo e($rank['label']); ?></div>
                <div style="font-size:12px; color:var(--fg-3);"><?php echo e($rank['score']); ?> pts</div>
            </div>
            <div style="height:6px; border-radius:3px; background:var(--surface-3); overflow:hidden; margin-bottom:8px;">
                <div style="width:<?php echo e($progress); ?>%; height:100%; background:var(--accent); border-radius:3px;"></div>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--fg-3);">
                <span><?php echo e($rank['label']); ?></span><span><?php echo e($progress); ?>% to next</span>
            </div>
        </div>

        <div class="card" style="padding:20px; margin-bottom:14px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 12px; color:var(--fg);">Badges</h4>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px;">
                <?php
                    $badges = [];
                    if ($approvedCount >= 100)  $badges[] = ['100+ tasks', '#F59E0B', 'star'];
                    if ($approvalRate >= 95)     $badges[] = ['Top 5%', 'var(--accent)', 'trending-up'];
                    if ($kyc == 1)               $badges[] = ['Verified', '#22C55E', 'shield-check'];
                    if ($user->email_verified)   $badges[] = ['Email OK', '#60A5FA', 'mail'];
                    if ($approvedCount >= 10)    $badges[] = ['10 done', 'var(--urgent)', 'zap'];
                    if ($user->created_at->diffInMonths(now()) >= 12) $badges[] = ['1-year', '#EC4899', 'calendar'];
                ?>
                <?php $__empty_1 = true; $__currentLoopData = $badges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div style="padding:10px; background:var(--surface-2); border-radius:8px; text-align:center;">
                    <i data-lucide="<?php echo e($b[2]); ?>" style="width:18px; height:18px; color:<?php echo e($b[1]); ?>; margin:0 auto 4px; display:block;"></i>
                    <div style="font-size:10.5px; color:var(--fg-2);"><?php echo e($b[0]); ?></div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div style="grid-column:1/-1; text-align:center; padding:16px; color:var(--fg-3); font-size:12px;">Complete tasks to earn badges</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="padding:20px; margin-bottom:14px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 12px; color:var(--fg);">Security</h4>
            <a href="<?php echo e(route('user.profile.password')); ?>" class="btn" style="width:100%; justify-content:center; font-size:13px; display:flex; align-items:center; gap:6px;">
                <i data-lucide="lock" style="width:13px; height:13px;"></i> Change password
            </a>
        </div>

        <?php if(Route::has('user.referral.index')): ?>
        <div class="card" style="padding:20px;">
            <h4 style="font-size:13px; font-weight:600; margin:0 0 8px; color:var(--fg);">Referrals</h4>
            <p style="font-size:12px; color:var(--fg-3); margin:0 0 12px; line-height:1.5;">Invite friends and earn coins for each signup.</p>
            <a href="<?php echo e(route('user.referral.index')); ?>" class="btn" style="width:100%; justify-content:center; font-size:13px; display:flex; align-items:center; gap:6px;">
                <i data-lucide="users" style="width:13px; height:13px;"></i> View referrals
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.skill-chip').forEach(function(chip) {
    chip.addEventListener('click', function() {
        const cb = this.parentElement.querySelector('.skill-cb');
        cb.checked = !cb.checked;
        if (cb.checked) {
            this.style.background = 'var(--accent-soft)';
            this.style.color = 'var(--accent)';
            this.style.borderColor = 'rgba(47,84,235,0.35)';
        } else {
            this.style.background = '';
            this.style.color = '';
            this.style.borderColor = '';
        }
    });
});
</script>

<style>
@media (max-width: 900px) {
    .profile-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 640px) {
    .profile-stats-row { grid-template-columns: repeat(2,1fr) !important; }
    .form-2col { grid-template-columns: 1fr !important; }
}
</style>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/profile/settings.blade.php ENDPATH**/ ?>