<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
| Prefix: /admin  |  Middleware: web  |  Name prefix: admin.
*/

// ── Auth (guests only) ───────────────────────────────────────────────────────
Route::middleware('guest.admin')->group(function () {
    Route::get('login',  [\App\Http\Controllers\Admin\Auth\LoginController::class, 'showLogin'])->name('login');
    Route::post('login', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'login'])->name('login.submit')->middleware('throttle:6,1');
});

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('admin')->group(function () {

    Route::post('logout', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('profile',  [\App\Http\Controllers\Admin\DashboardController::class, 'profile'])->name('profile');
    Route::put('profile',  [\App\Http\Controllers\Admin\DashboardController::class, 'updateProfile'])->name('profile.update');

    // Admin Notifications (bell icon)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\Admin\DashboardController::class, 'notifications'])->name('index');
        Route::post('{id}/read',   [\App\Http\Controllers\Admin\DashboardController::class, 'markRead'])->name('read');
        Route::post('read-all',    [\App\Http\Controllers\Admin\DashboardController::class, 'readAll'])->name('read-all');
    });

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        // Forgive worker reliability strikes (audited).
        Route::post('{id}/clear-strikes', [\App\Http\Controllers\Admin\UserController::class, 'clearStrikes'])
            ->whereNumber('id')->name('clear-strikes');
        Route::get('/',                          [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
        Route::get('kyc',                        [\App\Http\Controllers\Admin\UserController::class, 'kycList'])->name('kyc');
        Route::get('{id}',                       [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
        Route::put('{id}',                       [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
        Route::post('{id}/status',               [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('status');
        Route::post('{id}/balance',              [\App\Http\Controllers\Admin\UserController::class, 'adjustBalance'])->name('balance');
        Route::post('{id}/kyc/approve',          [\App\Http\Controllers\Admin\UserController::class, 'kycApprove'])->name('kyc.approve');
        Route::post('{id}/kyc/reject',           [\App\Http\Controllers\Admin\UserController::class, 'kycReject'])->name('kyc.reject');
        Route::post('{id}/kyc/request-docs',     [\App\Http\Controllers\Admin\UserController::class, 'kycRequestDocs'])->name('kyc.request-docs');
        Route::post('{id}/kyc/approve-doc',      [\App\Http\Controllers\Admin\UserController::class, 'kycApproveDoc'])->name('kyc.approve-doc');
        Route::get('{id}/login-as',              [\App\Http\Controllers\Admin\UserController::class, 'loginAsUser'])->name('login-as');
        Route::get('notify/all',                 [\App\Http\Controllers\Admin\UserController::class, 'bulkNotifyForm'])->name('notify.bulk');
        Route::post('notify/all',                [\App\Http\Controllers\Admin\UserController::class, 'sendBulkNotification'])->name('notify.bulk.send');
        Route::get('{id}/notify',                [\App\Http\Controllers\Admin\UserController::class, 'notifyForm'])->name('notify');
        Route::post('{id}/notify',               [\App\Http\Controllers\Admin\UserController::class, 'sendNotification'])->name('notify.send');
    });

    // Works
    Route::prefix('works')->name('works.')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Admin\WorkController::class, 'index'])->name('index');
        Route::get('pending',       [\App\Http\Controllers\Admin\WorkController::class, 'pending'])->name('pending');
        Route::get('create',        [\App\Http\Controllers\Admin\WorkController::class, 'create'])->name('create');

        // Bulk import. Declared BEFORE the {id} routes: 'import' would otherwise be
        // captured as an id by works/{id} and 404 on a findOrFail.
        Route::get('import',          [\App\Http\Controllers\Admin\WorkImportController::class, 'form'])->name('import');
        Route::get('import/template', [\App\Http\Controllers\Admin\WorkImportController::class, 'template'])->name('import.template');
        Route::post('import',         [\App\Http\Controllers\Admin\WorkImportController::class, 'import'])->name('import.store');
        Route::post('import/json',    [\App\Http\Controllers\Admin\WorkImportController::class, 'importJson'])->name('import.json');
        Route::post('/',            [\App\Http\Controllers\Admin\WorkController::class, 'store'])->name('store');
        Route::get('{id}',          [\App\Http\Controllers\Admin\WorkController::class, 'show'])->name('show');
        Route::get('{id}/edit',     [\App\Http\Controllers\Admin\WorkController::class, 'edit'])->name('edit');
        Route::put('{id}',          [\App\Http\Controllers\Admin\WorkController::class, 'update'])->name('update');
        Route::delete('{id}',       [\App\Http\Controllers\Admin\WorkController::class, 'destroy'])->name('delete');
        Route::post('{id}/approve', [\App\Http\Controllers\Admin\WorkController::class, 'approve'])->name('approve');
        Route::post('{id}/reject',  [\App\Http\Controllers\Admin\WorkController::class, 'reject'])->name('reject');
        Route::post('{id}/feature', [\App\Http\Controllers\Admin\WorkController::class, 'toggleFeature'])->name('feature');
        Route::post('{id}/extend',  [\App\Http\Controllers\Admin\WorkController::class, 'extendSlots'])->name('extend');
        Route::post('{id}/repost',  [\App\Http\Controllers\Admin\WorkController::class, 'repost'])->name('repost');
    });

    /*
    |--------------------------------------------------------------------------
    | Membership Applications (invite-only intake queue)
    |--------------------------------------------------------------------------
    */
    Route::prefix('membership')->name('membership.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\Admin\MembershipApplicationController::class, 'index'])->name('index');
        Route::get('{id}',         [\App\Http\Controllers\Admin\MembershipApplicationController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('{id}/approve', [\App\Http\Controllers\Admin\MembershipApplicationController::class, 'approve'])->whereNumber('id')->name('approve');
        Route::post('{id}/reject',  [\App\Http\Controllers\Admin\MembershipApplicationController::class, 'reject'])->whereNumber('id')->name('reject');
    });

    /*
    |--------------------------------------------------------------------------
    | Task Review (two-axis application + delivery lifecycle)
    |--------------------------------------------------------------------------
    */
    Route::prefix('task-review')->name('task-review.')->group(function () {
        Route::get('/',    [\App\Http\Controllers\Admin\TaskReviewController::class, 'index'])->name('index');
        Route::get('{id}', [\App\Http\Controllers\Admin\TaskReviewController::class, 'show'])->whereNumber('id')->name('show');

        Route::post('{id}/application/approve', [\App\Http\Controllers\Admin\TaskReviewController::class, 'approveApplication'])->whereNumber('id')->name('application.approve');
        Route::post('{id}/application/reject',  [\App\Http\Controllers\Admin\TaskReviewController::class, 'rejectApplication'])->whereNumber('id')->name('application.reject');
        Route::post('{id}/revision',            [\App\Http\Controllers\Admin\TaskReviewController::class, 'requestRevision'])->whereNumber('id')->name('revision');
        Route::post('{id}/delivery/approve',    [\App\Http\Controllers\Admin\TaskReviewController::class, 'approveSubmission'])->whereNumber('id')->name('delivery.approve');
        Route::post('{id}/delivery/reject',     [\App\Http\Controllers\Admin\TaskReviewController::class, 'rejectSubmission'])->whereNumber('id')->name('delivery.reject');
    });

    // Audit log
    Route::get('audit-log', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-log');

    // The legacy /admin/submissions screens are gone. Their approve path used a raw
    // increment on coin_balance plus a hand-rolled ledger row, bypassing category
    // commission and CoinService entirely, so any approval made through them
    // produced figures that could not reconcile. Task Review replaces them.
    //
    // Redirects rather than a bare 404, because these URLs have been in the admin
    // sidebar for the whole life of the install and will be bookmarked.
    Route::redirect('submissions', 'admin/task-review');
    Route::redirect('submissions/{id}', 'admin/task-review/{id}');

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/',                          [\App\Http\Controllers\Admin\WorkCategoryController::class, 'index'])->name('index');
        Route::post('/',                         [\App\Http\Controllers\Admin\WorkCategoryController::class, 'store'])->name('store');
        Route::put('{id}',                       [\App\Http\Controllers\Admin\WorkCategoryController::class, 'update'])->name('update');
        Route::delete('{id}',                    [\App\Http\Controllers\Admin\WorkCategoryController::class, 'destroy'])->name('delete');
        Route::post('{id}/toggle',               [\App\Http\Controllers\Admin\WorkCategoryController::class, 'toggleStatus'])->name('toggle');
        Route::get('{id}/subcategories',         [\App\Http\Controllers\Admin\WorkCategoryController::class, 'subcategories'])->name('subcategories');
        Route::post('{id}/subcategories',        [\App\Http\Controllers\Admin\WorkCategoryController::class, 'storeSubcategory'])->name('subcategories.store');
        Route::put('subcategories/{sid}',        [\App\Http\Controllers\Admin\WorkCategoryController::class, 'updateSubcategory'])->name('subcategories.update');
        Route::delete('subcategories/{sid}',     [\App\Http\Controllers\Admin\WorkCategoryController::class, 'destroySubcategory'])->name('subcategories.delete');
    });

    // Coin Top-ups
    Route::prefix('topups')->name('topups.')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Admin\CoinTopupController::class, 'index'])->name('index');
        Route::get('{id}',          [\App\Http\Controllers\Admin\CoinTopupController::class, 'show'])->name('show');
        Route::post('{id}/approve', [\App\Http\Controllers\Admin\CoinTopupController::class, 'approve'])->name('approve');
        Route::post('{id}/reject',  [\App\Http\Controllers\Admin\CoinTopupController::class, 'reject'])->name('reject');
    });

    // Cashouts
    Route::prefix('cashouts')->name('cashouts.')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Admin\CashoutController::class, 'index'])->name('index');
        Route::get('export',        [\App\Http\Controllers\Admin\CashoutController::class, 'export'])->name('export');
        Route::get('{id}',          [\App\Http\Controllers\Admin\CashoutController::class, 'show'])->name('show');
        Route::post('{id}/approve', [\App\Http\Controllers\Admin\CashoutController::class, 'approve'])->name('approve');
        Route::post('{id}/reject',  [\App\Http\Controllers\Admin\CashoutController::class, 'reject'])->name('reject');
    });

    // Ledger
    Route::prefix('ledger')->name('ledger.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LedgerController::class, 'index'])->name('index');
    });

    // Coin Packages
    Route::prefix('coin-packages')->name('coin-packages.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Admin\CoinPackageController::class, 'index'])->name('index');
        Route::post('/',             [\App\Http\Controllers\Admin\CoinPackageController::class, 'store'])->name('store');
        Route::put('{id}',           [\App\Http\Controllers\Admin\CoinPackageController::class, 'update'])->name('update');
        Route::delete('{id}',        [\App\Http\Controllers\Admin\CoinPackageController::class, 'destroy'])->name('delete');
        Route::post('{id}/toggle',   [\App\Http\Controllers\Admin\CoinPackageController::class, 'toggleStatus'])->name('toggle');
    });

    // Payout Methods
    Route::prefix('payout-methods')->name('payout-methods.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\PayoutMethodController::class, 'index'])->name('index');
        Route::get('create',      [\App\Http\Controllers\Admin\PayoutMethodController::class, 'create'])->name('create');
        Route::post('/',          [\App\Http\Controllers\Admin\PayoutMethodController::class, 'store'])->name('store');
        Route::get('{id}/edit',   [\App\Http\Controllers\Admin\PayoutMethodController::class, 'edit'])->name('edit');
        Route::put('{id}',        [\App\Http\Controllers\Admin\PayoutMethodController::class, 'update'])->name('update');
        Route::post('{id}/toggle',[\App\Http\Controllers\Admin\PayoutMethodController::class, 'toggleStatus'])->name('toggle');
    });

    // Payment Channels
    Route::prefix('payment-channels')->name('payment-channels.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\PaymentChannelController::class, 'index'])->name('index');
        Route::get('{id}/edit',   [\App\Http\Controllers\Admin\PaymentChannelController::class, 'edit'])->name('edit');
        Route::put('{id}',        [\App\Http\Controllers\Admin\PaymentChannelController::class, 'update'])->name('update');
        Route::post('{id}/toggle',[\App\Http\Controllers\Admin\PaymentChannelController::class, 'toggleStatus'])->name('toggle');
    });

    // Support Tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\HelpDeskController::class, 'index'])->name('index');
        Route::get('{id}',        [\App\Http\Controllers\Admin\HelpDeskController::class, 'show'])->name('show');
        Route::post('{id}/reply', [\App\Http\Controllers\Admin\HelpDeskController::class, 'reply'])->name('reply');
        Route::post('{id}/close', [\App\Http\Controllers\Admin\HelpDeskController::class, 'close'])->name('close');
        Route::delete('{id}',     [\App\Http\Controllers\Admin\HelpDeskController::class, 'destroy'])->name('delete');
    });

    // Contracts
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\Admin\ContractController::class, 'index'])->name('index');
        Route::get('{id}',                [\App\Http\Controllers\Admin\ContractController::class, 'show'])->name('show');
        Route::post('{id}/resolve',       [\App\Http\Controllers\Admin\ContractController::class, 'resolve'])->name('resolve');
        Route::post('{id}/force-cancel',  [\App\Http\Controllers\Admin\ContractController::class, 'forceCancel'])->name('force-cancel');
    });

    // Boost Requests
    Route::prefix('boost-requests')->name('boost-requests.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Admin\BoostRequestController::class, 'index'])->name('index');
        Route::post('{id}/approve',  [\App\Http\Controllers\Admin\BoostRequestController::class, 'approve'])->name('approve');
        Route::post('{id}/reject',   [\App\Http\Controllers\Admin\BoostRequestController::class, 'reject'])->name('reject');
    });

    // Skills
    Route::prefix('skills')->name('skills.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\Admin\SkillController::class, 'index'])->name('index');
        Route::post('/',           [\App\Http\Controllers\Admin\SkillController::class, 'store'])->name('store');
        Route::put('{id}',         [\App\Http\Controllers\Admin\SkillController::class, 'update'])->name('update');
        Route::post('{id}/toggle', [\App\Http\Controllers\Admin\SkillController::class, 'toggleStatus'])->name('toggle');
        Route::delete('{id}',      [\App\Http\Controllers\Admin\SkillController::class, 'destroy'])->name('delete');
    });

    // Job Listings
    Route::middleware('feature:enable_job_board')->prefix('jobs/listings')->name('jobs.listings.')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Admin\JobListingController::class, 'index'])->name('index');
        Route::get('{id}',          [\App\Http\Controllers\Admin\JobListingController::class, 'show'])->name('show');
        Route::post('{id}/approve', [\App\Http\Controllers\Admin\JobListingController::class, 'approve'])->name('approve');
        Route::post('{id}/reject',  [\App\Http\Controllers\Admin\JobListingController::class, 'reject'])->name('reject');
        Route::post('{id}/feature',      [\App\Http\Controllers\Admin\JobListingController::class, 'toggleFeature'])->name('feature');
        Route::post('{id}/toggle-kyc',   [\App\Http\Controllers\Admin\JobListingController::class, 'toggleKyc'])->name('toggle-kyc');
        Route::delete('{id}',            [\App\Http\Controllers\Admin\JobListingController::class, 'destroy'])->name('delete');
    });

    // Referrals
    Route::prefix('referrals')->name('referrals.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReferralController::class, 'index'])->name('index');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
        Route::get('transactions',   [\App\Http\Controllers\Admin\ReportController::class, 'transactions'])->name('transactions');
        Route::get('logins',         [\App\Http\Controllers\Admin\ReportController::class, 'logins'])->name('logins');
        Route::get('notifications',  [\App\Http\Controllers\Admin\ReportController::class, 'notifications'])->name('notifications');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('general',    [\App\Http\Controllers\Admin\SettingsController::class, 'general'])->name('general');
        Route::post('general',   [\App\Http\Controllers\Admin\SettingsController::class, 'updateGeneral'])->name('general.update');
        Route::get('mail',       [\App\Http\Controllers\Admin\SettingsController::class, 'mailSettings'])->name('mail');
        Route::post('mail',      [\App\Http\Controllers\Admin\SettingsController::class, 'updateMailSettings'])->name('mail.update');
        Route::post('mail/test', [\App\Http\Controllers\Admin\SettingsController::class, 'sendTestMail'])->name('mail.test');
        Route::get('notification',[\App\Http\Controllers\Admin\SettingsController::class, 'notification'])->name('notification');
        Route::post('notification',[\App\Http\Controllers\Admin\SettingsController::class, 'updateNotification'])->name('notification.update');
        Route::get('logo',       [\App\Http\Controllers\Admin\SettingsController::class, 'logoIcon'])->name('logo');
        Route::post('logo',      [\App\Http\Controllers\Admin\SettingsController::class, 'updateLogoIcon'])->name('logo.update');
        Route::get('css',        [\App\Http\Controllers\Admin\SettingsController::class, 'customCss'])->name('css');
        Route::post('css',       [\App\Http\Controllers\Admin\SettingsController::class, 'updateCustomCss'])->name('css.update');
        Route::get('social',     [\App\Http\Controllers\Admin\SettingsController::class, 'socialLogin'])->name('social');
        Route::post('social',    [\App\Http\Controllers\Admin\SettingsController::class, 'updateSocialLogin'])->name('social.update');
        Route::get('links',      [\App\Http\Controllers\Admin\SettingsController::class, 'links'])->name('links');
        Route::post('links',     [\App\Http\Controllers\Admin\SettingsController::class, 'updateLinks'])->name('links.update');
        Route::get('firebase',   [\App\Http\Controllers\Admin\SettingsController::class, 'firebase'])->name('firebase');
        Route::post('firebase',  [\App\Http\Controllers\Admin\SettingsController::class, 'updateFirebase'])->name('firebase.update');
        Route::get('license',    [\App\Http\Controllers\Admin\SettingsController::class, 'license'])->name('license');
        Route::post('license/reverify', [\App\Http\Controllers\Admin\SettingsController::class, 'reverifyLicense'])->name('license.reverify');
    });

    Route::get('notification-events',  [\App\Http\Controllers\Admin\SettingsController::class, 'notifEvents'])->name('notif-events');
    Route::post('notification-events', [\App\Http\Controllers\Admin\SettingsController::class, 'updateNotifEvents'])->name('notif-events.update');

    // Notification Templates
    Route::prefix('notification-templates')->name('notif-templates.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'index'])->name('index');
        Route::get('{id}/edit',   [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'edit'])->name('edit');
        Route::put('{id}',        [\App\Http\Controllers\Admin\NotificationTemplateController::class, 'update'])->name('update');
    });

    // Languages
    Route::prefix('languages')->name('languages.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\LanguageController::class, 'index'])->name('index');
        Route::post('/',          [\App\Http\Controllers\Admin\LanguageController::class, 'store'])->name('store');
        Route::get('{id}/edit',   [\App\Http\Controllers\Admin\LanguageController::class, 'edit'])->name('edit');
        Route::put('{id}',        [\App\Http\Controllers\Admin\LanguageController::class, 'update'])->name('update');
        Route::delete('{id}',     [\App\Http\Controllers\Admin\LanguageController::class, 'destroy'])->name('delete');
        Route::post('{id}/default',[\App\Http\Controllers\Admin\LanguageController::class, 'setDefault'])->name('default');
    });

    // Plugins
    Route::prefix('plugins')->name('plugins.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\PluginController::class, 'index'])->name('index');
        Route::put('{id}',        [\App\Http\Controllers\Admin\PluginController::class, 'update'])->name('update');
        Route::post('{id}/toggle',[\App\Http\Controllers\Admin\PluginController::class, 'toggleStatus'])->name('toggle');
    });

    // Pages
    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\PageController::class, 'index'])->name('index');
        Route::get('{id}/edit',   [\App\Http\Controllers\Admin\PageController::class, 'edit'])->name('edit');
        Route::put('{id}',        [\App\Http\Controllers\Admin\PageController::class, 'update'])->name('update');
    });

    // Content Sections
    Route::prefix('content')->name('content.')->group(function () {
        Route::get('/',         [\App\Http\Controllers\Admin\ContentSectionController::class, 'index'])->name('index');
        Route::get('{id}/edit', [\App\Http\Controllers\Admin\ContentSectionController::class, 'edit'])->name('edit');
        Route::put('{id}',      [\App\Http\Controllers\Admin\ContentSectionController::class, 'update'])->name('update');
    });

    // Subscribers
    Route::prefix('subscribers')->name('subscribers.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Admin\SubscriberController::class, 'index'])->name('index');
        Route::delete('{id}',     [\App\Http\Controllers\Admin\SubscriberController::class, 'destroy'])->name('delete');
        Route::post('send-email', [\App\Http\Controllers\Admin\SubscriberController::class, 'sendEmail'])->name('send-email');
    });

});
