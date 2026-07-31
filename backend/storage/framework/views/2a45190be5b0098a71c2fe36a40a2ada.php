<?php $__env->startSection('title', 'Sign In'); ?>
<?php $__env->startSection('heading', 'Welcome back.'); ?>
<?php $__env->startSection('subheading', 'Sign in to pick up where you left off — running tasks, active applications, and your wallet.'); ?>

<?php $__env->startSection('top-right'); ?>
<div style="font-size:13px;color:var(--fg-3);">
    New to <?php echo e(gs()->site_name ?? 'Job Station'); ?>?
    <a href="<?php echo e(route('user.register')); ?>" style="color:#2f54eb;font-weight:500;text-decoration:none;">Create account →</a>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $gs = gs(); ?>


<?php if(($gs->socialite_credentials['google']['client_id'] ?? null) || ($gs->socialite_credentials['facebook']['client_id'] ?? null)): ?>
<div style="display:grid;grid-template-columns:repeat(<?php echo e(($gs->socialite_credentials['google']['client_id'] ?? null) && ($gs->socialite_credentials['facebook']['client_id'] ?? null) ? 2 : 1); ?>,1fr);gap:8px;margin-bottom:4px;">
    <?php if($gs->socialite_credentials['google']['client_id'] ?? null): ?>
    <a href="<?php echo e(route('user.social.redirect', 'google')); ?>" class="auth-social-btn">
        <svg width="15" height="15" viewBox="0 0 24 24"><path fill="#EA4335" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Google
    </a>
    <?php endif; ?>
    <?php if($gs->socialite_credentials['facebook']['client_id'] ?? null): ?>
    <a href="<?php echo e(route('user.social.redirect', 'facebook')); ?>" class="auth-social-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        Facebook
    </a>
    <?php endif; ?>
</div>
<div class="auth-divider">or with email</div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('user.login.submit')); ?>">
    <?php echo csrf_field(); ?>

    <?php if($errors->any()): ?>
    <div class="auth-flash-error" style="margin-bottom:18px;"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <div class="auth-field">
        <label class="auth-label">Email address</label>
        <input type="email" name="email" value="<?php echo e(old('email')); ?>"
               class="auth-input <?php echo e($errors->has('email') ? 'error' : ''); ?>"
               placeholder="you@company.com" required autofocus autocomplete="email">
        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="auth-field" x-data="{ show: false }">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <label class="auth-label" style="margin:0;">Password</label>
            <a href="<?php echo e(route('user.forgot-password')); ?>" style="font-size:12px;color:#2f54eb;text-decoration:none;">Forgot?</a>
        </div>
        <div style="position:relative;">
            <input :type="show ? 'text' : 'password'" name="password"
                   class="auth-input <?php echo e($errors->has('password') ? 'error' : ''); ?>"
                   placeholder="••••••••" required autocomplete="current-password"
                   style="padding-right:44px;">
            <button type="button" @click="show = !show"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;padding:4px;">
                <svg x-show="!show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg x-show="show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <label style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--fg-3);margin-bottom:24px;cursor:pointer;">
        <input type="checkbox" name="remember" style="width:15px;height:15px;accent-color:#2f54eb;cursor:pointer;">
        Keep me signed in for 30 days
    </label>

    <?php echo recaptchaWidget(); ?>

    <?php $__errorArgs = ['captcha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="auth-error" style="margin-bottom:12px;"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

    <button type="submit" class="auth-btn-primary">
        Sign in
        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
    </button>
</form>

<div style="margin-top:28px;padding:14px;background:var(--surface-2);border-radius:10px;display:flex;gap:12px;align-items:center;font-size:12px;color:var(--fg-3);">
    <i data-lucide="shield" style="width:18px;height:18px;color:#2f54eb;flex-shrink:0;"></i>
    <div>
        <div style="font-weight:500;color:var(--fg-2);margin-bottom:2px;">Protected sign-in</div>
        <div>We detect unusual activity. You may be asked for a 6-digit code.</div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('footer-links'); ?>
<div style="text-align:center;margin-top:24px;font-size:13px;color:var(--fg-3);">
    Don't have an account?
    <a href="<?php echo e(route('user.register')); ?>" style="color:#2f54eb;font-weight:500;text-decoration:none;">Create one free</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/user/auth/login.blade.php ENDPATH**/ ?>