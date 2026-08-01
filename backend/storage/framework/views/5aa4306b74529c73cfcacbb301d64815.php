<?php $__env->startSection('title', 'Create Work'); ?>
<?php $__env->startSection('page-title', 'Create Work'); ?>

<?php $__env->startSection('content'); ?>

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="<?php echo e(route('admin.works.index')); ?>" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Works</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">Create</span>
</div>

<div style="max-width:820px;">
<form method="POST" action="<?php echo e(route('admin.works.store')); ?>" enctype="multipart/form-data">
<?php echo csrf_field(); ?>

<div style="display:flex;flex-direction:column;gap:16px;">

    
    <div class="jobstation-card" style="padding:24px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 18px;">Basic Information</h3>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Title <span style="color:#EF4444;">*</span></label>
                <input type="text" name="title" value="<?php echo e(old('title')); ?>"
                       placeholder="Clear, descriptive work title…"
                       <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> style="border-color:#EF4444;" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> required>
                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px;color:#EF4444;margin-top:4px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="work-form-2col"
                 x-data="{ catId: '<?php echo e(old('category_id')); ?>' }">
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Category <span style="color:#EF4444;">*</span></label>
                    <select name="category_id" x-model="catId"
                            @change="$refs.subcat.innerHTML = '<option value=\'\'>&hellip;</option>'; fetch('/admin/categories/'+catId+'/subcategories').then(r=>r.json()).then(d=>{ $refs.subcat.innerHTML = '<option value=\'\'>None</option>' + d.map(s=>`<option value='${s.id}'>${s.name}</option>`).join('') })"
                            <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> style="border-color:#EF4444;" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> required>
                        <option value="">Select category…</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($cat->id); ?>" <?php if(old('category_id') == $cat->id): echo 'selected'; endif; ?>><?php echo e($cat->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px;color:#EF4444;margin-top:4px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Subcategory</label>
                    <select name="subcategory_id" x-ref="subcat">
                        <option value="">None</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Description <span style="color:#EF4444;">*</span></label>
                <textarea name="description" rows="6"
                          placeholder="Detailed instructions for workers…"
                          style="resize:vertical;width:100%;font-size:13.5px;line-height:1.6;"
                          <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> style="border-color:#EF4444;" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                          required><?php echo e(old('description')); ?></textarea>
                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px;color:#EF4444;margin-top:4px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Cover Image</label>
                <input type="file" name="cover_image" accept="image/*" style="font-size:13px;">
                <?php $__errorArgs = ['cover_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px;color:#EF4444;margin-top:4px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
    </div>

    
    <div class="jobstation-card" style="padding:24px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 18px;">Reward & Capacity</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;" class="work-form-3col">
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Worker Slots <span style="color:#EF4444;">*</span></label>
                <input type="number" name="worker_slots" value="<?php echo e(old('worker_slots', 10)); ?>"
                       min="1" <?php $__errorArgs = ['worker_slots'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> style="border-color:#EF4444;" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> required>
                <?php $__errorArgs = ['worker_slots'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px;color:#EF4444;margin-top:4px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Coins / Worker <span style="color:#EF4444;">*</span></label>
                <input type="number" name="coins_per_worker" value="<?php echo e(old('coins_per_worker')); ?>"
                       min="0.01" step="0.01" placeholder="0.00"
                       style="font-family:ui-monospace,monospace;"
                       <?php $__errorArgs = ['coins_per_worker'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> style="border-color:#EF4444;" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> required>
                <?php $__errorArgs = ['coins_per_worker'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px;color:#EF4444;margin-top:4px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Est. Minutes</label>
                <input type="number" name="avg_minutes" value="<?php echo e(old('avg_minutes')); ?>"
                       min="1" placeholder="e.g. 10">
            </div>
        </div>
    </div>

    
    <div class="jobstation-card" style="padding:24px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 18px;">Publish Settings</h3>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Work Status</label>
                <select name="work_status" style="width:260px;">
                    <option value="1" <?php if(old('work_status','1')=='1'): echo 'selected'; endif; ?>>Active (live immediately)</option>
                    <option value="0" <?php if(old('work_status')=='0'): echo 'selected'; endif; ?>>Holding (not visible)</option>
                </select>
                <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Admin-created works are automatically approved.</div>
            </div>
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                <input type="hidden" name="allow_multiple_submissions" value="0">
                <input type="checkbox" id="allow_multiple_create" name="allow_multiple_submissions" value="1"
                       style="width:15px;height:15px;accent-color:var(--accent);margin-top:2px;flex-shrink:0;"
                       <?php if(old('allow_multiple_submissions')): echo 'checked'; endif; ?>>
                <div>
                    <div style="font-size:13px;color:var(--fg);font-weight:500;">Allow users to do this work more than once</div>
                    <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;">Workers can re-apply after each approved submission to earn more coins.</div>
                </div>
            </label>
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                <input type="hidden" name="requires_kyc" value="0">
                <input type="checkbox" id="requires_kyc_create" name="requires_kyc" value="1"
                       style="width:15px;height:15px;accent-color:#F59E0B;margin-top:2px;flex-shrink:0;"
                       <?php if(old('requires_kyc')): echo 'checked'; endif; ?>>
                <div>
                    <div style="font-size:13px;color:var(--fg);font-weight:500;">Requires KYC verification</div>
                    <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;">Only users who have completed identity verification can apply.</div>
                </div>
            </label>
        </div>
    </div>

    <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Create Work
        </button>
        <a href="<?php echo e(route('admin.works.index')); ?>" class="btn" style="padding:9px 18px;">Cancel</a>
    </div>

</div>
</form>
</div>

<style>
@media (max-width: 640px) {
    .work-form-2col, .work-form-3col { grid-template-columns: 1fr !important; }
}
</style>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/works/create.blade.php ENDPATH**/ ?>