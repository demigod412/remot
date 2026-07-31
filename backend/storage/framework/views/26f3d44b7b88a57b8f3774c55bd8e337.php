<?php $__env->startSection('title', 'Browse Work'); ?>
<?php $__env->startSection('page-title', 'Browse Work'); ?>

<?php $__env->startSection('content'); ?>

<div x-data="{ filtersOpen: true }">

    
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
        <div>
            <div style="font-size:13px; color:var(--fg-3);">
                <span class="mono" style="font-weight:600; color:var(--fg);"><?php echo e(number_format($works->total())); ?></span> tasks available
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <?php if(request()->hasAny(['search','category','skill','sort'])): ?>
            <a href="<?php echo e(route('user.browse.works')); ?>" style="font-size:12px; color:var(--fg-3); text-decoration:none; padding:6px 10px; border-radius:7px; background:var(--surface-2); border:1px solid var(--border);">
                Clear filters
            </a>
            <?php endif; ?>
            <button @click="filtersOpen = !filtersOpen"
                    style="display:flex; align-items:center; gap:6px; font-size:13px; font-weight:500; padding:7px 14px; border-radius:8px; border:1px solid var(--border); background:var(--surface); color:var(--fg-2); cursor:pointer; font-family:inherit; transition:all .14s;"
                    onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                    onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-2)'">
                <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M2 4h14M4 9h10M7 14h4"/></svg>
                Filters
                <?php if(request()->hasAny(['search','category','skill','sort'])): ?>
                <span style="width:6px; height:6px; border-radius:50%; background:var(--accent); flex-shrink:0;"></span>
                <?php endif; ?>
                <i data-lucide="chevron-down" style="width:13px; height:13px; transition:transform .2s;" :style="filtersOpen ? 'transform:rotate(180deg)' : ''"></i>
            </button>
        </div>
    </div>

    
    <div x-show="filtersOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         x-cloak
         style="margin-bottom:14px;">
        <form method="GET" action="<?php echo e(route('user.browse.works')); ?>"
              class="card"
              style="padding:18px; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">

            
            <div style="flex:1; min-width:180px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:6px;">Search</div>
                <div style="position:relative;">
                    <svg style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--fg-4);" width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="8" cy="8" r="5"/><path d="M13 13l3 3"/></svg>
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Task title or keyword…"
                           style="padding-left:32px;">
                </div>
            </div>

            
            <div style="min-width:160px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:6px;">Category</div>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($cat->id); ?>" <?php echo e(request('category') == $cat->id ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            
            <?php if($skills->isNotEmpty()): ?>
            <div style="min-width:140px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:6px;">Skill</div>
                <select name="skill">
                    <option value="">Any Skill</option>
                    <?php $__currentLoopData = $skills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($skill->id); ?>" <?php echo e(request('skill') == $skill->id ? 'selected' : ''); ?>><?php echo e($skill->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php endif; ?>

            
            <div style="min-width:150px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:6px;"><?php echo e(__('Sort by')); ?></div>
                <select name="sort">
                    <option value="latest"     <?php echo e(request('sort','latest') === 'latest'    ? 'selected' : ''); ?>><?php echo e(__('Latest')); ?></option>
                    <option value="coins_high" <?php echo e(request('sort') === 'coins_high'         ? 'selected' : ''); ?>><?php echo e(__('Highest reward')); ?></option>
                    <option value="coins_low"  <?php echo e(request('sort') === 'coins_low'          ? 'selected' : ''); ?>><?php echo e(__('Lowest reward')); ?></option>
                    <option value="slots"      <?php echo e(request('sort') === 'slots'              ? 'selected' : ''); ?>><?php echo e(__('Most spots')); ?></option>
                </select>
            </div>

            
            <button type="submit" class="btn btn-primary" style="padding:9px 20px; align-self:flex-end;">
                Apply filters
            </button>
        </form>
    </div>

    
    <?php $__empty_1 = true; $__currentLoopData = $works; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $work): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $applied    = $appliedIds->has($work->id);
        $bookmarked = $bookmarkedIds->has($work->id);
        $remaining  = $work->slots_remaining;
    ?>
    <div class="card work-browse-card" style="padding:14px 18px; display:flex; align-items:center; gap:16px; margin-bottom:8px; cursor:pointer; transition:box-shadow .14s, transform .14s;"
         onclick="window.location='<?php echo e(route('user.browse.works.show', $work->slug)); ?>'"
         onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">

        
        <div style="width:36px; height:36px; border-radius:9px; background:rgba(255,122,89,0.1); color:var(--urgent); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:16px;">
            ⚡
        </div>

        
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                <?php if($work->is_featured): ?>
                <span style="font-size:10px; font-weight:600; padding:2px 7px; border-radius:20px; background:rgba(245,158,11,0.1); color:#F59E0B; border:1px solid rgba(245,158,11,0.2);">★ Featured</span>
                <?php endif; ?>
                <div style="font-size:14px; font-weight:500; color:var(--fg); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:420px;"><?php echo e($work->title); ?></div>
            </div>
            <div style="display:flex; gap:10px; font-size:11.5px; color:var(--fg-3); align-items:center; flex-wrap:wrap;">
                <?php if($work->category): ?>
                <span><?php echo e($work->category->name); ?></span>
                <span style="color:var(--border-strong);">·</span>
                <?php endif; ?>
                <?php if($work->time_limit): ?>
                <span>~<?php echo e($work->time_limit); ?> min</span>
                <span style="color:var(--border-strong);">·</span>
                <?php endif; ?>
                <span style="color:<?php echo e($remaining < 5 ? 'var(--urgent)' : 'var(--fg-3)'); ?>;"><?php echo e($remaining); ?> spots left</span>
                <?php if($applied): ?>
                <span style="color:var(--border-strong);">·</span>
                <span style="color:#22C55E; font-weight:500;">✓ Applied</span>
                <?php endif; ?>
            </div>
        </div>

        
        <span class="mono" style="font-size:15px; font-weight:600; color:#E6C400; flex-shrink:0;"><?php echo e(coinSymbol()); ?><?php echo e(number_format($work->coins_per_worker)); ?></span>

        
        <div x-data="{ bm: <?php echo e($bookmarked ? 'true' : 'false'); ?> }" @click.stop>
            <button type="button"
                @click="bm = !bm; fetch('<?php echo e(route('user.works.bookmark', $work->id)); ?>', { method:'POST', headers:{'X-CSRF-TOKEN':'<?php echo e(csrf_token()); ?>','Accept':'application/json'} })"
                :title="bm ? '<?php echo e(__('Remove from saved')); ?>' : '<?php echo e(__('Save work')); ?>'"
                style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); cursor:pointer; transition:all .15s;"
                :style="bm ? 'border-color:var(--accent); background:rgba(47,84,235,0.08); color:var(--accent);' : 'color:var(--fg-3);'">
                <svg width="14" height="14" viewBox="0 0 24 24" :fill="bm ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            </button>
        </div>

        
        <?php if($applied): ?>
        <span style="font-size:12px; font-weight:500; padding:6px 12px; border-radius:7px; background:rgba(34,197,94,0.1); color:#22C55E; border:1px solid rgba(34,197,94,0.2); flex-shrink:0; white-space:nowrap;">✓ <?php echo e(__('Applied')); ?></span>
        <?php elseif($remaining <= 0): ?>
        <span style="font-size:12px; padding:6px 12px; border-radius:7px; background:var(--surface-2); color:var(--fg-4); border:1px solid var(--border); flex-shrink:0; cursor:not-allowed;"><?php echo e(__('Full')); ?></span>
        <?php else: ?>
        <span style="font-size:12px; font-weight:500; padding:6px 14px; border-radius:7px; background:var(--accent); color:white; flex-shrink:0; white-space:nowrap;"><?php echo e(__('Start')); ?> →</span>
        <?php endif; ?>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="card" style="padding:56px; text-align:center;">
        <div style="font-size:32px; margin-bottom:12px;">🔍</div>
        <div style="font-size:15px; font-weight:500; color:var(--fg); margin-bottom:6px;">No tasks found</div>
        <div style="font-size:13px; color:var(--fg-3); margin-bottom:20px;">Try adjusting your filters or search term.</div>
        <a href="<?php echo e(route('user.browse.works')); ?>" class="btn btn-primary" style="font-size:13px;">Clear filters</a>
    </div>
    <?php endif; ?>

    
    <?php if($works->hasPages()): ?>
    <?php
        $currentPage = $works->currentPage();
        $lastPage    = $works->lastPage();
        $window      = 2;
        $start       = max(1, $currentPage - $window);
        $end         = min($lastPage, $currentPage + $window);
    ?>
    <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:24px;flex-wrap:wrap;">

        
        <?php if($works->onFirstPage()): ?>
        <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--fg-4);cursor:not-allowed;opacity:.4;">
            <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14L6 9l5-5"/></svg>
        </span>
        <?php else: ?>
        <a href="<?php echo e($works->previousPageUrl()); ?>" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--fg-2);text-decoration:none;transition:all .14s;"
           onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
           onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-2)'">
            <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14L6 9l5-5"/></svg>
        </a>
        <?php endif; ?>

        
        <?php if($start > 1): ?>
        <a href="<?php echo e($works->url(1)); ?>" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--fg-2);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
           onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
           onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-2)'">1</a>
        <?php if($start > 2): ?>
        <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;color:var(--fg-4);font-size:13px;">···</span>
        <?php endif; ?>
        <?php endif; ?>

        
        <?php for($p = $start; $p <= $end; $p++): ?>
        <?php if($p === $currentPage): ?>
        <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--accent);background:var(--accent);color:#fff;font-size:13px;font-weight:600;"><?php echo e($p); ?></span>
        <?php else: ?>
        <a href="<?php echo e($works->url($p)); ?>" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--fg-2);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
           onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
           onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-2)'"><?php echo e($p); ?></a>
        <?php endif; ?>
        <?php endfor; ?>

        
        <?php if($end < $lastPage): ?>
        <?php if($end < $lastPage - 1): ?>
        <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;color:var(--fg-4);font-size:13px;">···</span>
        <?php endif; ?>
        <a href="<?php echo e($works->url($lastPage)); ?>" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--fg-2);font-size:13px;font-weight:500;text-decoration:none;transition:all .14s;"
           onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
           onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-2)'"><?php echo e($lastPage); ?></a>
        <?php endif; ?>

        
        <?php if($works->hasMorePages()): ?>
        <a href="<?php echo e($works->nextPageUrl()); ?>" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--fg-2);text-decoration:none;transition:all .14s;"
           onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
           onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-2)'">
            <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 14l5-5-5-5"/></svg>
        </a>
        <?php else: ?>
        <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--fg-4);cursor:not-allowed;opacity:.4;">
            <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 14l5-5-5-5"/></svg>
        </span>
        <?php endif; ?>

    </div>
    <?php endif; ?>

</div>

<style>[x-cloak] { display:none !important; }</style>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/works/browse.blade.php ENDPATH**/ ?>