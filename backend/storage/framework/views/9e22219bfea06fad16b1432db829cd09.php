<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Account'); ?> — <?php echo e(gs()->site_name ?? 'Job Station'); ?></title>
    <script>(function(){if(localStorage.getItem('jobstation-theme')==='dark')document.documentElement.classList.add('dark');})()</script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            background: var(--bg);
            color: var(--fg);
            font-family: 'Poppins', system-ui, sans-serif;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── LEFT: form panel ── */
        .auth-form-panel {
            display: flex;
            flex-direction: column;
            padding: 56px;
            background: var(--bg);
        }
        .auth-form-inner {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }

        /* ── RIGHT: brand panel ── */
        .auth-brand-panel {
            position: relative;
            overflow: hidden;
            background: linear-gradient(160deg, #2f54eb 0%, #1e6b2a 50%, #0B0B0F 100%);
            color: white;
            padding: 56px;
            display: flex;
            flex-direction: column;
        }
        .auth-brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(-45deg, rgba(255,255,255,0.04) 0 2px, transparent 2px 18px);
            pointer-events: none;
        }

        /* ── Inputs ── */
        .auth-field { margin-bottom: 18px; }
        .auth-label {
            display: block;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--fg-2);
            margin-bottom: 8px;
        }
        .auth-input {
            width: 100%;
            padding: 11px 14px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--fg);
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .auth-input:focus {
            border-color: #2f54eb;
            box-shadow: 0 0 0 3px rgba(47,84,235,0.15);
        }
        .auth-input::placeholder { color: var(--fg-4); }
        .auth-input.error { border-color: rgba(239,68,68,0.6); }
        .auth-error { font-size: 11.5px; color: #EF4444; margin-top: 6px; }

        .auth-select {
            width: 100%;
            padding: 11px 14px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--fg);
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            outline: none;
            cursor: pointer;
        }
        .auth-select option { background: var(--surface-2); }

        /* ── Primary button ── */
        .auth-btn-primary {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: #2f54eb;
            border: none;
            color: #000;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity .15s;
            box-shadow: 0 6px 20px -6px rgba(47,84,235,0.5);
        }
        .auth-btn-primary:hover { opacity: 0.88; }

        /* ── Social button ── */
        .auth-social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--surface-2);
            color: var(--fg-2);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background .14s;
            font-family: 'Poppins', sans-serif;
        }
        .auth-social-btn:hover { background: var(--surface-3); }

        /* ── Divider ── */
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            font-size: 11.5px;
            color: var(--fg-4);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .auth-divider::before, .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── Flash ── */
        .auth-flash-error {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 9px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #EF4444;
            font-size: 13px;
        }
        .auth-flash-success {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 9px;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.25);
            color: #22C55E;
            font-size: 13px;
        }

        /* ── Floating job cards (brand panel) ── */
        .auth-card {
            position: absolute;
            width: 230px;
            padding: 14px;
            background: rgba(255,255,255,0.96);
            border-radius: 12px;
            box-shadow: 0 20px 50px -15px rgba(0,0,0,0.5);
            color: #111;
        }

        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
            .auth-brand-panel { display: none; }
            .auth-form-panel { padding: 40px 28px; }
        }
    </style>
</head>
<body>


<div class="auth-form-panel">
    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:auto;padding-bottom:20px;">
        <a href="<?php echo e(route('home')); ?>" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="#2f54eb" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="#2f54eb" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="#2f54eb" stroke-width="2"/>
            </svg>
            <span style="font-size:15px;font-weight:600;color:var(--fg);letter-spacing:-0.3px;"><?php echo e(gs()->site_name ?? 'Job Station'); ?></span>
        </a>
        <?php echo $__env->yieldContent('top-right'); ?>
    </div>

    <div class="auth-form-inner">
        
        <?php if(session('error')): ?>
        <div class="auth-flash-error"><?php echo e(session('error')); ?></div>
        <?php endif; ?>
        <?php if(session('success')): ?>
        <div class="auth-flash-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        
        <h1 style="font-size:32px;font-weight:600;letter-spacing:-1px;margin:0 0 10px;color:var(--fg);"><?php echo $__env->yieldContent('heading', 'Welcome back.'); ?></h1>
        <p style="font-size:14.5px;color:var(--fg-3);line-height:1.55;margin:0 0 32px;"><?php echo $__env->yieldContent('subheading'); ?></p>

        <?php echo $__env->yieldContent('content'); ?>

        <?php echo $__env->yieldContent('footer-links'); ?>
    </div>

    <div style="font-size:11.5px;color:var(--fg-4);padding-top:20px;margin-top:auto;">
        Need help? <a href="<?php echo e(route('contact')); ?>" style="color:var(--fg-3);text-decoration:none;">Contact support</a>
    </div>
</div>


<div class="auth-brand-panel">
    <div style="display:inline-flex;align-items:center;gap:8px;position:relative;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="white" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="white" stroke-width="2"/>
        </svg>
    </div>

    <div style="flex:1;display:flex;flex-direction:column;justify-content:center;position:relative;">
        <h2 style="font-size:42px;font-weight:600;letter-spacing:-1.5px;line-height:1.05;margin:0 0 20px;color:white;">
            Work that pays<br>in minutes.
        </h2>
        <p style="font-size:15.5px;color:rgba(255,255,255,0.8);line-height:1.55;max-width:400px;margin:0 0 40px;">
            Join 38,000+ workers earning coins on <?php echo e(gs()->site_name ?? 'Job Station'); ?> every day. Instant microtasks, hiring jobs, and long-term contracts — one wallet.
        </p>

        
        <div style="position:relative;height:200px;max-width:440px;">
            
            <div class="auth-card" style="top:0;left:0;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span style="font-size:9.5px;font-weight:600;text-transform:uppercase;padding:2px 7px;border-radius:999px;background:rgba(255,122,89,0.14);color:#FF7A59;">Instant</span>
                    <span style="font-size:11px;color:#EF4444;font-weight:500;font-family:ui-monospace,monospace;">⏱ 12:30</span>
                </div>
                <div style="font-size:13px;font-weight:500;margin-bottom:10px;">Test mobile checkout flow</div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid #E5E7EB;">
                    <span style="font-size:13px;font-weight:600;color:#CA8A04;font-family:ui-monospace,monospace;">JC22</span>
                    <span style="font-size:10.5px;color:#6B7280;">Start →</span>
                </div>
            </div>
            
            <div class="auth-card" style="top:68px;left:160px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span style="font-size:9.5px;font-weight:600;text-transform:uppercase;padding:2px 7px;border-radius:999px;background:rgba(96,165,250,0.12);color:#60A5FA;">Hiring</span>
                    <span style="font-size:10.5px;color:#6B7280;">Halo Studio</span>
                </div>
                <div style="font-size:13px;font-weight:500;margin-bottom:10px;">Senior Designer</div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid #E5E7EB;">
                    <span style="font-size:13px;font-weight:600;color:#2563EB;font-family:ui-monospace,monospace;">JC8k–12k</span>
                    <span style="font-size:10.5px;color:#6B7280;">Apply →</span>
                </div>
            </div>
        </div>

        
        <div style="margin-top:32px;padding:20px;background:rgba(0,0,0,0.22);border:1px solid rgba(255,255,255,0.12);border-radius:12px;backdrop-filter:blur(12px);max-width:440px;">
            <p style="font-size:13.5px;color:white;line-height:1.55;margin:0 0 14px;">
                "Earned my first JC500 in a weekend. Three months later Job Station turned into my full-time income."
            </p>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:28px;height:28px;border-radius:50%;background:#2f54eb;display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:600;">P</div>
                <div>
                    <div style="font-size:12px;font-weight:500;color:white;">Priya Nair</div>
                    <div style="font-size:10.5px;color:rgba(255,255,255,0.6);">QA Engineer · L2 verified</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:20px;font-size:11.5px;color:rgba(255,255,255,0.6);position:relative;">
        <span>© <?php echo e(date('Y')); ?> <?php echo e(gs()->site_name ?? 'Job Station'); ?></span>
        <span>·</span>
        <a href="<?php echo e(url('/terms')); ?>" style="color:rgba(255,255,255,0.6);text-decoration:none;">Terms</a>
        <span>·</span>
        <a href="<?php echo e(url('/privacy')); ?>" style="color:rgba(255,255,255,0.6);text-decoration:none;">Privacy</a>
    </div>
</div>

<script>
    document.addEventListener('alpine:initialized', () => { if (window.lucide) lucide.createIcons(); });
    document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
</body>
</html>
<?php /**PATH /var/www/resources/views/user/layouts/auth.blade.php ENDPATH**/ ?>