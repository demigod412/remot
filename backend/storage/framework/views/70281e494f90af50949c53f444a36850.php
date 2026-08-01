<?php $__env->startSection('title', 'Categories'); ?>
<?php $__env->startSection('page-title', 'Work Categories'); ?>

<?php $__env->startSection('content'); ?>


<?php if($errors->any()): ?>
<div style="padding:12px 16px;border-radius:10px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);margin-bottom:16px;font-size:13px;color:var(--fg-2);">
    <strong style="color:#EF4444;">Could not save.</strong>
    <ul style="margin:6px 0 0;padding-left:20px;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($error); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div style="font-size:13px;color:var(--fg-3);"><?php echo e($categories->count()); ?> categories</div>
    <button x-data @click="$dispatch('open-add-category')" class="btn-primary" style="padding:8px 16px;font-size:13px;display:flex;align-items:center;gap:6px;">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Category
    </button>
</div>

<div class="jobstation-card" style="overflow:hidden;margin-bottom:24px;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Category</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Fee</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Commission</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Subs</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Works</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Status</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            
            <tr style="border-bottom:1px solid var(--border);"
                x-data="{ editOpen: <?php echo e((old('form_source') === 'edit' && (int) old('cat_id') === $cat->id) ? 'true' : 'false'); ?> }">
                <td style="padding:13px 20px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <?php if($cat->icon): ?>
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(47,84,235,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="<?php echo e($cat->icon); ?>" style="width:16px;height:16px;color:var(--accent);"></i>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span style="font-weight:500;color:var(--fg);"><?php echo e($cat->name); ?></span>
                            <div style="font-size:11.5px;color:var(--fg-3);">
                                <?php echo e($cat->eligible_user_type_label); ?>

                                <?php if($cat->hasResultSchema()): ?>
                                    <span style="color:#22C55E;">· schema set</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                
                <td style="padding:13px 20px;text-align:right;font-family:ui-monospace,monospace;color:<?php echo e((float) $cat->application_cost > 0 ? 'var(--fg-2)' : '#F59E0B'); ?>;">
                    <?php echo e(formatCoins($cat->application_cost)); ?>

                </td>
                <td style="padding:13px 20px;text-align:right;font-family:ui-monospace,monospace;color:<?php echo e((float) $cat->commission_percent > 0 ? 'var(--fg-2)' : '#F59E0B'); ?>;">
                    <?php echo e(rtrim(rtrim(number_format($cat->commission_percent, 2), '0'), '.')); ?>%
                </td>
                <td style="padding:13px 20px;text-align:center;color:var(--fg-2);"><?php echo e($cat->subcategories_count); ?></td>
                <td style="padding:13px 20px;text-align:center;color:var(--fg-2);"><?php echo e($cat->works_count); ?></td>
                <td style="padding:13px 20px;text-align:center;">
                    <?php if($cat->status): ?>
                        <span class="badge-success" style="font-size:11px;">Active</span>
                    <?php else: ?>
                        <span class="badge-default" style="font-size:11px;">Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="padding:13px 20px;text-align:right;">
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                        <button @click="editOpen = !editOpen"
                                style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                title="Edit"
                                onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                            <i data-lucide="edit-3" style="width:15px;height:15px;"></i>
                        </button>
                        <form method="POST" action="<?php echo e(route('admin.categories.toggle', $cat->id)); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit"
                                    style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                    title="<?php echo e($cat->status ? 'Deactivate' : 'Activate'); ?>"
                                    onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                                <i data-lucide="<?php echo e($cat->status ? 'eye-off' : 'eye'); ?>" style="width:15px;height:15px;"></i>
                            </button>
                        </form>
                        <?php if(!$cat->works_count): ?>
                        <form method="POST" action="<?php echo e(route('admin.categories.delete', $cat->id)); ?>"
                              onsubmit="return confirm('Delete <?php echo e(addslashes($cat->name)); ?>?')">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button type="submit"
                                    style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;"
                                    title="Delete"
                                    onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-4)'">
                                <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>

            
            <tr x-show="editOpen" x-cloak x-transition style="background:var(--surface-2);">
                <td colspan="7" style="padding:16px 20px;">
                    
                    <div style="display:flex;flex-direction:column;gap:24px;">
                        
                        <div>
                            <div style="font-size:11px;color:var(--fg-3);font-weight:600;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:10px;">Edit Category</div>
                            <form method="POST" action="<?php echo e(route('admin.categories.update', $cat->id)); ?>">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                
                                <input type="hidden" name="form_source" value="edit">
                                <input type="hidden" name="cat_id" value="<?php echo e($cat->id); ?>">

                                <div style="display:flex;gap:8px;margin-bottom:14px;">
                                    <input type="text" name="name" value="<?php echo e($cat->name); ?>" placeholder="Category name" style="flex:1;font-size:13px;" required>
                                    <input type="text" name="icon" value="<?php echo e($cat->icon); ?>" placeholder="lucide icon" style="width:120px;font-size:13px;font-family:ui-monospace,monospace;">
                                </div>

                                <?php echo $__env->make('admin.partials.category-marketplace-fields', ['cat' => $cat], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                                <button type="submit" class="btn-primary" style="padding:8px 14px;font-size:13px;">Save</button>
                            </form>
                        </div>

                        
                        <div>
                            <div style="font-size:11px;color:var(--fg-3);font-weight:600;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:10px;">Subcategories</div>
                            <div style="max-height:120px;overflow-y:auto;margin-bottom:10px;display:flex;flex-direction:column;gap:4px;">
                                <?php $__empty_2 = true; $__currentLoopData = $cat->subcategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                <div style="display:flex;align-items:center;gap:8px;"
                                     onmouseover="this.querySelector('.sub-del').style.opacity='1'"
                                     onmouseout="this.querySelector('.sub-del').style.opacity='0'">
                                    <span style="font-size:13px;color:var(--fg-2);flex:1;"><?php echo e($sub->name); ?></span>
                                    <form method="POST" action="<?php echo e(route('admin.categories.subcategories.delete', $sub->id)); ?>"
                                          onsubmit="return confirm('Delete subcategory?')">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="sub-del"
                                                style="padding:3px;border-radius:5px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;opacity:0;transition:opacity .14s;"
                                                onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                                                onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                                            <i data-lucide="x" style="width:13px;height:13px;"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                <div style="font-size:12px;color:var(--fg-4);">No subcategories yet.</div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="<?php echo e(route('admin.categories.subcategories.store', $cat->id)); ?>" style="display:flex;gap:8px;">
                                <?php echo csrf_field(); ?>
                                <input type="text" name="name" placeholder="New subcategory…" style="flex:1;font-size:13px;" required>
                                <button type="submit" class="btn" style="padding:8px 12px;font-size:13px;flex-shrink:0;">Add</button>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" style="padding:56px;text-align:center;color:var(--fg-3);">
                    <i data-lucide="tag" style="width:32px;height:32px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
                    No categories yet.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<div x-data="{ open: false }" @open-add-category.window="open = true">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5);overflow-y:auto;" x-transition>
        <div @click.outside="open = false"
             class="jobstation-card" style="width:100%;max-width:600px;padding:24px;max-height:90vh;overflow-y:auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h3 style="font-weight:600;font-size:15px;color:var(--fg);">Add Category</h3>
                <button @click="open = false"
                        style="padding:5px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);"
                        onmouseover="this.style.background='var(--surface-2)'"
                        onmouseout="this.style.background='transparent'">
                    <i data-lucide="x" style="width:16px;height:16px;display:block;"></i>
                </button>
            </div>
            <form method="POST" action="<?php echo e(route('admin.categories.store')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="form_source" value="create">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">Name <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="name" value="<?php echo e(old('name')); ?>" placeholder="e.g. Social Media" required>
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div style="font-size:12px;color:#EF4444;margin-top:4px;"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;color:var(--fg-2);margin-bottom:6px;">
                            Icon <span style="font-size:12px;color:var(--fg-3);">(Lucide icon name)</span>
                        </label>
                        <input type="text" name="icon" value="<?php echo e(old('icon')); ?>" placeholder="share-2, globe, star…" style="font-family:ui-monospace,monospace;">
                    </div>

                    
                    <?php echo $__env->make('admin.partials.category-marketplace-fields', ['cat' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                    <div style="display:flex;gap:10px;padding-top:4px;">
                        <button type="submit" class="btn-primary" style="flex:1;padding:9px;font-size:13px;">Create Category</button>
                        <button type="button" @click="open = false" class="btn" style="padding:9px 16px;font-size:13px;">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<?php if($errors->any() && old('form_source') === 'create'): ?>
<script>document.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-add-category')));</script>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/categories/index.blade.php ENDPATH**/ ?>