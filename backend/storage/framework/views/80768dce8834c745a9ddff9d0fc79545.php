<!DOCTYPE html>
<html lang="en">
<head>
    <script>(function(){if(localStorage.getItem('jobstation-theme')==='dark')document.documentElement.classList.add('dark');})()</script>
    <script>(function(){document.documentElement.style.setProperty('--sb-w',localStorage.getItem('admin_sidebar')==='0'?'64px':'240px');})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> — <?php echo e(gs()->site_name ?? 'Job Station'); ?> Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body style="background:var(--bg); color:var(--fg);">

<div class="flex h-screen overflow-hidden" x-data="{
    sidebarOpen: localStorage.getItem('admin_sidebar') !== '0',
    mobileOpen: false,
    toggleSidebar() { this.sidebarOpen = !this.sidebarOpen; localStorage.setItem('admin_sidebar', this.sidebarOpen ? '1' : '0'); }
}">

    
    <div
        x-show="mobileOpen"
        x-cloak
        x-transition.opacity
        @click="mobileOpen = false"
        class="fixed inset-0 z-20 lg:hidden"
        style="background:rgba(0,0,0,0.6);"
    ></div>

    
    <aside
        class="fixed lg:relative z-30 h-full flex flex-col dark-sidebar lg:translate-x-0"
        :class="[
            sidebarOpen ? 'w-60' : 'w-16',
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            'transition-all duration-300'
        ]"
        style="background:#27272A; border-right:1px solid rgba(255,255,255,0.06); flex-shrink:0; width:var(--sb-w,240px); overflow:hidden;"
        :style="{ width: sidebarOpen ? '240px' : '64px' }"
    >
        
        <div style="display:flex; align-items:center; height:60px; padding:0 14px; border-bottom:1px solid rgba(255,255,255,0.06); flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:8px; overflow:hidden;">
                
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;">
                    <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="var(--accent)" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="var(--accent)" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="var(--accent)" stroke-width="2"/>
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity
                      style="font-weight:600; font-size:15px; color:#fff; letter-spacing:-0.3px; white-space:nowrap;">
                    <?php echo e(gs()->site_name ?? 'Job Station'); ?>

                </span>
                <span x-show="sidebarOpen" x-transition.opacity
                      style="margin-left:auto; font-size:10px; font-weight:600; color:var(--urgent); letter-spacing:0.12em; text-transform:uppercase; padding:2px 7px; border-radius:4px; background:var(--urgent-soft); white-space:nowrap; flex-shrink:0;">
                    ADMIN
                </span>
            </div>
        </div>

        
        <nav style="flex:1; overflow-y:auto; padding:12px 10px; display:flex; flex-direction:column; gap:2px;">

            <a href="<?php echo e(route('admin.dashboard')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>"
               title="Dashboard">
                <i data-lucide="layout-dashboard" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Dashboard</span>
            </a>

            
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.works*', 'admin.task-review*', 'admin.categories*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open"
                    class="sidebar-link <?php echo e(request()->routeIs('admin.works*', 'admin.task-review*', 'admin.categories*') ? 'active' : ''); ?>"
                    title="Works">
                    <i data-lucide="briefcase" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate" style="flex:1;text-align:left;">Works</span>
                    <i data-lucide="chevron-down" x-show="sidebarOpen"
                       :class="open ? 'rotate-180' : ''"
                       style="width:14px;height:14px;flex-shrink:0;transition:transform .2s;"></i>
                </button>
                <div x-show="open && sidebarOpen" x-transition style="padding-left:12px; margin-top:2px; display:flex; flex-direction:column; gap:2px;">
                    <a href="<?php echo e(route('admin.works.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.works.index') ? 'active' : ''); ?>">
                        <i data-lucide="list" style="width:14px;height:14px;"></i><span>All Works</span>
                    </a>
                    <a href="<?php echo e(route('admin.works.pending')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.works.pending') ? 'active' : ''); ?>">
                        <i data-lucide="clock" style="width:14px;height:14px;"></i><span>Pending Approval</span>
                    </a>
                    
                    <a href="<?php echo e(route('admin.task-review.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.task-review*') ? 'active' : ''); ?>">
                        <i data-lucide="clipboard-check" style="width:14px;height:14px;"></i>
                        <span>Task Review</span>
                        <?php
                            $pendingReview = \App\Models\WorkSubmission::where(function ($q) {
                                $q->where('application_status', \App\Models\WorkSubmission::APP_APPLIED)
                                  ->orWhere('delivery_status', \App\Models\WorkSubmission::DEL_SUBMITTED);
                            })->count();
                        ?>
                        <?php if($pendingReview > 0): ?>
                        <span class="mono" x-show="sidebarOpen" style="margin-left:auto;font-size:10px;padding:2px 6px;background:var(--urgent-soft);color:var(--urgent);border-radius:4px;"><?php echo e($pendingReview); ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo e(route('admin.categories.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.categories*') ? 'active' : ''); ?>">
                        <i data-lucide="tag" style="width:14px;height:14px;"></i><span>Categories</span>
                    </a>
                </div>
            </div>

            
            <a href="<?php echo e(route('admin.skills.index')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.skills*') ? 'active' : ''); ?>"
               title="Skills">
                <i data-lucide="sparkles" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Skills</span>
            </a>

            
            <a href="<?php echo e(route('admin.jobs.listings.index')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.jobs*') ? 'active' : ''); ?>"
               title="Job Listings">
                <i data-lucide="building-2" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Job Listings</span>
            </a>

            
            <a href="<?php echo e(route('admin.boost-requests.index')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.boost-requests*') ? 'active' : ''); ?>"
               title="Boost Requests">
                <i data-lucide="zap" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Boost Requests</span>
                <?php $pendingBoosts = \App\Models\BoostRequest::where('status', 0)->count(); ?>
                <?php if($pendingBoosts > 0): ?>
                <span class="mono" x-show="sidebarOpen" style="margin-left:auto;font-size:10px;padding:2px 6px;background:rgba(245,158,11,0.15);color:#F59E0B;border-radius:4px;flex-shrink:0;"><?php echo e($pendingBoosts); ?></span>
                <?php endif; ?>
            </a>

            
            <a href="<?php echo e(route('admin.membership.index')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.membership*') ? 'active' : ''); ?>"
               title="Membership Applications">
                <i data-lucide="user-plus" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Membership</span>
                <?php
                    $pendingMembers = \App\Models\MembershipApplication::where('status', \App\Models\MembershipApplication::STATUS_PENDING)->count();
                ?>
                <?php if($pendingMembers > 0): ?>
                <span class="mono" x-show="sidebarOpen" style="margin-left:auto;font-size:10px;padding:2px 6px;background:var(--urgent-soft);color:var(--urgent);border-radius:4px;"><?php echo e($pendingMembers); ?></span>
                <?php endif; ?>
            </a>

            
            <a href="<?php echo e(route('admin.contracts.index')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.contracts*') ? 'active' : ''); ?>"
               title="Contracts">
                <i data-lucide="file-text" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Contracts</span>
                <?php $disputed = \App\Models\Contract::where('status', 6)->count(); ?>
                <?php if($disputed > 0): ?>
                <span class="mono" x-show="sidebarOpen" style="margin-left:auto;font-size:10px;padding:2px 6px;background:rgba(239,68,68,0.15);color:#EF4444;border-radius:4px;flex-shrink:0;"><?php echo e($disputed); ?></span>
                <?php endif; ?>
            </a>

            
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.users*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open"
                    class="sidebar-link <?php echo e(request()->routeIs('admin.users*') ? 'active' : ''); ?>"
                    title="Users">
                    <i data-lucide="users" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate" style="flex:1;text-align:left;">Users</span>
                    <i data-lucide="chevron-down" x-show="sidebarOpen"
                       :class="open ? 'rotate-180' : ''"
                       style="width:14px;height:14px;flex-shrink:0;transition:transform .2s;"></i>
                </button>
                <div x-show="open && sidebarOpen" x-transition style="padding-left:12px; margin-top:2px; display:flex; flex-direction:column; gap:2px;">
                    <a href="<?php echo e(route('admin.users.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.users.index') ? 'active' : ''); ?>">
                        <i data-lucide="list" style="width:14px;height:14px;"></i><span>All Users</span>
                    </a>
                    <a href="<?php echo e(route('admin.users.kyc')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.users.kyc') ? 'active' : ''); ?>">
                        <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                        <span>KYC Requests</span>
                        <?php $pendingKyc = \App\Models\User::where('kyc_status', 2)->count(); ?>
                        <?php if($pendingKyc > 0): ?>
                        <span class="mono" style="margin-left:auto;font-size:10px;padding:2px 6px;background:var(--surface-3);color:var(--fg-2);border-radius:4px;"><?php echo e($pendingKyc); ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.topups*', 'admin.cashouts*', 'admin.ledger*', 'admin.coin-packages*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open" class="sidebar-link" title="Coins & Wallet">
                    <i data-lucide="coins" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate" style="flex:1;text-align:left;">Coins & Wallet</span>
                    <i data-lucide="chevron-down" x-show="sidebarOpen"
                       :class="open ? 'rotate-180' : ''"
                       style="width:14px;height:14px;flex-shrink:0;transition:transform .2s;"></i>
                </button>
                <div x-show="open && sidebarOpen" x-transition style="padding-left:12px; margin-top:2px; display:flex; flex-direction:column; gap:2px;">
                    <a href="<?php echo e(route('admin.topups.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.topups*') ? 'active' : ''); ?>">
                        <i data-lucide="arrow-down-circle" style="width:14px;height:14px;"></i><span>Top-ups</span>
                    </a>
                    <a href="<?php echo e(route('admin.cashouts.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.cashouts*') ? 'active' : ''); ?>">
                        <i data-lucide="arrow-up-circle" style="width:14px;height:14px;"></i><span>Cashouts</span>
                    </a>
                    <a href="<?php echo e(route('admin.ledger.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.ledger*') ? 'active' : ''); ?>">
                        <i data-lucide="book-open" style="width:14px;height:14px;"></i><span>Ledger</span>
                    </a>
                    <a href="<?php echo e(route('admin.coin-packages.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.coin-packages*') ? 'active' : ''); ?>">
                        <i data-lucide="package" style="width:14px;height:14px;"></i><span>Coin Packages</span>
                    </a>
                </div>
            </div>

            
            <a href="<?php echo e(route('admin.payment-channels.index')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.payment-channels*') ? 'active' : ''); ?>"
               title="Payment Channels">
                <i data-lucide="credit-card" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Payment Channels</span>
            </a>

            
            <a href="<?php echo e(route('admin.tickets.index')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.tickets*') ? 'active' : ''); ?>"
               title="Support">
                <i data-lucide="life-buoy" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Support</span>
            </a>

            
            <a href="<?php echo e(route('admin.referrals.index')); ?>"
               class="sidebar-link <?php echo e(request()->routeIs('admin.referrals*') ? 'active' : ''); ?>"
               title="Referrals">
                <i data-lucide="users-2" style="width:16px;height:16px;flex-shrink:0;"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Referrals</span>
            </a>

            
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.reports*', 'admin.audit-log') ? 'true' : 'false'); ?> }">
                <button @click="open = !open"
                    class="sidebar-link <?php echo e(request()->routeIs('admin.reports*', 'admin.audit-log') ? 'active' : ''); ?>"
                    title="Reports">
                    <i data-lucide="bar-chart-2" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate" style="flex:1;text-align:left;">Reports</span>
                    <i data-lucide="chevron-down" x-show="sidebarOpen"
                       :class="open ? 'rotate-180' : ''"
                       style="width:14px;height:14px;flex-shrink:0;transition:transform .2s;"></i>
                </button>
                <div x-show="open && sidebarOpen" x-transition style="padding-left:12px; margin-top:2px; display:flex; flex-direction:column; gap:2px;">
                    
                    <a href="<?php echo e(route('admin.audit-log')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.audit-log') ? 'active' : ''); ?>">
                        <i data-lucide="scroll-text" style="width:14px;height:14px;"></i><span>Audit Log</span>
                    </a>
                    <a href="<?php echo e(route('admin.reports.transactions')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.reports.transactions') ? 'active' : ''); ?>">
                        <i data-lucide="arrow-left-right" style="width:14px;height:14px;"></i><span>Transactions</span>
                    </a>
                    <a href="<?php echo e(route('admin.reports.logins')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.reports.logins') ? 'active' : ''); ?>">
                        <i data-lucide="log-in" style="width:14px;height:14px;"></i><span>Login Logs</span>
                    </a>
                    <a href="<?php echo e(route('admin.reports.notifications')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.reports.notifications') ? 'active' : ''); ?>">
                        <i data-lucide="bell" style="width:14px;height:14px;"></i><span>Notif Logs</span>
                    </a>
                </div>
            </div>

            <div style="height:1px; background:rgba(255,255,255,0.06); margin:6px 0;"></div>

            
            <div x-data="{ open: <?php echo e(request()->routeIs('admin.settings*', 'admin.languages*', 'admin.plugins*', 'admin.pages*', 'admin.content*', 'admin.subscribers*', 'admin.notif-events*', 'admin.notif-templates*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open"
                    class="sidebar-link <?php echo e(request()->routeIs('admin.settings*', 'admin.languages*', 'admin.plugins*', 'admin.pages*', 'admin.content*', 'admin.subscribers*', 'admin.notif-events*', 'admin.notif-templates*') ? 'active' : ''); ?>"
                    title="Settings">
                    <i data-lucide="settings" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate" style="flex:1;text-align:left;">Settings</span>
                    <i data-lucide="chevron-down" x-show="sidebarOpen"
                       :class="open ? 'rotate-180' : ''"
                       style="width:14px;height:14px;flex-shrink:0;transition:transform .2s;"></i>
                </button>
                <div x-show="open && sidebarOpen" x-transition style="padding-left:12px; margin-top:2px; display:flex; flex-direction:column; gap:2px;">
                    <a href="<?php echo e(route('admin.settings.general')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.settings.general') ? 'active' : ''); ?>">
                        <i data-lucide="sliders" style="width:14px;height:14px;"></i><span>General</span>
                    </a>
                    <a href="<?php echo e(route('admin.notif-events')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.notif-events*') ? 'active' : ''); ?>">
                        <i data-lucide="bell" style="width:14px;height:14px;"></i><span>Notifications</span>
                    </a>
                    <a href="<?php echo e(route('admin.languages.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.languages*') ? 'active' : ''); ?>">
                        <i data-lucide="globe" style="width:14px;height:14px;"></i><span>Languages</span>
                    </a>
                    <a href="<?php echo e(route('admin.plugins.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.plugins*') ? 'active' : ''); ?>">
                        <i data-lucide="puzzle" style="width:14px;height:14px;"></i><span>Plugins</span>
                    </a>
                    <a href="<?php echo e(route('admin.pages.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.pages*') ? 'active' : ''); ?>">
                        <i data-lucide="file-text" style="width:14px;height:14px;"></i><span>Pages</span>
                    </a>
                    <a href="<?php echo e(route('admin.content.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.content*') ? 'active' : ''); ?>">
                        <i data-lucide="layout-template" style="width:14px;height:14px;"></i><span>Content Sections</span>
                    </a>
                    <a href="<?php echo e(route('admin.subscribers.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.subscribers*') ? 'active' : ''); ?>">
                        <i data-lucide="mail" style="width:14px;height:14px;"></i><span>Subscribers</span>
                    </a>
                    <a href="<?php echo e(route('admin.notif-templates.index')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.notif-templates*') ? 'active' : ''); ?>">
                        <i data-lucide="mail-open" style="width:14px;height:14px;"></i><span>Email Templates</span>
                    </a>
                    <a href="<?php echo e(route('admin.settings.license')); ?>" class="sidebar-link btn-sm <?php echo e(request()->routeIs('admin.settings.license') ? 'active' : ''); ?>">
                        <i data-lucide="shield-check" style="width:14px;height:14px;"></i><span>License</span>
                    </a>
                </div>
            </div>

        </nav>

        
        <div style="padding:10px; border-top:1px solid rgba(255,255,255,0.06); flex-shrink:0; display:flex; align-items:center; gap:10px;">
            
            <?php
                $adminName = auth('admin')->user()->name ?? 'Admin';
                $initial   = strtoupper(substr($adminName, 0, 1));
                $colors    = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
                $color     = $colors[ord($initial) % count($colors)];
            ?>
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,<?php echo e($color); ?>,<?php echo e($colors[(ord($initial)+2)%count($colors)]); ?>);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:11px;flex-shrink:0;">
                <?php echo e($initial); ?>

            </div>
            <div x-show="sidebarOpen" x-transition.opacity style="flex:1; min-width:0;">
                <div style="font-size:12.5px; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($adminName); ?></div>
                <div style="font-size:10.5px; color:var(--fg-3);">Super-admin</div>
            </div>
            <form method="POST" action="<?php echo e(route('admin.logout')); ?>" x-show="sidebarOpen" x-transition.opacity>
                <?php echo csrf_field(); ?>
                <button type="submit" title="Logout"
                        style="padding:6px;border-radius:6px;color:var(--fg-3);border:none;background:transparent;cursor:pointer;display:flex;align-items:center;"
                        onmouseover="this.style.color='#EF4444';this.style.background='rgba(239,68,68,0.1)'"
                        onmouseout="this.style.color='var(--fg-3)';this.style.background='transparent'">
                    <i data-lucide="log-out" style="width:14px;height:14px;"></i>
                </button>
            </form>
        </div>
    </aside>

    
    <div style="flex:1; display:flex; flex-direction:column; overflow:hidden; min-width:0;">

        
        <header style="height:60px; display:flex; align-items:center; justify-content:space-between; padding:0 24px; background:var(--bg); border-bottom:1px solid var(--border); flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:12px;">
                
                <button @click="mobileOpen = !mobileOpen"
                        class="lg:hidden"
                        style="padding:7px;border-radius:7px;color:var(--fg-3);border:none;background:transparent;cursor:pointer;"
                        onmouseover="this.style.background='var(--surface-2)'"
                        onmouseout="this.style.background='transparent'">
                    <i data-lucide="menu" style="width:18px;height:18px;"></i>
                </button>
                
                <button @click="toggleSidebar()"
                        class="hidden lg:flex"
                        style="padding:7px;border-radius:7px;color:var(--fg-3);border:none;background:transparent;cursor:pointer;"
                        onmouseover="this.style.background='var(--surface-2)'"
                        onmouseout="this.style.background='transparent'">
                    <i data-lucide="panel-left" style="width:18px;height:18px;"></i>
                </button>

                <div>
                    <div style="font-size:11px; color:var(--fg-3);">Job Station admin</div>
                    <div style="font-size:17px; font-weight:600; letter-spacing:-0.3px; line-height:1.2;"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></div>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:10px;">
                
                <button onclick="(function(){var d=document.documentElement.classList.toggle('dark');localStorage.setItem('jobstation-theme',d?'dark':'light');this.querySelector('[data-lucide]').setAttribute('data-lucide',d?'sun':'moon');if(window.lucide)lucide.createIcons({icons:lucide.icons});}).call(this)"
                        title="Toggle dark mode"
                        style="padding:7px;border-radius:999px;color:var(--fg-3);border:1px solid var(--border);background:var(--surface);cursor:pointer;display:flex;align-items:center;"
                        onmouseover="this.style.color='var(--fg)';this.style.borderColor='var(--border-strong)'"
                        onmouseout="this.style.color='var(--fg-3)';this.style.borderColor='var(--border)'">
                    <i data-lucide="moon" style="width:15px;height:15px;"></i>
                </button>

                
                <?php $languages = \App\Models\Language::all(); ?>
                <?php if($languages->count() > 1): ?>
                <div x-data="{ open: false }" style="position:relative;">
                    <button @click.stop="open = !open" @keydown.escape.window="open = false"
                            style="display:flex;align-items:center;gap:5px;padding:6px 10px;border-radius:7px;font-size:12px;font-weight:500;color:var(--fg-3);border:1px solid var(--border);background:var(--surface);cursor:pointer;font-family:inherit;"
                            onmouseover="this.style.color='var(--fg)';this.style.borderColor='var(--border-strong)'"
                            onmouseout="this.style.color='var(--fg-3)';this.style.borderColor='var(--border)'">
                        <i data-lucide="globe" style="width:13px;height:13px;"></i>
                        <?php echo e(strtoupper(app()->getLocale())); ?>

                        <svg width="9" height="5" viewBox="0 0 9 5" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 1l3.5 3.5L8 1"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition @click.outside="open = false"
                         style="position:absolute;right:0;top:calc(100% + 8px);background:var(--surface);border:1px solid var(--border-strong);border-radius:10px;min-width:140px;overflow:hidden;z-index:200;box-shadow:0 8px 32px rgba(0,0,0,0.35);">
                        <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('language', $lang->code)); ?>"
                           style="display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;color:<?php echo e(app()->getLocale() === $lang->code ? 'var(--accent)' : 'var(--fg-2)'); ?>;text-decoration:none;transition:.12s;font-weight:<?php echo e(app()->getLocale() === $lang->code ? '600' : '400'); ?>;"
                           onmouseover="this.style.background='var(--surface-2)'"
                           onmouseout="this.style.background='transparent'">
                            <?php if($lang->icon): ?><span style="font-size:15px;"><?php echo e($lang->icon); ?></span><?php endif; ?>
                            <?php echo e($lang->name); ?>

                            <?php if(app()->getLocale() === $lang->code): ?>
                            <svg style="margin-left:auto;width:12px;height:12px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>

                
                <span class="chip" style="background:rgba(34,197,94,0.1);color:#22C55E;border-color:rgba(34,197,94,0.3);">
                    <span class="pulse-dot" style="background:#22C55E;box-shadow:none;animation:none;width:5px;height:5px;"></span>
                    All systems normal
                </span>

                
                <?php
                    $adminId     = auth('admin')->id();
                    $unreadCount = \App\Models\AdminNotification::where('admin_id', $adminId)->where('is_read', 0)->count();
                    $bellNotifs  = \App\Models\AdminNotification::where('admin_id', $adminId)->latest()->limit(10)->get();
                ?>
                <div x-data="{ open: false }" style="position:relative;">
                    <button @click="open = !open"
                            style="position:relative;padding:7px;border-radius:7px;color:var(--fg-3);border:none;background:transparent;cursor:pointer;"
                            onmouseover="this.style.color='var(--fg)';this.style.background='var(--surface-2)'"
                            onmouseout="this.style.color='var(--fg-3)';this.style.background='transparent'">
                        <i data-lucide="bell" style="width:18px;height:18px;display:block;"></i>
                        <?php if($unreadCount > 0): ?>
                        <span style="position:absolute;top:4px;right:4px;width:16px;height:16px;border-radius:50%;background:var(--accent);color:white;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;">
                            <?php echo e($unreadCount > 9 ? '9+' : $unreadCount); ?>

                        </span>
                        <?php endif; ?>
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false" x-transition
                         style="position:absolute;right:0;top:calc(100% + 8px);width:300px;background:var(--surface);border:1px solid var(--border-strong);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,0.4);z-index:50;overflow:hidden;">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);">
                            <span style="font-size:13px;font-weight:600;">Notifications</span>
                            <?php if($unreadCount > 0): ?>
                            <form method="POST" action="<?php echo e(route('admin.notifications.read-all')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" style="font-size:11.5px;color:var(--accent);background:none;border:none;cursor:pointer;">Mark all read</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <div style="max-height:320px;overflow-y:auto;">
                            <?php $__empty_1 = true; $__currentLoopData = $bellNotifs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php $dot = ['success'=>'#22C55E','warning'=>'#F59E0B','danger'=>'#EF4444','info'=>'#60A5FA'][$n->type] ?? 'var(--fg-3)'; ?>
                            <a href="<?php echo e($n->url ?? route('admin.notifications.index')); ?>"
                               style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);text-decoration:none;background:<?php echo e(!$n->is_read ? 'rgba(47,84,235,0.05)' : 'transparent'); ?>;">
                                <span style="width:6px;height:6px;border-radius:50%;background:<?php echo e($dot); ?>;flex-shrink:0;margin-top:5px;"></span>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:12px;font-weight:500;color:var(--fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo e($n->title); ?></div>
                                    <div style="font-size:11px;color:var(--fg-3);margin-top:2px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?php echo e($n->message); ?></div>
                                    <div style="font-size:10px;color:var(--fg-4);margin-top:4px;"><?php echo e($n->created_at->diffForHumans()); ?></div>
                                </div>
                                <?php if(!$n->is_read): ?>
                                <span style="width:6px;height:6px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:5px;"></span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div style="padding:32px;text-align:center;color:var(--fg-3);font-size:12px;">
                                <i data-lucide="bell-off" style="width:24px;height:24px;margin:0 auto 8px;display:block;opacity:0.4;"></i>
                                No notifications yet
                            </div>
                            <?php endif; ?>
                        </div>
                        <div style="padding:10px 16px;border-top:1px solid var(--border);">
                            <a href="<?php echo e(route('admin.notifications.index')); ?>" style="font-size:11.5px;color:var(--accent);">View all notifications →</a>
                        </div>
                    </div>
                </div>

                
                <div x-data="{ open: false }" style="position:relative;">
                    <button @click="open = !open"
                            style="display:flex;align-items:center;gap:8px;padding:5px 8px 5px 5px;border-radius:8px;border:none;background:transparent;cursor:pointer;"
                            onmouseover="this.style.background='var(--surface-2)'"
                            onmouseout="this.style.background='transparent'">
                        <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,<?php echo e($color); ?>,<?php echo e($colors[(ord($initial)+2)%count($colors)]); ?>);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:11px;">
                            <?php echo e($initial); ?>

                        </div>
                        <span class="hidden md:block" style="font-size:13px;color:var(--fg-2);"><?php echo e($adminName); ?></span>
                        <i data-lucide="chevron-down" style="width:14px;height:14px;color:var(--fg-3);"></i>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" x-transition
                         style="position:absolute;right:0;top:calc(100% + 8px);width:180px;background:var(--surface);border:1px solid var(--border-strong);border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.4);z-index:50;padding:4px;overflow:hidden;">
                        <a href="<?php echo e(route('admin.profile')); ?>"
                           style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;font-size:13px;color:var(--fg-2);text-decoration:none;"
                           onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                           onmouseout="this.style.background='transparent';this.style.color='var(--fg-2)'">
                            <i data-lucide="user" style="width:14px;height:14px;"></i> Profile
                        </a>
                        <div style="height:1px;background:var(--border);margin:4px 0;"></div>
                        <form method="POST" action="<?php echo e(route('admin.logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 10px;border-radius:8px;font-size:13px;color:#EF4444;background:none;border:none;cursor:pointer;font-family:inherit;"
                                    onmouseover="this.style.background='rgba(239,68,68,0.08)'"
                                    onmouseout="this.style.background='none'">
                                <i data-lucide="log-out" style="width:14px;height:14px;"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        
        <?php if(session('success') || session('error') || session('info')): ?>
        <div style="padding:16px 24px 0;">
            <?php if(session('success')): ?>
                <div x-data="{ show: true }" x-show="show" x-transition
                     style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#22C55E;font-size:13px;margin-bottom:0;">
                    <i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span style="flex:1;"><?php echo e(session('success')); ?></span>
                    <button @click="show = false" style="background:none;border:none;cursor:pointer;color:inherit;padding:0;">
                        <i data-lucide="x" style="width:14px;height:14px;"></i>
                    </button>
                </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div x-data="{ show: true }" x-show="show" x-transition
                     style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#EF4444;font-size:13px;margin-bottom:0;">
                    <i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span style="flex:1;"><?php echo e(session('error')); ?></span>
                    <button @click="show = false" style="background:none;border:none;cursor:pointer;color:inherit;padding:0;">
                        <i data-lucide="x" style="width:14px;height:14px;"></i>
                    </button>
                </div>
            <?php endif; ?>
            <?php if(session('info')): ?>
                <div x-data="{ show: true }" x-show="show" x-transition
                     style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(96,165,250,0.1);border:1px solid rgba(96,165,250,0.25);color:#60A5FA;font-size:13px;margin-bottom:0;">
                    <i data-lucide="info" style="width:16px;height:16px;flex-shrink:0;"></i>
                    <span style="flex:1;"><?php echo e(session('info')); ?></span>
                    <button @click="show = false" style="background:none;border:none;cursor:pointer;color:inherit;padding:0;">
                        <i data-lucide="x" style="width:14px;height:14px;"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        
        <main style="flex:1; overflow-y:auto; padding:24px;">
            <?php echo $__env->yieldContent('content'); ?>
        </main>

    </div>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/resources/views/admin/layouts/app.blade.php ENDPATH**/ ?>