<?php $__env->startSection('title', 'Admin Sign In'); ?>
<?php $__env->startSection('heading', 'Admin console access.'); ?>
<?php $__env->startSection('subheading', 'Staff only. All sign-in attempts are logged and attributed to your identity.'); ?>

<?php $__env->startSection('content'); ?>
<form method="POST" action="<?php echo e(route('admin.login.submit')); ?>">
    <?php echo csrf_field(); ?>

    <div class="adm-field">
        <label class="adm-label">Staff email</label>
        <input type="email" name="email" value="<?php echo e(old('email')); ?>"
               class="adm-input <?php echo e($errors->has('email') ? 'error' : ''); ?>"
               placeholder="you@example.com" required autofocus autocomplete="email">
        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="adm-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="adm-field" x-data="{ show: false }">
        <label class="adm-label">Password</label>
        <div style="position:relative;">
            <input :type="show ? 'text' : 'password'" name="password"
                   class="adm-input <?php echo e($errors->has('password') ? 'error' : ''); ?>"
                   placeholder="••••••••••••" required autocomplete="current-password"
                   style="padding-right:44px;">
            <button type="button" @click="show = !show"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.3);display:flex;align-items:center;padding:0;"
                    onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
                <template x-if="!show"><i data-lucide="eye" style="width:15px;height:15px;"></i></template>
                <template x-if="show"><i data-lucide="eye-off" style="width:15px;height:15px;"></i></template>
            </button>
        </div>
        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="adm-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <label style="display:flex;align-items:center;gap:10px;font-size:13px;color:rgba(255,255,255,0.45);margin-bottom:28px;cursor:pointer;">
        <input type="checkbox" name="remember"
               style="width:15px;height:15px;accent-color:#2f54eb;cursor:pointer;">
        Keep me signed in
    </label>

    <button type="submit" class="adm-btn">
        <i data-lucide="shield" style="width:14px;height:14px;"></i>
        Sign in to admin
    </button>
</form>


<div style="margin-top:24px;padding:14px;background:rgba(255,255,255,0.04);border-radius:10px;font-size:11.5px;color:rgba(255,255,255,0.4);">
    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
        <span>Access level</span>
        <span style="color:rgba(255,255,255,0.6);">Super-admin</span>
    </div>
    <div style="display:flex;justify-content:space-between;">
        <span>Session</span>
        <span style="color:rgba(255,255,255,0.6);">Encrypted · audited</span>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/admin/auth/login.blade.php ENDPATH**/ ?>