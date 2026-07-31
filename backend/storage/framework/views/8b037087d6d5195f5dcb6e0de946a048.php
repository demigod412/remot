<?php $__env->startSection('title', 'KYC Review'); ?>
<?php $__env->startSection('page-title', 'KYC Review'); ?>

<?php $__env->startSection('content'); ?>


<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Pending</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;color:#F59E0B;"><?php echo e($users->total()); ?></div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">
            <?php echo e($users->total() > 0 ? 'Oldest: ' . $users->first()?->updated_at->diffForHumans() : 'All clear'); ?>

        </div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Approved (7d)</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;color:#22C55E;"><?php echo e(number_format($approvedRecent)); ?></div>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Rejected (7d)</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;color:#EF4444;"><?php echo e(number_format($rejectedRecent)); ?></div>
        <?php if($users->total() + $approvedRecent + $rejectedRecent > 0): ?>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">
            <?php echo e($approvedRecent + $rejectedRecent > 0 ? round(($rejectedRecent / ($approvedRecent + $rejectedRecent)) * 100, 1) . '% rate' : '—'); ?>

        </div>
        <?php endif; ?>
    </div>
    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Avg review time</div>
        <div class="mono" style="font-size:26px;font-weight:600;letter-spacing:-0.8px;">—</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">not tracked</div>
    </div>
</div>


<div style="display:grid;grid-template-columns:320px 1fr;gap:16px;">

    
    <div class="jobstation-card" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;">KYC queue</div>
        <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $ini  = strtoupper(substr($u->firstname ?? $u->username ?? 'U', 0, 1));
            $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
            $clr  = $clrs[ord($ini) % count($clrs)];
            $days = $u->updated_at->diffInDays(now());
            $priority = $days >= 3 ? 'high' : ($days < 1 ? 'low' : 'normal');
            $pColor   = $priority === 'high' ? '#EF4444' : ($priority === 'low' ? '#22C55E' : 'var(--fg-3)');
            $pBg      = $priority === 'high' ? 'rgba(239,68,68,0.12)' : ($priority === 'low' ? 'rgba(34,197,94,0.12)' : 'var(--surface-2)');
            $isSelected = $selectedUser && $selectedUser->id === $u->id;
        ?>
        <a href="<?php echo e(route('admin.users.kyc', ['review' => $u->id])); ?>"
           style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border);text-decoration:none;
                  background:<?php echo e($isSelected ? 'var(--surface-2)' : 'transparent'); ?>;
                  border-left:<?php echo e($isSelected ? '3px solid var(--accent)' : '3px solid transparent'); ?>;
                  transition:background .12s;">
            <div style="width:28px;height:28px;border-radius:50%;background:<?php echo e($clr); ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:600;flex-shrink:0;"><?php echo e($ini); ?></div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:12.5px;font-weight:500;color:var(--fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($u->fullname); ?></div>
                <div style="font-size:10.5px;color:var(--fg-3);">L0 → L2 · <?php echo e($u->updated_at->diffForHumans()); ?></div>
            </div>
            <span style="font-size:9.5px;font-weight:500;padding:2px 7px;border-radius:999px;color:<?php echo e($pColor); ?>;background:<?php echo e($pBg); ?>;flex-shrink:0;"><?php echo e($priority); ?></span>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="padding:40px;text-align:center;color:var(--fg-3);font-size:13px;">
            <i data-lucide="shield-check" style="width:32px;height:32px;margin:0 auto 10px;display:block;opacity:0.3;"></i>
            All clear — no pending KYC
        </div>
        <?php endif; ?>
        <?php if($users->hasPages()): ?>
        <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:11.5px;color:var(--fg-3);">
            <?php if($users->onFirstPage()): ?>
                <span style="opacity:0.3;">← Prev</span>
            <?php else: ?>
                <a href="<?php echo e($users->previousPageUrl()); ?>" style="color:var(--accent);text-decoration:none;">← Prev</a>
            <?php endif; ?>
            <?php if($users->hasMorePages()): ?>
                <a href="<?php echo e($users->nextPageUrl()); ?>" style="color:var(--accent);text-decoration:none;">Next →</a>
            <?php else: ?>
                <span style="opacity:0.3;">Next →</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    
    <?php if($selectedUser): ?>
    <?php
        $ini  = strtoupper(substr($selectedUser->firstname ?? $selectedUser->username ?? 'U', 0, 1));
        $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
        $clr  = $clrs[ord($ini) % count($clrs)];
        $kycData     = $selectedUser->kyc_data ?? [];
        $frontImage  = $kycData['front_image']  ?? null;
        $backImage   = $kycData['back_image']   ?? null;
        $docType     = $kycData['document_type'] ?? null;
        $submittedAt = isset($kycData['submitted_at']) ? \Carbon\Carbon::parse($kycData['submitted_at'])->diffForHumans() : $selectedUser->updated_at->diffForHumans();
        $days        = $selectedUser->updated_at->diffInDays(now());
        $isHighRisk  = $days >= 3;
    ?>
    <div class="jobstation-card" style="padding:24px;overflow:auto;">

        
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
            <div style="width:56px;height:56px;border-radius:50%;overflow:hidden;flex-shrink:0;">
                <?php if($selectedUser->image): ?>
                    <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.avatars'), $selectedUser->image)); ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                <?php else: ?>
                    <div style="width:100%;height:100%;background:<?php echo e($clr); ?>;display:flex;align-items:center;justify-content:center;color:white;font-size:22px;font-weight:600;"><?php echo e($ini); ?></div>
                <?php endif; ?>
            </div>
            <div style="flex:1;">
                <h2 style="font-size:18px;font-weight:600;margin:0 0 4px;"><?php echo e($selectedUser->fullname); ?></h2>
                <div style="font-size:12px;color:var(--fg-3);">user #<?php echo e($selectedUser->id); ?> · <?php echo e('@' . $selectedUser->username); ?> · Applied <?php echo e($submittedAt); ?></div>
            </div>
            <?php if($isHighRisk): ?>
            <span style="font-size:10.5px;font-weight:600;padding:4px 10px;border-radius:999px;background:rgba(239,68,68,0.12);color:#EF4444;border:1px solid rgba(239,68,68,0.3);flex-shrink:0;">HIGH RISK</span>
            <?php else: ?>
            <span style="font-size:10.5px;font-weight:600;padding:4px 10px;border-radius:999px;background:rgba(245,158,11,0.12);color:#F59E0B;border:1px solid rgba(245,158,11,0.3);flex-shrink:0;">PENDING</span>
            <?php endif; ?>
        </div>

        
        <?php if($isHighRisk): ?>
        <div style="padding:14px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:10px;display:flex;gap:12px;align-items:flex-start;margin-bottom:20px;">
            <i data-lucide="flag" style="width:16px;height:16px;color:#EF4444;flex-shrink:0;margin-top:1px;"></i>
            <div style="font-size:12.5px;color:var(--fg-2);line-height:1.55;">
                <strong style="color:#FCA5A5;">Waiting <?php echo e($days); ?> days</strong> — This application has exceeded the normal review window. Please review promptly.
            </div>
        </div>
        <?php endif; ?>

        
        <?php
            $frontApproved = $kycData['front_approved'] ?? false;
            $backApproved  = $kycData['back_approved']  ?? false;
        ?>
        <?php if($frontImage || $backImage): ?>
        <div style="display:grid;grid-template-columns:<?php echo e($frontImage && $backImage ? '1fr 1fr' : '1fr'); ?>;gap:14px;margin-bottom:20px;">
            <?php if($frontImage): ?>
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <div class="label">Government ID — Front</div>
                    <?php if($frontApproved): ?>
                    <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:999px;background:rgba(34,197,94,0.12);color:#22C55E;border:1px solid rgba(34,197,94,0.25);">✓ Approved</span>
                    <?php else: ?>
                    <form method="POST" action="<?php echo e(route('admin.users.kyc.approve-doc', $selectedUser->id)); ?>" style="margin:0;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="doc_side" value="front">
                        <button type="submit" style="font-size:10px;font-weight:500;padding:2px 8px;border-radius:999px;background:transparent;border:1px solid rgba(34,197,94,0.4);color:#22C55E;cursor:pointer;font-family:inherit;">
                            Approve front
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php $ext = strtolower(pathinfo($frontImage, PATHINFO_EXTENSION)); ?>
                <?php if(in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                <a href="<?php echo e(route('secure.kyc', ['user' => $selectedUser->id, 'side' => 'front'])); ?>" target="_blank"
                   style="display:block;border-radius:10px;overflow:hidden;border:2px solid <?php echo e($frontApproved ? 'rgba(34,197,94,0.4)' : 'var(--border)'); ?>;">
                    <img src="<?php echo e(route('secure.kyc', ['user' => $selectedUser->id, 'side' => 'front'])); ?>"
                         style="width:100%;height:180px;object-fit:cover;display:block;" alt="Front">
                </a>
                <?php else: ?>
                <a href="<?php echo e(route('secure.kyc', ['user' => $selectedUser->id, 'side' => 'front'])); ?>" target="_blank"
                   style="display:flex;align-items:center;gap:8px;padding:12px 14px;border-radius:8px;border:1px solid var(--border);background:var(--surface-2);color:var(--fg);text-decoration:none;font-size:13px;">
                    <i data-lucide="file-text" style="width:15px;height:15px;color:var(--accent);"></i> View document
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if($backImage): ?>
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <div class="label">Government ID — Back</div>
                    <?php if($backApproved): ?>
                    <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:999px;background:rgba(34,197,94,0.12);color:#22C55E;border:1px solid rgba(34,197,94,0.25);">✓ Approved</span>
                    <?php else: ?>
                    <form method="POST" action="<?php echo e(route('admin.users.kyc.approve-doc', $selectedUser->id)); ?>" style="margin:0;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="doc_side" value="back">
                        <button type="submit" style="font-size:10px;font-weight:500;padding:2px 8px;border-radius:999px;background:transparent;border:1px solid rgba(34,197,94,0.4);color:#22C55E;cursor:pointer;font-family:inherit;">
                            Approve back
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php $ext = strtolower(pathinfo($backImage, PATHINFO_EXTENSION)); ?>
                <?php if(in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                <a href="<?php echo e(route('secure.kyc', ['user' => $selectedUser->id, 'side' => 'back'])); ?>" target="_blank"
                   style="display:block;border-radius:10px;overflow:hidden;border:2px solid <?php echo e($backApproved ? 'rgba(34,197,94,0.4)' : 'var(--border)'); ?>;">
                    <img src="<?php echo e(route('secure.kyc', ['user' => $selectedUser->id, 'side' => 'back'])); ?>"
                         style="width:100%;height:180px;object-fit:cover;display:block;" alt="Back">
                </a>
                <?php else: ?>
                <a href="<?php echo e(route('secure.kyc', ['user' => $selectedUser->id, 'side' => 'back'])); ?>" target="_blank"
                   style="display:flex;align-items:center;gap:8px;padding:12px 14px;border-radius:8px;border:1px solid var(--border);background:var(--surface-2);color:var(--fg);text-decoration:none;font-size:13px;">
                    <i data-lucide="file-text" style="width:15px;height:15px;color:var(--accent);"></i> View document
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        
        <div style="padding:24px;border:1px dashed var(--border);border-radius:10px;text-align:center;color:var(--fg-3);font-size:13px;margin-bottom:20px;">
            <i data-lucide="file-x" style="width:24px;height:24px;margin:0 auto 8px;display:block;opacity:0.3;"></i>
            No documents uploaded yet
        </div>
        <?php endif; ?>

        
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:24px;">
            <?php $__currentLoopData = [
                ['Full name',     $selectedUser->fullname,                                                      true],
                ['Email',         $selectedUser->email,                                                         true],
                ['Document type', $docType ? ucwords(str_replace('_',' ',$docType)) : '—',                     (bool)$docType],
                ['Account status', $selectedUser->status == 1 ? 'Active' : 'Suspended',                        $selectedUser->status == 1],
                ['Joined',        $selectedUser->created_at->format('M d, Y'),                                  true],
                ['Applied',       $selectedUser->updated_at->format('M d, Y H:i'),                             true],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $ok]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex;gap:14px;font-size:12.5px;align-items:center;">
                <span style="color:var(--fg-3);width:120px;flex-shrink:0;"><?php echo e($label); ?></span>
                <span style="color:<?php echo e($ok ? 'var(--fg)' : '#FCA5A5'); ?>;flex:1;"><?php echo e($value); ?></span>
                <i data-lucide="<?php echo e($ok ? 'check' : 'x'); ?>" style="width:13px;height:13px;color:<?php echo e($ok ? '#22C55E' : '#EF4444'); ?>;flex-shrink:0;"></i>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div x-data="{ rejectOpen: false, requestOpen: false }" style="display:flex;flex-direction:column;gap:10px;padding-top:16px;border-top:1px solid var(--border);">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <button @click="requestOpen = !requestOpen"
                        style="padding:8px 14px;border-radius:999px;border:1px solid var(--border-strong);background:transparent;color:var(--fg-2);font-size:12.5px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit;">
                    <i data-lucide="file-search" style="width:12px;height:12px;"></i> Request docs
                </button>
                <button @click="rejectOpen = !rejectOpen"
                        style="padding:8px 14px;border-radius:999px;border:1px solid #EF4444;background:transparent;color:#EF4444;font-size:12.5px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit;">
                    <i data-lucide="x" style="width:12px;height:12px;"></i> Reject & ban
                </button>
                <div style="flex:1;"></div>
                <a href="<?php echo e(route('admin.users.show', $selectedUser->id)); ?>"
                   style="font-size:12px;color:var(--fg-3);text-decoration:none;"
                   onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">
                    View profile →
                </a>
                <form method="POST" action="<?php echo e(route('admin.users.kyc.approve', $selectedUser->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                            onclick="return confirm('Approve full KYC for <?php echo e(addslashes($selectedUser->fullname)); ?>?')"
                            style="padding:9px 20px;border-radius:999px;background:#22C55E;border:none;color:white;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit;">
                        <i data-lucide="shield-check" style="width:13px;height:13px;"></i> Approve KYC
                    </button>
                </form>
            </div>

            
            <div x-show="requestOpen" x-cloak x-transition
                 style="padding:14px;border-radius:10px;background:rgba(96,165,250,0.06);border:1px solid rgba(96,165,250,0.2);">
                <form method="POST" action="<?php echo e(route('admin.users.kyc.request-docs', $selectedUser->id)); ?>"
                      style="display:flex;flex-direction:column;gap:10px;">
                    <?php echo csrf_field(); ?>
                    <div style="font-size:12px;font-weight:500;color:#60A5FA;">Request document re-upload</div>
                    <textarea name="request_message" rows="2"
                              placeholder="Message to user (optional — e.g. 'Your front ID is blurry, please resubmit')…"
                              style="flex:1;font-size:12.5px;resize:none;border-radius:8px;"></textarea>
                    <div style="display:flex;gap:8px;">
                        <button type="submit"
                                style="padding:8px 16px;border-radius:999px;background:#60A5FA;border:none;color:white;font-size:12.5px;font-weight:500;cursor:pointer;font-family:inherit;">
                            Send request
                        </button>
                        <button type="button" @click="requestOpen = false"
                                style="padding:8px 12px;border-radius:999px;border:1px solid var(--border);background:transparent;color:var(--fg-3);font-size:12.5px;cursor:pointer;font-family:inherit;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            
            <div x-show="rejectOpen" x-cloak x-transition
                 style="padding:14px;border-radius:10px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);">
                <form method="POST" action="<?php echo e(route('admin.users.kyc.reject', $selectedUser->id)); ?>"
                      style="display:flex;gap:10px;align-items:flex-start;">
                    <?php echo csrf_field(); ?>
                    <textarea name="rejection_reason" rows="2"
                              placeholder="Rejection reason (required)…"
                              style="flex:1;font-size:12.5px;resize:none;" required></textarea>
                    <button type="submit"
                            style="padding:9px 16px;border-radius:999px;background:#EF4444;border:none;color:white;font-size:13px;font-weight:500;cursor:pointer;flex-shrink:0;white-space:nowrap;font-family:inherit;">
                        Confirm reject
                    </button>
                </form>
            </div>
        </div>

    </div>
    <?php else: ?>
    <div class="jobstation-card" style="padding:64px;text-align:center;">
        <i data-lucide="shield-check" style="width:40px;height:40px;margin:0 auto 14px;display:block;color:#22C55E;opacity:0.35;"></i>
        <div style="font-size:14px;font-weight:500;color:var(--fg-2);margin-bottom:4px;">All clear!</div>
        <div style="font-size:13px;color:var(--fg-3);">No pending KYC requests.</div>
        <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-sm" style="display:inline-flex;margin-top:16px;">
            Back to Users
        </a>
    </div>
    <?php endif; ?>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/users/kyc.blade.php ENDPATH**/ ?>