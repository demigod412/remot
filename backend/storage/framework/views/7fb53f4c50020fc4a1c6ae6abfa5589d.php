<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e(app()->getLocale() === 'ar' ? 'rtl' : 'ltr'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <?php
        $gsSettings = gs();
        $siteName   = $gsSettings->site_name ?? config('app.name');
        $metaDesc   = trim($__env->yieldContent('meta_description'))
            ?: 'Complete micro-tasks, hire talent, run escrow contracts and earn coins on ' . $siteName . '.';
        $ogImage    = ! empty($gsSettings->logo)
            ? fileUrl(config('jobstation.upload_paths.logos'), $gsSettings->logo)
            : asset('apple-touch-icon.png');
        $canonical  = url()->current();
    ?>

    <title><?php echo $__env->yieldContent('title', $siteName); ?></title>
    <meta name="description" content="<?php echo e($metaDesc); ?>">
    <link rel="canonical" href="<?php echo e($canonical); ?>">
    <meta name="robots" content="index, follow">

    
    <meta property="og:type" content="<?php echo $__env->yieldContent('og_type', 'website'); ?>">
    <meta property="og:site_name" content="<?php echo e($siteName); ?>">
    <meta property="og:title" content="<?php echo $__env->yieldContent('title', $siteName); ?>">
    <meta property="og:description" content="<?php echo e($metaDesc); ?>">
    <meta property="og:url" content="<?php echo e($canonical); ?>">
    <meta property="og:image" content="<?php echo $__env->yieldContent('og_image', $ogImage); ?>">

    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $__env->yieldContent('title', $siteName); ?>">
    <meta name="twitter:description" content="<?php echo e($metaDesc); ?>">
    <meta name="twitter:image" content="<?php echo $__env->yieldContent('og_image', $ogImage); ?>">

    
    <script type="application/ld+json">
    <?php echo json_encode([
        '<?php $__contextArgs = [];
if (context()->has($__contextArgs[0])) :
if (isset($value)) { $__contextPrevious[] = $value; }
$value = context()->get($__contextArgs[0]); ?>' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => $siteName,
        'url'      => url('/'),
        'logo'     => $ogImage,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>

    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/web.css', 'resources/js/app.js']); ?>

    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body x-data="{ mobileOpen: false }" @keydown.escape.window="mobileOpen = false">


<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?php echo e(route('home')); ?>" class="navbar-brand">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="#2f54eb" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="#2f54eb" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="#2f54eb" stroke-width="2"/>
            </svg>
            <?php echo e(gs()->site_name ?? config('app.name')); ?>

        </a>

        <nav class="navbar-nav">
            <a href="<?php echo e(route('works.index')); ?>"
               class="<?php echo e(request()->routeIs('works.*') || request()->routeIs('jobs.*') ? 'active' : ''); ?>">
                <?php echo e(__('Find work')); ?>

            </a>
            <a href="<?php echo e(route('featured')); ?>"
               class="<?php echo e(request()->routeIs('featured') ? 'active' : ''); ?>">
                ⭐ <?php echo e(__('Featured')); ?>

            </a>
            <a href="<?php echo e(route('home')); ?>#how-it-works"
               class="<?php echo e(request()->routeIs('home') ? 'active' : ''); ?>">
                <?php echo e(__('How it works')); ?>

            </a>
            <a href="<?php echo e(route('blog.index')); ?>"
               class="<?php echo e(request()->routeIs('blog.*') ? 'active' : ''); ?>">
                <?php echo e(__('Blog')); ?>

            </a>
        </nav>

        <div class="navbar-actions">
            
            <?php if(gs()->show_coin_rate): ?>
            <?php
                $webCurrencies = array_values(array_filter(gs()->currencies ?? [], fn($c) => !empty($c['code']) && isset($c['rate']) && $c['rate'] > 0));
                $webDefaultCur = gs()->default_currency ?? ($webCurrencies[0]['code'] ?? '');
            ?>
            <?php if(count($webCurrencies) > 0): ?>
            <div x-data="{
                open: false,
                currencies: <?php echo e(json_encode($webCurrencies)); ?>,
                sel: localStorage.getItem('jobstation_currency') || '<?php echo e($webDefaultCur); ?>',
                init() {
                    if (!this.currencies.find(c => c.code === this.sel)) {
                        this.sel = this.currencies[0] ? this.currencies[0].code : '';
                    }
                },
                get cur() { return this.currencies.find(c => c.code === this.sel) || this.currencies[0] || {code:'',name:'',symbol:'',rate:0}; },
                fmt(r) { const n = Number(r); if (isNaN(n)) return '—'; return Number.isInteger(n) ? n.toLocaleString() : n.toFixed(2); },
                pick(code) { this.sel = code; localStorage.setItem('jobstation_currency', code); this.open = false; }
            }" @click.outside="open = false" style="position:relative;">
                <button @click="open = !open" type="button"
                        style="display:flex;align-items:center;gap:5px;padding:5px 11px;border-radius:20px;background:rgba(245,213,71,0.10);border:1px solid rgba(245,213,71,0.22);font-size:12px;font-weight:500;color:#b8960a;white-space:nowrap;cursor:pointer;font-family:inherit;line-height:1;">
                    <span style="font-family:ui-monospace,monospace;font-size:12px;color:#d97706;"><?php echo e(coinSymbol()); ?>1</span>
                    <span style="color:#b8960a;opacity:0.5;">=</span>
                    <span style="font-family:ui-monospace,monospace;font-size:12px;" x-text="fmt(cur.rate)"></span>
                    <span style="font-size:11px;opacity:0.8;" x-text="cur.symbol"></span>
                    <svg style="flex-shrink:0;transition:transform .15s;" :style="open ? {transform:'rotate(180deg)'} : {transform:'rotate(0deg)'}" width="8" height="5" viewBox="0 0 9 5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 1l3.5 3L8 1"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition
                     style="position:absolute;right:0;top:calc(100% + 8px);background:#fff;border:1px solid rgba(0,0,0,0.1);border-radius:12px;min-width:200px;overflow:hidden;z-index:200;box-shadow:0 8px 24px rgba(0,0,0,0.12);">
                    <div style="padding:8px 14px 6px;font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;">Select currency</div>
                    <template x-for="c in currencies" :key="c.code">
                        <button @click="pick(c.code)" type="button"
                                style="display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:9px 14px;font-size:13px;font-family:inherit;border:none;cursor:pointer;transition:background .1s;text-align:left;"
                                :style="{ background: sel===c.code ? 'rgba(47,84,235,0.08)' : 'transparent', color: sel===c.code ? '#16a34a' : '#374151', fontWeight: sel===c.code ? '600' : '400' }">
                            <span x-text="c.name" style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></span>
                            <span style="font-family:ui-monospace,monospace;font-size:11px;color:#9ca3af;flex-shrink:0;" x-text="c.symbol"></span>
                        </button>
                    </template>
                </div>
            </div>
            <?php elseif(gs()->coin_rate): ?>
            <div style="display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;background:rgba(245,213,71,0.10);border:1px solid rgba(245,213,71,0.22);font-size:12px;font-weight:500;color:#b8960a;white-space:nowrap;">
                <span style="font-family:ui-monospace,monospace;font-size:13px;color:var(--coin,#F5D547);"><?php echo e(coinSymbol()); ?>1</span>
                <span style="color:#b8960a;opacity:0.6;">=</span>
                <span style="font-family:ui-monospace,monospace;"><?php echo e(number_format(gs()->coin_rate, gs()->coin_rate == intval(gs()->coin_rate) ? 0 : 2)); ?> <?php echo e(gs()->coin_rate_currency ?? gs()->cur_text); ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if(auth()->guard('web')->check()): ?>
                <a href="<?php echo e(route('user.dashboard')); ?>" class="btn-primary-sm"><?php echo e(__('Dashboard')); ?></a>
            <?php else: ?>
                <a href="<?php echo e(route('user.login')); ?>" class="btn-outline-sm"><?php echo e(__('Sign in')); ?></a>
                <?php if(gs()->registration): ?>
                    <a href="<?php echo e(route('user.register')); ?>" class="btn-primary-sm"><?php echo e(__('Get started')); ?></a>
                <?php endif; ?>
            <?php endif; ?>

            
            <?php $languages = \App\Models\Language::all(); ?>
            <?php if($languages->count() > 1): ?>
            <div style="position:relative" x-data="{ open:false }">
                <button @click="open=!open" class="btn-outline-sm" style="gap:6px;display:flex;align-items:center">
                    <?php echo e(strtoupper(app()->getLocale())); ?>

                    <svg width="10" height="6" viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                </button>
                <div x-show="open" @click.outside="open=false" x-cloak
                     style="position:absolute;right:0;top:calc(100% + 8px);background:#fff;border:1px solid var(--border);border-radius:10px;min-width:120px;overflow:hidden;z-index:200;box-shadow:0 8px 24px rgba(10,10,11,0.1);">
                    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('language', $lang->code)); ?>"
                           style="display:block;padding:10px 16px;font-size:13px;font-weight:500;color:var(--muted);transition:.15s;"
                           onmouseover="this.style.background='rgba(10,10,11,0.04)';this.style.color='var(--text)'"
                           onmouseout="this.style.background='';this.style.color='var(--muted)'">
                            <?php echo e($lang->name); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <button class="navbar-toggle" @click="mobileOpen = !mobileOpen" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>


<div x-show="mobileOpen" x-cloak
     style="position:fixed;inset:0;z-index:99;background:rgba(15,23,42,0.4);"
     @click="mobileOpen=false">
</div>
<div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-x-full"
     x-transition:enter-end="opacity-100 translate-x-0"
     style="position:fixed;top:0;left:0;bottom:0;width:280px;z-index:100;background:#fff;box-shadow:4px 0 24px rgba(15,23,42,0.12);padding:24px;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;">
        <a href="<?php echo e(route('home')); ?>" style="display:inline-flex;align-items:center;gap:8px;font-size:15px;font-weight:600;color:var(--text);text-decoration:none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="#2f54eb" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="#2f54eb" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="#2f54eb" stroke-width="2"/>
            </svg>
            <?php echo e(gs()->site_name ?? config('app.name')); ?>

        </a>
        <button @click="mobileOpen=false" style="background:none;border:none;cursor:pointer;padding:4px;">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 5l10 10M15 5L5 15" stroke="var(--text)" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
    </div>
    <nav style="display:flex;flex-direction:column;gap:2px;">
        <a href="<?php echo e(route('works.index')); ?>"
           style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:<?php echo e(request()->routeIs('works.*','jobs.*') ? 'var(--accent)' : 'var(--text)'); ?>;background:<?php echo e(request()->routeIs('works.*','jobs.*') ? 'var(--accent-bg)' : 'transparent'); ?>;">
           <?php echo e(__('Find work')); ?>

        </a>
        <a href="<?php echo e(route('featured')); ?>"
           style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:<?php echo e(request()->routeIs('featured') ? 'var(--accent)' : 'var(--muted)'); ?>;background:<?php echo e(request()->routeIs('featured') ? 'var(--accent-bg)' : 'transparent'); ?>;">
           ⭐ <?php echo e(__('Featured')); ?>

        </a>
        <a href="<?php echo e(route('home')); ?>#how-it-works"
           style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:var(--muted);">
           <?php echo e(__('How it works')); ?>

        </a>
        <a href="<?php echo e(route('blog.index')); ?>"
           style="padding:10px 14px;border-radius:8px;font-size:14px;font-weight:500;color:<?php echo e(request()->routeIs('blog.*') ? 'var(--accent)' : 'var(--muted)'); ?>;background:<?php echo e(request()->routeIs('blog.*') ? 'var(--accent-bg)' : 'transparent'); ?>;">
           <?php echo e(__('Blog')); ?>

        </a>
    </nav>
    <div style="margin-top:24px;padding-top:24px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:8px;">
        <?php if(auth()->guard('web')->check()): ?>
            <a href="<?php echo e(route('user.dashboard')); ?>" class="btn btn-primary btn-sm" style="justify-content:center;"><?php echo e(__('Dashboard')); ?></a>
        <?php else: ?>
            <a href="<?php echo e(route('user.login')); ?>"    class="btn btn-secondary btn-sm" style="justify-content:center;"><?php echo e(__('Sign in')); ?></a>
            <?php if(gs()->registration): ?>
            <a href="<?php echo e(route('user.register')); ?>" class="btn btn-primary btn-sm" style="justify-content:center;"><?php echo e(__('Get started')); ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>


<div class="flash" id="flash-container">
    <?php $__currentLoopData = ['success','error','info','warning']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(session($type)): ?>
            <div class="flash-item flash-<?php echo e($type === 'warning' ? 'yellow' : $type); ?>" onclick="this.remove()">
                <?php echo e(session($type)); ?>

            </div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php echo $__env->yieldContent('content'); ?>


<footer style="background:#111827;margin-top:0;">

    
    <?php if(gs()->registration ?? true): ?>
    <div style="padding:0 24px;">
        <div style="max-width:1200px;margin:0 auto;transform:translateY(-50px);">
            <div style="background:#1f2937;border-radius:20px;padding:48px 56px;
                        display:flex;align-items:center;justify-content:space-between;
                        gap:32px;flex-wrap:wrap;position:relative;overflow:hidden;
                        border:1px solid rgba(255,255,255,.06);">
                
                <div style="position:absolute;width:200px;height:200px;border-radius:50%;
                            background:rgba(47,84,235,.08);top:-60px;left:40px;pointer-events:none;"></div>
                <div style="position:absolute;width:140px;height:140px;border-radius:50%;
                            background:rgba(47,84,235,.06);bottom:-40px;right:120px;pointer-events:none;"></div>
                <div>
                    <div style="font-size:13px;font-weight:800;color:#2f54eb;letter-spacing:.5px;
                                text-transform:uppercase;margin-bottom:10px;">
                        <?php echo e(__('Talk to support')); ?>

                    </div>
                    <h2 style="font-size:clamp(22px,3vw,36px);font-weight:900;color:#fff;
                               line-height:1.2;margin-bottom:12px;letter-spacing:-.5px;">
                        <?php echo e(__('Join & Get a Unique Opportunity')); ?>

                    </h2>
                    <p style="font-size:15px;color:rgba(255,255,255,.55);line-height:1.75;max-width:520px;">
                        <?php echo e(__('Connect with skilled workers, streamline your hiring, and unlock success. Join now and redefine your work experience!')); ?>

                    </p>
                </div>
                <div style="text-align:center;flex-shrink:0;">
                    <a href="<?php echo e(route('user.register')); ?>"
                       style="display:inline-flex;align-items:center;gap:10px;
                              background:#2f54eb;color:#fff;border-radius:12px;
                              padding:16px 36px;font-size:16px;font-weight:800;
                              transition:all .15s;white-space:nowrap;"
                       onmouseover="this.style.background='#2442c4';this.style.transform='translateY(-1px)';this.style.boxShadow='0 8px 28px rgba(47,84,235,.4)'"
                       onmouseout="this.style.background='#2f54eb';this.style.transform='';this.style.boxShadow=''">
                        <?php echo e(__('Get Started Now')); ?>

                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <div style="font-size:12px;color:rgba(255,255,255,.4);margin-top:10px;font-weight:600;">
                        <?php echo e(__('Try it free — no credit card required')); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <div style="padding:0 24px 56px;margin-top:<?php echo e(gs()->registration ?? true ? '-10px' : '56px'); ?>;">
        <div style="max-width:1200px;margin:0 auto;
                    display:grid;grid-template-columns:2fr 1.4fr 1fr;gap:56px;flex-wrap:wrap;"
             class="footer-main-grid">

            
            <div>
                <div style="font-size:24px;font-weight:900;color:#fff;
                            letter-spacing:-.5px;margin-bottom:14px;">
                    <?php echo e(gs()->site_name ?? config('app.name')); ?>

                </div>
                <p style="font-size:14px;color:rgba(255,255,255,.5);line-height:1.8;
                           max-width:280px;margin-bottom:28px;">
                    <?php echo e(__('Our platform offers the best features for workers and employers, making it easy to connect, collaborate, and get work done.')); ?>

                </p>
                
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <?php if(gs()->app_store_url): ?>
                    <a href="<?php echo e(gs()->app_store_url); ?>" target="_blank" rel="noopener"
                       style="display:flex;align-items:center;gap:10px;background:#fff;
                              border-radius:10px;padding:9px 18px;transition:.15s;"
                       onmouseover="this.style.background='#f0fdf9'"
                       onmouseout="this.style.background='#fff'">
                        <svg width="20" height="24" viewBox="0 0 20 24" fill="#000"><path d="M14.77 12.67c-.02-2.57 2.1-3.8 2.19-3.86-1.19-1.74-3.05-1.98-3.72-2.01-1.58-.16-3.1.93-3.9.93-.8 0-2.02-.91-3.32-.88-1.7.02-3.27 1-4.14 2.51-1.77 3.07-.45 7.6 1.27 10.09.84 1.22 1.85 2.59 3.17 2.54 1.28-.05 1.76-.82 3.31-.82 1.54 0 1.97.82 3.32.8 1.37-.02 2.24-1.24 3.07-2.47.97-1.41 1.37-2.78 1.39-2.85-.03-.02-2.67-1.02-2.7-3.99zM12.23 4.46c.7-.85 1.17-2.02 1.04-3.2-1 .04-2.22.67-2.94 1.51-.64.74-1.2 1.93-1.05 3.07 1.11.09 2.24-.57 2.95-1.38z"/></svg>
                        <div>
                            <div style="font-size:9px;color:#333;font-weight:600;line-height:1;"><?php echo e(__('Available on the')); ?></div>
                            <div style="font-size:13px;color:#000;font-weight:800;line-height:1.3;">App Store</div>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if(gs()->play_store_url): ?>
                    <a href="<?php echo e(gs()->play_store_url); ?>" target="_blank" rel="noopener"
                       style="display:flex;align-items:center;gap:10px;background:#fff;
                              border-radius:10px;padding:9px 18px;transition:.15s;"
                       onmouseover="this.style.background='#f0fdf9'"
                       onmouseout="this.style.background='#fff'">
                        <svg width="20" height="22" viewBox="0 0 20 22" fill="none">
                            <path d="M.3 1.2C.1 1.4 0 1.8 0 2.3v17.4c0 .5.1.9.3 1.1l.1.1 9.7-9.7v-.2L.4 1.1l-.1.1z" fill="#4285F4"/>
                            <path d="M13.4 14.5l-3.2-3.2v-.3l3.2-3.2.1.1 3.8 2.2c1.1.6 1.1 1.6 0 2.2l-3.8 2.1-.1.1z" fill="#FBBC04"/>
                            <path d="M13.5 14.4L10.2 11 .3 20.9c.4.4.9.4 1.6.1l11.6-6.6" fill="#34A853"/>
                            <path d="M13.5 7.6L1.9 1C1.2.7.7.7.3 1.1L10.2 11l3.3-3.4z" fill="#EA4335"/>
                        </svg>
                        <div>
                            <div style="font-size:9px;color:#333;font-weight:600;line-height:1;"><?php echo e(__('Android App on')); ?></div>
                            <div style="font-size:13px;color:#000;font-weight:800;line-height:1.3;">Google Play</div>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            
            <div>
                <h4 style="font-size:13px;font-weight:800;color:#fff;text-transform:uppercase;
                           letter-spacing:1px;margin-bottom:20px;">
                    <?php echo e(__('Top Rated Categories')); ?>

                </h4>
                <?php $cats = \App\Models\WorkCategory::where('status',1)->limit(8)->get(); ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;">
                    <?php $__empty_1 = true; $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <a href="<?php echo e(route('works.index', ['category' => $cat->id])); ?>"
                           style="font-size:14px;color:rgba(255,255,255,.5);transition:.15s;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                           onmouseover="this.style.color='#2f54eb'"
                           onmouseout="this.style.color='rgba(255,255,255,.5)'">
                            <?php echo e($cat->name); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <a href="<?php echo e(route('works.index')); ?>" style="font-size:14px;color:rgba(255,255,255,.5);"><?php echo e(__('Browse All')); ?></a>
                    <?php endif; ?>
                </div>
                <?php if($cats->count() >= 8): ?>
                <a href="<?php echo e(route('works.index')); ?>"
                   style="display:inline-block;margin-top:16px;font-size:13px;
                          font-weight:700;color:#2f54eb;transition:.15s;"
                   onmouseover="this.style.color='#5570e8'"
                   onmouseout="this.style.color='#2f54eb'">
                    + <?php echo e(__('Show All')); ?>

                </a>
                <?php endif; ?>
            </div>

            
            <div>
                <h4 style="font-size:13px;font-weight:800;color:#fff;text-transform:uppercase;
                           letter-spacing:1px;margin-bottom:20px;">
                    <?php echo e(__('Feel Free To Ask')); ?>

                </h4>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php if(gs()->contact_email): ?>
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(47,84,235,.15);
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f54eb" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:rgba(255,255,255,.35);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;"><?php echo e(__('Email')); ?></div>
                            <a href="mailto:<?php echo e(gs()->contact_email); ?>" style="font-size:13px;color:rgba(255,255,255,.7);transition:.15s;"
                               onmouseover="this.style.color='#2f54eb'" onmouseout="this.style.color='rgba(255,255,255,.7)'"><?php echo e(gs()->contact_email); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if(gs()->contact_phone): ?>
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(47,84,235,.15);
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f54eb" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.12 1.18 2 2 0 012.11 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.09a16 16 0 006 6l.45-.45a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:rgba(255,255,255,.35);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;"><?php echo e(__('Phone')); ?></div>
                            <a href="tel:<?php echo e(gs()->contact_phone); ?>" style="font-size:13px;color:rgba(255,255,255,.7);transition:.15s;"
                               onmouseover="this.style.color='#2f54eb'" onmouseout="this.style.color='rgba(255,255,255,.7)'"><?php echo e(gs()->contact_phone); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(47,84,235,.15);
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2f54eb" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:rgba(255,255,255,.35);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;"><?php echo e(__('Support Hours')); ?></div>
                            <div style="font-size:13px;color:rgba(255,255,255,.7);"><?php echo e(gs()->support_hours ?: __('Mon – Fri, 9am – 6pm')); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div style="border-top:1px solid rgba(255,255,255,.07);padding:20px 24px;">
        <div style="max-width:1200px;margin:0 auto;
                    display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <a href="<?php echo e(route('pages.show', 'terms')); ?>"
                   style="font-size:13px;color:rgba(255,255,255,.4);transition:.15s;"
                   onmouseover="this.style.color='rgba(255,255,255,.8)'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                    <?php echo e(__('Terms & Condition')); ?>

                </a>
                <a href="<?php echo e(route('pages.show', 'privacy-policy')); ?>"
                   style="font-size:13px;color:rgba(255,255,255,.4);transition:.15s;"
                   onmouseover="this.style.color='rgba(255,255,255,.8)'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                    <?php echo e(__('Privacy Policy')); ?>

                </a>
                <a href="<?php echo e(route('pages.show', 'about')); ?>"
                   style="font-size:13px;color:rgba(255,255,255,.4);transition:.15s;"
                   onmouseover="this.style.color='rgba(255,255,255,.8)'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                    <?php echo e(__('About Us')); ?>

                </a>
                <a href="<?php echo e(route('contact')); ?>"
                   style="font-size:13px;color:rgba(255,255,255,.4);transition:.15s;"
                   onmouseover="this.style.color='rgba(255,255,255,.8)'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                    <?php echo e(__('Contact')); ?>

                </a>
            </div>

            <div style="font-size:13px;color:rgba(255,255,255,.35);">
                &copy; <?php echo e(date('Y')); ?> <?php echo e(gs()->site_name ?? config('app.name')); ?>. <?php echo e(__('All rights reserved.')); ?>

            </div>

            
            <div style="display:flex;gap:10px;">
                <?php
                    $socials = array_filter([
                        ['href' => gs()->facebook,  'icon' => '<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>'],
                        ['href' => gs()->twitter,   'icon' => '<path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>'],
                        ['href' => gs()->linkedin,  'icon' => '<path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'],
                        ['href' => gs()->instagram, 'icon' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'],
                    ], fn ($s) => ! empty($s['href']));
                ?>
                <?php $__currentLoopData = $socials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($s['href']); ?>" target="_blank" rel="noopener"
                   style="width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,.07);
                          display:flex;align-items:center;justify-content:center;transition:.15s;"
                   onmouseover="this.style.background='rgba(47,84,235,.25)';this.style.color='#2f54eb'"
                   onmouseout="this.style.background='rgba(255,255,255,.07)';this.style.color='rgba(255,255,255,.5)'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.5)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <?php echo $s['icon']; ?>

                    </svg>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</footer>

<style>
@media(max-width:900px){
    .footer-main-grid{ grid-template-columns:1fr !important; gap:40px !important; }
}
</style>


<?php $showCookie = (gs()->cookie_enabled ?? true); ?>
<?php if($showCookie): ?>
<div id="cookie-banner">
    
    <div style="width:36px;height:36px;border-radius:10px;background:var(--accent-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5v.01"/><path d="M16 15.5v.01"/><path d="M12 12v.01"/><path d="M11 17v.01"/><path d="M7 14v.01"/></svg>
    </div>

    
    <div style="flex:1;min-width:0;">
        <div style="font-size:13.5px;font-weight:600;color:var(--text);margin-bottom:2px;"><?php echo e(__('We use cookies')); ?></div>
        <div style="font-size:12.5px;color:var(--muted);line-height:1.55;">
            <?php echo e(gs()->cookie_text ?? __('We use cookies to improve your experience and analyse site usage.')); ?>

            <a href="<?php echo e(route('pages.show', 'cookie-policy')); ?>"
               style="color:var(--accent);text-decoration:none;font-weight:500;"
               onmouseover="this.style.textDecoration='underline'"
               onmouseout="this.style.textDecoration='none'"><?php echo e(__('Learn more')); ?></a>
        </div>
    </div>

    
    <div style="display:flex;gap:8px;flex-shrink:0;">
        <button id="reject-cookie" type="button" class="btn btn-secondary btn-sm">
            <?php echo e(__('Reject')); ?>

        </button>
        <button id="accept-cookie" type="button" class="btn btn-primary btn-sm">
            <?php echo e(__('Accept all')); ?>

        </button>
    </div>
</div>
<script>
(function () {
    var banner = document.getElementById('cookie-banner');
    if (!banner) return;
    // Already accepted/rejected on a previous visit → remove before paint (no flash, no re-prompt).
    if (document.cookie.split('; ').some(function (c) { return c.indexOf('cookie_consent=') === 0; })) {
        banner.remove();
        return;
    }
    function decide(value) {
        // Persist the choice for a year so it survives reloads and new sessions.
        document.cookie = 'cookie_consent=' + value + ';path=/;max-age=31536000;SameSite=Lax';
        banner.classList.add('hiding');
        setTimeout(function () { banner.remove(); }, 280);
    }
    var a = document.getElementById('accept-cookie');
    var r = document.getElementById('reject-cookie');
    if (a) a.addEventListener('click', function () { decide('accepted'); });
    if (r) r.addEventListener('click', function () { decide('rejected'); });
})();
</script>
<?php endif; ?>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    setTimeout(() => document.querySelectorAll('.flash-item').forEach(el => el.remove()), 5000);
</script>
<?php echo $__env->yieldPushContent('scripts'); ?>

<?php echo renderPluginScripts(); ?>

</body>
</html>
<?php /**PATH /var/www/resources/views/web/layouts/app.blade.php ENDPATH**/ ?>