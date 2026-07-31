<?php
    $cell     = 'display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 11px;border-radius:8px;font-size:13px;font-weight:500;line-height:1;text-decoration:none;border:1px solid var(--border,#e5e7eb);transition:background .12s,border-color .12s;';
    $link     = $cell.'color:var(--fg,#1f2937);background:var(--surface,#fff);';
    $current  = $cell.'color:#fff;background:var(--primary,#6C47FF);border-color:var(--primary,#6C47FF);';
    $disabled = $cell.'color:var(--fg-4,#9ca3af);background:transparent;opacity:.55;cursor:default;';
    $dots     = 'display:inline-flex;align-items:center;padding:0 4px;color:var(--fg-4,#9ca3af);font-size:13px;';
?>

<?php if($paginator->hasPages()): ?>
    <nav role="navigation" aria-label="<?php echo e(__('Pagination Navigation')); ?>"
         style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="font-size:12.5px;color:var(--fg-3,#6b7280);">
            <?php echo e(__('Showing')); ?>

            <span style="font-weight:600;color:var(--fg,#1f2937);"><?php echo e($paginator->firstItem() ?? 0); ?></span>–<span style="font-weight:600;color:var(--fg,#1f2937);"><?php echo e($paginator->lastItem() ?? 0); ?></span>
            <?php echo e(__('of')); ?> <span style="font-weight:600;color:var(--fg,#1f2937);"><?php echo e($paginator->total()); ?></span>
        </div>

        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
            
            <?php if($paginator->onFirstPage()): ?>
                <span aria-disabled="true" style="<?php echo e($disabled); ?>">&lsaquo;</span>
            <?php else: ?>
                <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev" aria-label="<?php echo e(__('Previous')); ?>" style="<?php echo e($link); ?>">&lsaquo;</a>
            <?php endif; ?>

            
            <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(is_string($element)): ?>
                    <span style="<?php echo e($dots); ?>"><?php echo e($element); ?></span>
                <?php endif; ?>
                <?php if(is_array($element)): ?>
                    <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($page == $paginator->currentPage()): ?>
                            <span aria-current="page" style="<?php echo e($current); ?>"><?php echo e($page); ?></span>
                        <?php else: ?>
                            <a href="<?php echo e($url); ?>" style="<?php echo e($link); ?>"><?php echo e($page); ?></a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            
            <?php if($paginator->hasMorePages()): ?>
                <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next" aria-label="<?php echo e(__('Next')); ?>" style="<?php echo e($link); ?>">&rsaquo;</a>
            <?php else: ?>
                <span aria-disabled="true" style="<?php echo e($disabled); ?>">&rsaquo;</span>
            <?php endif; ?>
        </div>
    </nav>
<?php endif; ?>
<?php /**PATH /var/www/resources/views/pagination/jobstation.blade.php ENDPATH**/ ?>