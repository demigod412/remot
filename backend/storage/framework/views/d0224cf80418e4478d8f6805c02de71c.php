
<?php [$avg, $cnt] = array_values($user->publicRatingData()); ?>
<?php if($avg): ?>
<span style="display:inline-flex; align-items:center; gap:3px; font-size:11.5px; color:var(--fg-3);">
    <span style="color:#F5D547;">★</span>
    <span style="font-weight:600; color:var(--fg-2);"><?php echo e(number_format($avg, 1)); ?></span>
    <span style="color:var(--fg-4);">(<?php echo e($cnt); ?>)</span>
</span>
<?php endif; ?>
<?php /**PATH /var/www/resources/views/user/partials/rating-stars.blade.php ENDPATH**/ ?>