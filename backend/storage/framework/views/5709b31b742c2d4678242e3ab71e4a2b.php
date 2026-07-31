<?php $__env->startSection('title', 'Browse Jobs'); ?>
<?php $__env->startSection('page-title', 'Find Jobs'); ?>

<?php $__env->startSection('content'); ?>


<form method="GET" action="<?php echo e(route('user.jobs.browse')); ?>"
      class="card" style="padding:16px 18px; margin-bottom:18px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
    <div style="flex:1; min-width:180px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Search</label>
        <div style="position:relative;">
            <i data-lucide="search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); width:14px; height:14px; color:var(--fg-4); pointer-events:none;"></i>
            <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Job title, keyword…"
                   style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 12px 8px 32px; color:var(--fg); font-size:13px; outline:none;">
        </div>
    </div>
    <div style="width:145px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Category</label>
        <select name="category" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none;">
            <option value="">All categories</option>
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($cat->id); ?>" <?php echo e(request('category') == $cat->id ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <div style="width:125px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Work type</label>
        <select name="location_type" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none;">
            <option value="">Any</option>
            <option value="1" <?php echo e(request('location_type') == '1' ? 'selected' : ''); ?>>Remote</option>
            <option value="2" <?php echo e(request('location_type') == '2' ? 'selected' : ''); ?>>On-site</option>
            <option value="3" <?php echo e(request('location_type') == '3' ? 'selected' : ''); ?>>Hybrid</option>
        </select>
    </div>
    <div style="width:130px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Employment</label>
        <select name="employment_type" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none;">
            <option value="">Any</option>
            <?php $__currentLoopData = ['full_time'=>'Full-time','part_time'=>'Part-time','contract'=>'Contract','freelance'=>'Freelance']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($val); ?>" <?php echo e(request('employment_type') == $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <?php if($skills->isNotEmpty()): ?>
    <div style="width:115px;">
        <label style="font-size:11px; color:var(--fg-4); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;">Skill</label>
        <select name="skill" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none;">
            <option value="">Any skill</option>
            <?php $__currentLoopData = $skills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($skill->id); ?>" <?php echo e(request('skill') == $skill->id ? 'selected' : ''); ?>><?php echo e($skill->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <?php endif; ?>
    <div style="display:flex; gap:8px;">
        <button type="submit" class="btn btn-primary" style="font-size:13px; padding:8px 18px;">Filter</button>
        <?php if(request()->hasAny(['search','category','location_type','employment_type','skill'])): ?>
        <a href="<?php echo e(route('user.jobs.browse')); ?>" class="btn" style="font-size:13px; padding:8px 14px;">Clear</a>
        <?php endif; ?>
    </div>
</form>


<div style="font-size:12.5px; color:var(--fg-3); margin-bottom:14px;">
    <span class="mono" style="color:var(--fg); font-weight:600;"><?php echo e($listings->total()); ?></span>
    job<?php echo e($listings->total() !== 1 ? 's' : ''); ?> found
</div>


<div style="display:flex; flex-direction:column; gap:10px;">
<?php $__empty_1 = true; $__currentLoopData = $listings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<div class="card jobs-card" style="padding:18px; transition:border-color .15s;">
    <div style="display:flex; align-items:flex-start; gap:14px;">
        
        <?php if($listing->cover_image): ?>
        <img src="<?php echo e(fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image)); ?>"
             alt="<?php echo e($listing->title); ?>" style="width:52px; height:52px; border-radius:10px; object-fit:cover; flex-shrink:0;">
        <?php else: ?>
        <div style="width:52px; height:52px; border-radius:10px; background:rgba(47,84,235,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i data-lucide="briefcase" style="width:22px; height:22px; color:var(--accent); opacity:.5;"></i>
        </div>
        <?php endif; ?>

        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:5px; flex-wrap:wrap;">
                <h3 style="font-size:14.5px; font-weight:600; color:var(--fg); margin:0;"><?php echo e($listing->title); ?></h3>
                <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                    <?php if($appliedIds->has($listing->id)): ?>
                    <span style="font-size:11px; padding:2px 8px; border-radius:999px; background:rgba(34,197,94,0.1); color:#22C55E; border:1px solid rgba(34,197,94,0.2); font-weight:500;">Applied</span>
                    <?php endif; ?>
                    
                    <div x-data="{ bm: <?php echo e($bookmarkedIds->has($listing->id) ? 'true' : 'false'); ?> }">
                        <button type="button" @click="
                            bm = !bm;
                            fetch('<?php echo e(route('user.jobs.bookmark', $listing->id)); ?>', {
                                method:'POST',
                                headers:{'X-CSRF-TOKEN':'<?php echo e(csrf_token()); ?>','Accept':'application/json'}
                            });"
                            :title="bm ? '<?php echo e(__('Remove from saved')); ?>' : '<?php echo e(__('Save job')); ?>'"
                            style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); cursor:pointer; transition:all .15s;"
                            :style="bm ? 'border-color:var(--accent); background:rgba(47,84,235,0.08); color:var(--accent);' : 'color:var(--fg-3);'">
                            <svg width="14" height="14" viewBox="0 0 24 24" :fill="bm ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        </button>
                    </div>
                    <a href="<?php echo e(route('user.jobs.show', $listing->id)); ?>" class="btn" style="font-size:12px; padding:5px 14px;">View</a>
                </div>
            </div>

            <div style="font-size:12px; color:var(--fg-3); margin-bottom:10px; display:flex; align-items:center; gap:5px;">
                <?php echo e($listing->employer->fullname ?? $listing->employer->username ?? 'Employer'); ?>

                <?php if($listing->employer->kyc_status === 1): ?>
                <i data-lucide="shield-check" style="width:12px; height:12px; color:#22C55E;"></i>
                <?php endif; ?>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px;">
                <?php if($listing->category): ?>
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3); display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="tag" style="width:10px; height:10px;"></i> <?php echo e($listing->category->name); ?>

                </span>
                <?php endif; ?>
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3); display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="map-pin" style="width:10px; height:10px;"></i> <?php echo e($listing->locationTypeLabel); ?><?php echo e($listing->location ? ' · '.$listing->location : ''); ?>

                </span>
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3); display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="clock" style="width:10px; height:10px;"></i> <?php echo e($listing->employmentTypeLabel); ?>

                </span>
                <?php if($listing->salaryRange): ?>
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:rgba(47,84,235,0.08); color:var(--accent); display:inline-flex; align-items:center; gap:4px; font-weight:500;">
                    <i data-lucide="dollar-sign" style="width:10px; height:10px;"></i> <?php echo e($listing->salaryRange); ?>

                </span>
                <?php endif; ?>
                <?php if($listing->closes_at): ?>
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3); display:inline-flex; align-items:center; gap:4px;">
                    <i data-lucide="calendar" style="width:10px; height:10px;"></i> Closes <?php echo e($listing->closes_at->format('M d')); ?>

                </span>
                <?php endif; ?>
            </div>

            <p style="font-size:12.5px; color:var(--fg-3); margin:0; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                <?php echo e(Str::limit(strip_tags($listing->description), 160)); ?>

            </p>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="card" style="padding:60px 24px; text-align:center;">
    <div style="font-size:32px; margin-bottom:12px;">🔍</div>
    <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:8px;">No jobs found</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;">Try adjusting your filters or check back later for new listings.</p>
    <?php if(request()->hasAny(['search','category','location_type','employment_type','skill'])): ?>
    <a href="<?php echo e(route('user.jobs.browse')); ?>" class="btn btn-primary" style="font-size:13px;">Clear filters</a>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>

<div style="margin-top:20px;"><?php echo e($listings->withQueryString()->links()); ?></div>

<style>
.jobs-card:hover { border-color: rgba(47,84,235,0.25) !important; }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/jobs/browse.blade.php ENDPATH**/ ?>