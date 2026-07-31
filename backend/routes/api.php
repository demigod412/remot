<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Flutter Mobile App)
|--------------------------------------------------------------------------
| Prefix: /api/v1
| Auth: Laravel Sanctum
*/

// ── Public auth ───────────────────────────────────────────────────────────────
Route::prefix('auth')->name('api.auth.')->middleware('throttle:10,1')->group(function () {
    Route::post('register',        [\App\Http\Controllers\Api\Auth\RegisterController::class,     'register'])->name('register');
    Route::post('login',           [\App\Http\Controllers\Api\Auth\LoginController::class,        'login'])->name('login');
    Route::post('social',          [\App\Http\Controllers\Api\Auth\SocialLoginController::class,  'handle'])->name('social');
    Route::post('forgot-password', [\App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'sendCode'])->name('forgot-password');
    Route::post('verify-code',     [\App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'verifyCode'])->name('verify-code');
    Route::post('reset-password',  [\App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'reset'])->name('reset-password');
});

// ── Public general ────────────────────────────────────────────────────────────
Route::get('settings',        [\App\Http\Controllers\Api\GeneralController::class, 'settings'])->name('api.settings');
Route::get('categories',      [\App\Http\Controllers\Api\WorkController::class,    'categories'])->name('api.categories');
Route::get('skills',          [\App\Http\Controllers\Api\SkillController::class,   'index'])->name('api.skills');
Route::get('users/{username}',[\App\Http\Controllers\Api\RatingController::class,  'publicProfile'])->name('api.users.profile');

// These must come before the parameterized works/{slug} & jobs/{id} routes to avoid collision
Route::middleware('auth:sanctum')->group(function () {
    Route::get('works/bookmarks', [\App\Http\Controllers\Api\BookmarkController::class, 'workBookmarks'])->name('api.works.bookmarks');
    Route::get('works/suggested', [\App\Http\Controllers\Api\WorkController::class,     'suggested'])->name('api.works.suggested');
    Route::get('jobs/bookmarks',  [\App\Http\Controllers\Api\BookmarkController::class, 'jobBookmarks'])->name('api.jobs.bookmarks');
});

Route::get('works',           [\App\Http\Controllers\Api\WorkController::class,    'index'])->name('api.works.index');
Route::get('works/{slug}',    [\App\Http\Controllers\Api\WorkController::class,    'show'])->name('api.works.show');
Route::get('jobs',            [\App\Http\Controllers\Api\JobController::class,     'index'])->name('api.jobs.index');
Route::get('jobs/{id}',       [\App\Http\Controllers\Api\JobController::class,     'show'])->name('api.jobs.show');

// ── Protected routes (Sanctum) ────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::delete('auth/revoke', [\App\Http\Controllers\Api\Auth\LoginController::class, 'logout'])->name('api.auth.logout');

    // Authorization (email / phone / 2FA verification)
    Route::prefix('authorization')->name('api.authorization.')->middleware('throttle:10,1')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Api\AuthorizationController::class, 'status'])->name('status');
        Route::post('send-code',    [\App\Http\Controllers\Api\AuthorizationController::class, 'sendVerifyCode'])->name('send-code');
        Route::post('verify-email', [\App\Http\Controllers\Api\AuthorizationController::class, 'verifyEmail'])->name('verify-email');
        Route::post('verify-phone', [\App\Http\Controllers\Api\AuthorizationController::class, 'verifyPhone'])->name('verify-phone');
        Route::post('verify-2fa',   [\App\Http\Controllers\Api\AuthorizationController::class, 'verifyTwoFa'])->name('verify-2fa');
    });

    // User profile + device tokens
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::get('/',                  [\App\Http\Controllers\Api\UserController::class, 'profile'])->name('profile');
        Route::put('profile',            [\App\Http\Controllers\Api\UserController::class, 'updateProfile'])->name('update');
        Route::put('password',           [\App\Http\Controllers\Api\UserController::class, 'changePassword'])->name('password');
        Route::post('avatar',            [\App\Http\Controllers\Api\UserController::class, 'uploadAvatar'])->name('avatar');
        Route::get('skills',             [\App\Http\Controllers\Api\UserController::class, 'skills'])->name('skills');
        Route::put('skills',             [\App\Http\Controllers\Api\UserController::class, 'updateSkills'])->name('skills.update');
        Route::get('kyc',                [\App\Http\Controllers\Api\UserController::class, 'kycForm'])->name('kyc');
        Route::post('kyc',               [\App\Http\Controllers\Api\UserController::class, 'submitKyc'])->name('kyc.submit');
        Route::post('device-token',      [\App\Http\Controllers\Api\UserController::class, 'registerDeviceToken'])->name('device-token.register');
        Route::delete('device-token',    [\App\Http\Controllers\Api\UserController::class, 'removeDeviceToken'])->name('device-token.remove');
    });

    // Works
    Route::post('works',              [\App\Http\Controllers\Api\WorkController::class, 'store'])->name('api.works.store');
    Route::get('my-works',            [\App\Http\Controllers\Api\WorkController::class, 'myWorks'])->name('api.works.mine');
    Route::delete('my-works/{id}',    [\App\Http\Controllers\Api\WorkController::class, 'delete'])->name('api.works.delete');
    Route::post('works/{slug}/apply', [\App\Http\Controllers\Api\WorkController::class, 'apply'])->name('api.works.apply');
    Route::delete('works/{slug}/apply', [\App\Http\Controllers\Api\WorkController::class, 'cancel'])->name('api.works.cancel');

    // Work bookmarks
    Route::post('works/{id}/bookmark',     [\App\Http\Controllers\Api\BookmarkController::class, 'toggleWorkBookmark'])->name('api.works.bookmark');

    // Submissions
    Route::prefix('submissions')->name('api.submissions.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\Api\WorkSubmissionController::class, 'mySubmissions'])->name('index');
        Route::post('{id}/proof',         [\App\Http\Controllers\Api\WorkSubmissionController::class, 'submitProof'])->name('proof');
        Route::get('my-work-submissions', [\App\Http\Controllers\Api\WorkSubmissionController::class, 'myWorkSubmissions'])->name('work-submissions');
        Route::post('{id}/approve',       [\App\Http\Controllers\Api\WorkSubmissionController::class, 'approve'])->name('approve');
        Route::post('{id}/reject',        [\App\Http\Controllers\Api\WorkSubmissionController::class, 'reject'])->name('reject');
    });

    // Wallet
    Route::prefix('wallet')->name('api.wallet.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Api\WalletController::class, 'overview'])->name('overview');
        Route::get('ledger',          [\App\Http\Controllers\Api\WalletController::class, 'ledger'])->name('ledger');
        Route::get('coin-packages',   [\App\Http\Controllers\Api\WalletController::class, 'coinPackages'])->name('packages');
        Route::get('topup/methods',   [\App\Http\Controllers\Api\WalletController::class, 'topupMethods'])->name('topup.methods');
        Route::post('topup',          [\App\Http\Controllers\Api\WalletController::class, 'topupInsert'])->name('topup');
        Route::post('topup/manual',   [\App\Http\Controllers\Api\WalletController::class, 'manualTopupConfirm'])->name('topup.manual');
        Route::get('cashout/methods', [\App\Http\Controllers\Api\WalletController::class, 'cashoutMethods'])->name('cashout.methods');
        Route::post('cashout/preview',[\App\Http\Controllers\Api\WalletController::class, 'cashoutPreview'])->name('cashout.preview');
        Route::post('cashout',        [\App\Http\Controllers\Api\WalletController::class, 'cashoutStore'])->name('cashout');
        Route::get('cashouts',        [\App\Http\Controllers\Api\WalletController::class, 'cashoutHistory'])->name('cashouts');
        Route::post('send',           [\App\Http\Controllers\Api\WalletController::class, 'send'])->name('send');
    });

    // Referral
    Route::prefix('referral')->name('api.referral.')->group(function () {
        Route::get('/',        [\App\Http\Controllers\Api\ReferralController::class, 'overview'])->name('overview');
        Route::get('referred', [\App\Http\Controllers\Api\ReferralController::class, 'referred'])->name('referred');
        Route::get('earnings', [\App\Http\Controllers\Api\ReferralController::class, 'earnings'])->name('earnings');
    });

    // Help Desk
    Route::prefix('tickets')->name('api.tickets.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Api\HelpDeskController::class, 'index'])->name('index');
        Route::post('/',              [\App\Http\Controllers\Api\HelpDeskController::class, 'store'])->name('store');
        Route::get('{number}',        [\App\Http\Controllers\Api\HelpDeskController::class, 'show'])->name('show');
        Route::post('{number}/reply', [\App\Http\Controllers\Api\HelpDeskController::class, 'reply'])->name('reply');
        Route::post('{number}/close', [\App\Http\Controllers\Api\HelpDeskController::class, 'close'])->name('close');
    });

    // Jobs
    Route::post('jobs',                   [\App\Http\Controllers\Api\JobController::class,      'store'])->name('api.jobs.store');
    Route::post('jobs/{id}/bookmark',     [\App\Http\Controllers\Api\BookmarkController::class, 'toggleJobBookmark'])->name('api.jobs.bookmark');
    Route::post('jobs/{id}/apply',        [\App\Http\Controllers\Api\JobController::class,      'apply'])->name('api.jobs.apply');
    Route::delete('jobs/{id}/apply',      [\App\Http\Controllers\Api\JobController::class,      'cancelApplication'])->name('api.jobs.apply.cancel');
    Route::get('my-applications',         [\App\Http\Controllers\Api\JobController::class,      'myApplications'])->name('api.jobs.applications');

    // Contracts
    Route::prefix('contracts')->name('api.contracts.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Api\ContractController::class, 'index'])->name('index');
        Route::get('sent',           [\App\Http\Controllers\Api\ContractController::class, 'sent'])->name('sent');
        Route::get('received',       [\App\Http\Controllers\Api\ContractController::class, 'received'])->name('received');
        Route::get('{id}',           [\App\Http\Controllers\Api\ContractController::class, 'show'])->name('show');
        Route::post('/',             [\App\Http\Controllers\Api\ContractController::class, 'store'])->name('store');
        Route::post('{id}/accept',   [\App\Http\Controllers\Api\ContractController::class, 'accept'])->name('accept');
        Route::post('{id}/decline',  [\App\Http\Controllers\Api\ContractController::class, 'decline'])->name('decline');
        Route::post('{id}/cancel',   [\App\Http\Controllers\Api\ContractController::class, 'cancel'])->name('cancel');
        Route::post('{id}/submit',   [\App\Http\Controllers\Api\ContractController::class, 'submit'])->name('submit');
        Route::post('{id}/approve',  [\App\Http\Controllers\Api\ContractController::class, 'approve'])->name('approve');
        Route::post('{id}/dispute',  [\App\Http\Controllers\Api\ContractController::class, 'dispute'])->name('dispute');
        Route::post('{id}/milestones/{milestoneId}/submit',  [\App\Http\Controllers\Api\ContractController::class, 'submitMilestone'])->name('milestones.submit');
        Route::post('{id}/milestones/{milestoneId}/approve', [\App\Http\Controllers\Api\ContractController::class, 'approveMilestone'])->name('milestones.approve');
        Route::post('{id}/milestones/{milestoneId}/dispute', [\App\Http\Controllers\Api\ContractController::class, 'disputeMilestone'])->name('milestones.dispute');
        Route::post('{id}/rate',     [\App\Http\Controllers\Api\RatingController::class,   'rateContract'])->name('rate');
        Route::get('{id}/my-rating', [\App\Http\Controllers\Api\RatingController::class,   'myContractRating'])->name('my-rating');
    });

    // Notifications
    Route::get('notifications',             [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('api.notifications');
    Route::post('notifications/read-all',   [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead'])->name('api.notifications.readall');
    Route::post('notifications/mark-read',  [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead'])->name('api.notifications.read'); // legacy alias
    Route::post('notifications/{id}/read',  [\App\Http\Controllers\Api\NotificationController::class, 'markRead'])->whereNumber('id')->name('api.notifications.read.one');

});
