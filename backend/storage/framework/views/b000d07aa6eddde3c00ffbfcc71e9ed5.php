<?php $__env->startSection('title', $listing->title); ?>
<?php $__env->startSection('page-title', __('Job Detail')); ?>

<?php $__env->startSection('content'); ?>


<div style="font-size:12.5px; color:var(--fg-3); margin-bottom:24px; display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
    <a href="<?php echo e(route('user.jobs.browse')); ?>" style="color:var(--fg-3); text-decoration:none; transition:color .15s;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'"><?php echo e(__('Jobs')); ?></a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-2);"><?php echo e(Str::limit($listing->title, 45)); ?></span>
</div>

<div style="display:grid; grid-template-columns:1fr 320px; gap:32px; align-items:start;" class="job-detail-grid">

    
    <div>

        
        <?php if($listing->cover_image): ?>
        <div style="border-radius:12px; overflow:hidden; margin-bottom:24px; height:200px;">
            <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image)); ?>"
                 alt="<?php echo e($listing->title); ?>" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <?php endif; ?>

        
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap;">
            <span class="chip chip-hiring" style="font-size:10.5px; text-transform:uppercase; font-weight:600;">💼 <?php echo e(__('Hiring')); ?></span>
            <?php if($listing->category): ?>
            <span class="chip" style="font-size:11px;"><?php echo e($listing->category->name); ?></span>
            <?php endif; ?>
            <?php if($listing->employment_type): ?>
            <span class="chip" style="font-size:11px;"><?php echo e($listing->employmentTypeLabel); ?></span>
            <?php endif; ?>
            <?php if($listing->location_type): ?>
            <span class="chip" style="font-size:11px;"><?php echo e($listing->locationTypeLabel); ?></span>
            <?php endif; ?>
            <?php if($listing->is_featured): ?>
            <span style="font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; background:rgba(245,181,71,0.14); color:#ca8a04; border:1px solid rgba(245,181,71,0.25);">★ <?php echo e(__('Featured')); ?></span>
            <?php endif; ?>
        </div>

        
        <h1 style="font-size:clamp(24px,3vw,36px); font-weight:700; letter-spacing:-0.75px; line-height:1.15; margin:0 0 14px; color:var(--fg);">
            <?php echo e($listing->title); ?>

        </h1>

        
        <div style="display:flex; gap:14px; align-items:center; margin-bottom:28px; font-size:13px; color:var(--fg-3); flex-wrap:wrap;">
            <?php if($listing->employer): ?>
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:22px; height:22px; border-radius:50%; background:linear-gradient(135deg,#2f54eb,#60A5FA); display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:9px; flex-shrink:0;">
                    <?php echo e(strtoupper(substr($listing->employer->name ?? $listing->employer->username ?? '?', 0, 1))); ?>

                </div>
                <span style="font-weight:500; color:var(--fg-2);"><?php echo e($listing->employer->name ?? $listing->employer->username); ?></span>
                <?php if($listing->employer->kyc_status === 1): ?>
                <span style="color:var(--accent); display:inline-flex; align-items:center; gap:3px; font-size:11px;">
                    <svg width="12" height="12" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 1L2 4v5c0 4 3 7 7 8 4-1 7-4 7-8V4L9 1z"/></svg>
                    <?php echo e(__('Verified')); ?>

                </span>
                <?php endif; ?>
            </div>
            <span style="color:var(--fg-4);">·</span>
            <?php endif; ?>
            <span><?php echo e(__('Posted')); ?> <?php echo e($listing->created_at->diffForHumans()); ?></span>
            <?php if($listing->location && $listing->location_type != 1): ?>
            <span style="color:var(--fg-4);">·</span>
            <span>📍 <?php echo e($listing->location); ?></span>
            <?php endif; ?>
            <?php if($listing->closes_at): ?>
            <span style="color:var(--fg-4);">·</span>
            <span style="color:<?php echo e($listing->closes_at->isPast() ? 'var(--danger)' : 'var(--fg-3)'); ?>;">
                <?php echo e($listing->closes_at->isPast() ? __('Closed') : __('Closes') . ' ' . $listing->closes_at->diffForHumans()); ?>

            </span>
            <?php endif; ?>
        </div>

        
        <div class="card" style="padding:24px; margin-bottom:20px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:14px;"><?php echo e(__('About the role')); ?></div>
            <div class="job-prose" style="font-size:14px; color:var(--fg-2); line-height:1.7;">
                <?php echo richBody($listing->description); ?>

            </div>
        </div>

        
        <?php if($listing->requirements): ?>
        <div class="card" style="padding:24px; margin-bottom:20px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:14px;"><?php echo e(__('Requirements')); ?></div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php $__currentLoopData = explode("\n", $listing->requirements); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(trim($req)): ?>
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="#2f54eb" stroke-width="1.8" stroke-linecap="round" style="flex-shrink:0; margin-top:2px;"><path d="M3 9l4 4 8-8"/></svg>
                    <span style="font-size:13.5px; color:var(--fg-2);"><?php echo e(trim($req)); ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        
        <?php if($listing->benefits): ?>
        <div class="card" style="padding:24px; margin-bottom:20px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:14px;"><?php echo e(__('Benefits')); ?></div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php $__currentLoopData = explode("\n", $listing->benefits); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $benefit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(trim($benefit)): ?>
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="#60A5FA" stroke-width="1.8" stroke-linecap="round" style="flex-shrink:0; margin-top:2px;"><path d="M9 1L2 4v5c0 4 3 7 7 8 4-1 7-4 7-8V4L9 1z"/></svg>
                    <span style="font-size:13.5px; color:var(--fg-2);"><?php echo e(trim($benefit)); ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        
        <?php if($listing->skills->isNotEmpty()): ?>
        <div class="card" style="padding:24px; margin-bottom:20px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:14px;"><?php echo e(__('Skills')); ?></div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php $__currentLoopData = $listing->skills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="chip" style="font-size:12px;"><?php echo e($skill->name); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        
        <?php if($listing->closes_at && $listing->closes_at->isPast()): ?>
        <div class="card" style="padding:24px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">🔒</div>
            <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:6px;"><?php echo e(__('Applications closed')); ?></div>
            <p style="font-size:13px; color:var(--fg-3);"><?php echo e(__('This position is no longer accepting applications.')); ?></p>
        </div>

        <?php elseif($application): ?>
        
        <?php
            $statusColors = [0=>'rgba(245,158,11,0.14)',1=>'rgba(96,165,250,0.14)',2=>'rgba(168,85,247,0.14)',3=>'rgba(34,197,94,0.14)',4=>'rgba(239,68,68,0.14)'];
            $statusText   = [0=>'#ca8a04',1=>'#60A5FA',2=>'#a855f7',3=>'#22C55E',4=>'#EF4444'];
            $statusLabels = [0=>__('Pending'),1=>__('Reviewed'),2=>__('Shortlisted'),3=>__('Accepted'),4=>__('Rejected')];
        ?>
        <div class="card" style="padding:24px;">
            <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:<?php echo e($application->employer_note ? '20px' : '0'); ?>;">
                <div style="width:40px; height:40px; border-radius:10px; background:rgba(34,197,94,0.12); color:#22C55E; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9l4 4 8-8"/></svg>
                </div>
                <div>
                    <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:6px;"><?php echo e(__("You've already applied")); ?></div>
                    <div style="display:flex; align-items:center; gap:10px; font-size:13px; color:var(--fg-3); flex-wrap:wrap;">
                        <span><?php echo e(__('Status')); ?>:</span>
                        <span style="font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:20px; background:<?php echo e($statusColors[$application->status] ?? $statusColors[0]); ?>; color:<?php echo e($statusText[$application->status] ?? $statusText[0]); ?>;">
                            <?php echo e($statusLabels[$application->status] ?? __('Unknown')); ?>

                        </span>
                        <span>·</span>
                        <span><?php echo e(__('Applied')); ?> <?php echo e($application->created_at->diffForHumans()); ?></span>
                    </div>
                </div>
            </div>

            <?php if($application->employer_note): ?>
            <div style="background:var(--surface-2); border-radius:8px; padding:14px; font-size:13px; color:var(--fg-2); line-height:1.6;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--fg-3); margin-bottom:6px;"><?php echo e(__("Employer's note")); ?></div>
                <?php echo e($application->employer_note); ?>

            </div>
            <?php endif; ?>

            <?php if($application->status === 0): ?>
            <form method="POST" action="<?php echo e(route('user.jobs.applications.withdraw', $application->id)); ?>" style="margin-top:16px;">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button type="submit" onclick="return confirm('<?php echo e(__('Withdraw your application?')); ?>')"
                        style="background:none; border:none; cursor:pointer; font-size:13px; color:var(--danger); padding:0; font-family:inherit;">
                    <?php echo e(__('Withdraw application')); ?>

                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php else: ?>
        
        <div class="card" style="padding:24px;" id="apply-form">
            <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:20px;"><?php echo e(__('Apply for this position')); ?></div>

            <?php if($errors->any()): ?>
            <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#EF4444;">
                <ul style="margin:0; padding-left:16px;">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('user.jobs.apply', $listing->id)); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:var(--fg-3); margin-bottom:7px; text-transform:uppercase; letter-spacing:0.06em;">
                        <?php echo e(__('Cover Letter')); ?> <span style="color:var(--danger);">*</span>
                    </label>
                    <textarea name="cover_letter" rows="7"
                              placeholder="<?php echo e(__('Tell the employer why you\'re the right fit…')); ?>"
                              style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px 14px; color:var(--fg); font-size:13.5px; line-height:1.6; font-family:inherit; resize:vertical; outline:none; box-sizing:border-box; <?php $__errorArgs = ['cover_letter'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-color:rgba(239,68,68,0.5); <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('cover_letter')); ?></textarea>
                    <?php $__errorArgs = ['cover_letter'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p style="color:#EF4444; font-size:12px; margin:5px 0 0;"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--fg-3); margin-bottom:7px; text-transform:uppercase; letter-spacing:0.06em;"><?php echo e(__('Expected Salary')); ?></label>
                        <input type="number" name="expected_salary" value="<?php echo e(old('expected_salary')); ?>" min="0"
                               placeholder="0"
                               style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--fg-3); margin-bottom:7px; text-transform:uppercase; letter-spacing:0.06em;"><?php echo e(__('Currency')); ?></label>
                        <input type="text" name="expected_salary_currency" value="<?php echo e(old('expected_salary_currency', 'USD')); ?>"
                               placeholder="USD"
                               style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:var(--fg-3); margin-bottom:7px; text-transform:uppercase; letter-spacing:0.06em;"><?php echo e(__('Portfolio URL')); ?></label>
                    <input type="url" name="portfolio_url" value="<?php echo e(old('portfolio_url')); ?>"
                           placeholder="https://…"
                           style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box; <?php $__errorArgs = ['portfolio_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-color:rgba(239,68,68,0.5); <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['portfolio_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p style="color:#EF4444; font-size:12px; margin:5px 0 0;"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:var(--fg-3); margin-bottom:7px; text-transform:uppercase; letter-spacing:0.06em;">
                        <?php echo e(__('Resume')); ?> <span style="font-size:11px; color:var(--fg-4); text-transform:none; letter-spacing:0;">(PDF, DOC, DOCX — <?php echo e(__('max 5MB')); ?>)</span>
                    </label>
                    <input type="file" name="resume" accept=".pdf,.doc,.docx"
                           style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:var(--fg-3); font-size:13px; box-sizing:border-box; <?php $__errorArgs = ['resume'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-color:rgba(239,68,68,0.5); <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['resume'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p style="color:#EF4444; font-size:12px; margin:5px 0 0;"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-size:14px; display:flex; gap:8px; align-items:center;">
                    💼 <?php echo e(__('Submit Application')); ?>

                </button>
                <p style="text-align:center; font-size:12px; color:var(--fg-4); margin-top:10px;">
                    <?php echo e(__('The employer will review and contact you directly.')); ?>

                </p>
            </form>
        </div>
        <?php endif; ?>

    </div>

    
    <aside style="position:sticky; top:80px;">

        
        <div class="card" style="padding:24px; margin-bottom:16px;">
            <div style="text-align:center; padding-bottom:20px; border-bottom:1px solid var(--border); margin-bottom:20px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; color:var(--fg-3); margin-bottom:8px;"><?php echo e(__('Salary')); ?></div>
                <?php if($listing->salary_visible && ($listing->salary_min || $listing->salary_max)): ?>
                <div class="mono" style="font-size:28px; font-weight:700; letter-spacing:-1px; line-height:1; color:var(--fg);">
                    <?php if($listing->salary_min && $listing->salary_max): ?>
                        <?php echo e(coinSymbol()); ?><?php echo e(number_format($listing->salary_min)); ?>–<?php echo e(number_format($listing->salary_max)); ?>

                    <?php elseif($listing->salary_min): ?>
                        <?php echo e(coinSymbol()); ?><?php echo e(number_format($listing->salary_min)); ?>+
                    <?php else: ?>
                        <?php echo e(__('up to')); ?> <?php echo e(coinSymbol()); ?><?php echo e(number_format($listing->salary_max)); ?>

                    <?php endif; ?>
                </div>
                <div style="font-size:12px; color:var(--fg-3); margin-top:6px;"><?php echo e($listing->salary_currency ?? 'TZS'); ?> / <?php echo e(__('month')); ?></div>
                <?php else: ?>
                <div style="font-size:15px; font-weight:500; color:var(--fg-3);"><?php echo e(__('Undisclosed')); ?></div>
                <?php endif; ?>
            </div>

            <div style="display:flex; flex-direction:column; gap:13px; margin-bottom:22px;">
                <?php if($listing->employment_type): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--fg-3);"><?php echo e(__('Type')); ?></span>
                    <span style="font-weight:500; color:var(--fg);"><?php echo e($listing->employmentTypeLabel); ?></span>
                </div>
                <?php endif; ?>
                <?php if($listing->location_type): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--fg-3);"><?php echo e(__('Location')); ?></span>
                    <span style="font-weight:500; color:var(--fg);"><?php echo e($listing->locationTypeLabel); ?></span>
                </div>
                <?php endif; ?>
                <?php if($listing->location && $listing->location_type != 1): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--fg-3);"><?php echo e(__('City')); ?></span>
                    <span style="font-weight:500; color:var(--fg);"><?php echo e($listing->location); ?></span>
                </div>
                <?php endif; ?>
                <?php if($listing->category): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--fg-3);"><?php echo e(__('Category')); ?></span>
                    <span style="font-weight:500; color:var(--fg);"><?php echo e($listing->category->name); ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--fg-3);"><?php echo e(__('Applicants')); ?></span>
                    <span class="mono" style="font-weight:500; color:var(--fg);"><?php echo e($listing->application_count); ?></span>
                </div>
                <?php if($listing->closes_at && !$listing->closes_at->isPast()): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--fg-3);"><?php echo e(__('Deadline')); ?></span>
                    <span style="font-weight:500; color:var(--fg);"><?php echo e($listing->closes_at->format('M j, Y')); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if($listing->closes_at && $listing->closes_at->isPast()): ?>
            <div style="text-align:center; padding:10px; background:var(--surface-2); border-radius:8px; font-size:13px; color:var(--fg-3);">
                <?php echo e(__('Applications closed')); ?>

            </div>
            <?php elseif($application): ?>
            <div style="text-align:center; padding:12px; background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.2); border-radius:8px; font-size:13.5px; color:#22C55E; font-weight:500;">
                ✓ <?php echo e(__("Application submitted")); ?>

            </div>
            <?php endif; ?>
        </div>

        
        <div x-data="{ bookmarked: <?php echo e($isBookmarked ? 'true' : 'false'); ?> }">
            <form method="POST" action="<?php echo e(route('user.jobs.bookmark', $listing->id)); ?>" @submit.prevent="
                bookmarked = !bookmarked;
                fetch('<?php echo e(route('user.jobs.bookmark', $listing->id)); ?>', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' }
                });
            ">
                <?php echo csrf_field(); ?>
                <button type="submit" style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px; padding:10px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); font-size:13px; cursor:pointer; transition:all .15s;"
                    :style="bookmarked ? 'border-color:var(--accent); background:rgba(47,84,235,0.08); color:var(--accent);' : 'color:var(--fg-3);'"
                    onmouseover="if(!this.closest('[x-data]').__x.$data.bookmarked) this.style.color='var(--fg)'" onmouseout="if(!this.closest('[x-data]').__x.$data.bookmarked) this.style.color='var(--fg-3)'">
                    <svg width="15" height="15" viewBox="0 0 24 24" :fill="bookmarked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    <span x-text="bookmarked ? '<?php echo e(__('Saved')); ?>' : '<?php echo e(__('Save Job')); ?>'"></span>
                </button>
            </form>
        </div>

        
        <a href="<?php echo e(route('user.jobs.browse')); ?>" style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--fg-3); text-decoration:none; transition:color .15s; padding:4px 0;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">
            <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4L6 9l5 5"/></svg>
            <?php echo e(__('Back to Jobs')); ?>

        </a>

    </aside>
</div>

<style>
.job-detail-grid { grid-template-columns: 1fr 320px; }
@media (max-width:1100px) {
    .job-detail-grid { grid-template-columns: 1fr !important; }
}
.job-prose p { margin: 0 0 10px; }
.job-prose p:last-child { margin-bottom: 0; }
input:focus, textarea:focus, select:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(47,84,235,0.15);
}
</style>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/jobs/show.blade.php ENDPATH**/ ?>