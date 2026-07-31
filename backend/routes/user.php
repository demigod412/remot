<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Dashboard Routes
|--------------------------------------------------------------------------
| Prefix: /dashboard
| Middleware: web
| Name prefix: user.
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [\App\Http\Controllers\User\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [\App\Http\Controllers\User\Auth\LoginController::class, 'login'])->name('login.submit')->middleware('throttle:6,1');
    Route::get('register', [\App\Http\Controllers\User\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [\App\Http\Controllers\User\Auth\RegisterController::class, 'register'])->name('register.submit')->middleware('throttle:6,1');
    Route::get('forgot-password', [\App\Http\Controllers\User\Auth\ForgotPasswordController::class, 'showForm'])->name('forgot-password');
    Route::post('forgot-password', [\App\Http\Controllers\User\Auth\ForgotPasswordController::class, 'sendCode'])->name('forgot-password.send')->middleware('throttle:6,1');
    Route::get('verify-code', [\App\Http\Controllers\User\Auth\ForgotPasswordController::class, 'showVerifyForm'])->name('verify-code');
    Route::post('verify-code', [\App\Http\Controllers\User\Auth\ForgotPasswordController::class, 'verifyCode'])->name('verify-code.submit')->middleware('throttle:6,1');
    Route::get('reset-password', [\App\Http\Controllers\User\Auth\ResetPasswordController::class, 'showForm'])->name('reset-password');
    Route::post('reset-password', [\App\Http\Controllers\User\Auth\ResetPasswordController::class, 'reset'])->name('reset-password.submit')->middleware('throttle:6,1');

    // Social login
    Route::get('auth/{provider}', [\App\Http\Controllers\User\Auth\SocialiteController::class, 'redirect'])->name('social.redirect');
    Route::get('auth/{provider}/callback', [\App\Http\Controllers\User\Auth\SocialiteController::class, 'callback'])->name('social.callback');
});

// Authenticated routes
Route::middleware('auth')->group(function () {

    Route::post('logout', [\App\Http\Controllers\User\Auth\LoginController::class, 'logout'])->name('logout');

    // Authorization (verification steps)
    Route::get('authorization', [\App\Http\Controllers\User\AuthorizationController::class, 'showForm'])->name('authorization');
    Route::post('authorization/send-code', [\App\Http\Controllers\User\AuthorizationController::class, 'sendVerifyCode'])->name('authorization.send-code')->middleware('throttle:6,1');
    Route::post('authorization/verify-email', [\App\Http\Controllers\User\AuthorizationController::class, 'verifyEmail'])->name('authorization.verify-email')->middleware('throttle:6,1');
    Route::post('authorization/verify-phone', [\App\Http\Controllers\User\AuthorizationController::class, 'verifyPhone'])->name('authorization.verify-phone')->middleware('throttle:6,1');
    Route::post('authorization/verify-2fa', [\App\Http\Controllers\User\AuthorizationController::class, 'verifyTwoFa'])->name('authorization.verify-2fa')->middleware('throttle:6,1');

    // Onboarding
    Route::get('complete-profile', [\App\Http\Controllers\User\DashboardController::class, 'onboarding'])->name('onboarding');
    Route::post('complete-profile', [\App\Http\Controllers\User\DashboardController::class, 'completeOnboarding'])->name('onboarding.submit');

    // Dashboard
    Route::get('/', [\App\Http\Controllers\User\DashboardController::class, 'index'])->name('dashboard');

    // Browse Works (dashboard-internal)
    Route::get('browse-works', [\App\Http\Controllers\User\WorkBrowseController::class, 'index'])->name('browse.works');
    Route::post('browse-works/{id}/bookmark', [\App\Http\Controllers\User\WorkBookmarkController::class, 'toggle'])->name('works.bookmark');
    Route::get('browse-works/saved', [\App\Http\Controllers\User\WorkBookmarkController::class, 'index'])->name('works.saved');
    // In-dashboard work detail + start (keeps the worker inside the dashboard)
    Route::get('browse-works/{slug}', [\App\Http\Controllers\User\WorkBrowseController::class, 'show'])->name('browse.works.show');
    Route::post('browse-works/{slug}/start', [\App\Http\Controllers\User\WorkBrowseController::class, 'start'])->name('browse.works.start');

    // Notifications
    Route::post('notifications/read-all', function () {
        \App\Models\UserNotification::where('user_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        return back();
    })->name('notifications.read-all');

    // Subcategory AJAX (used by work create form + public browse filter)
    Route::get('works/subcategories', function (\Illuminate\Http\Request $request) {
        return \App\Models\WorkSubcategory::where('category_id', $request->category_id)
            ->where('status', 1)
            ->get(['id', 'name']);
    })->name('works.subcategories');

    // My Works (posted)
    Route::prefix('works')->name('works.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\WorkController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\User\WorkController::class, 'create'])->middleware('kyc')->name('create');
        Route::post('/', [\App\Http\Controllers\User\WorkController::class, 'store'])->middleware('kyc')->name('store');
        Route::get('{id}', [\App\Http\Controllers\User\WorkController::class, 'show'])->name('show');
        Route::delete('{id}', [\App\Http\Controllers\User\WorkController::class, 'delete'])->name('delete');
        Route::post('{id}/boost', [\App\Http\Controllers\User\WorkController::class, 'boost'])->name('boost');
        // Submissions on my works
        Route::get('{id}/submissions', [\App\Http\Controllers\User\WorkSubmissionController::class, 'myWorkSubmissions'])->name('submissions');
        Route::get('{workId}/submissions/{submissionId}', [\App\Http\Controllers\User\WorkSubmissionController::class, 'reviewProof'])->name('submissions.review');
        Route::post('{workId}/submissions/{submissionId}/approve', [\App\Http\Controllers\User\WorkSubmissionController::class, 'approveProof'])->name('submissions.approve');
        Route::post('{workId}/submissions/{submissionId}/reject', [\App\Http\Controllers\User\WorkSubmissionController::class, 'rejectProof'])->name('submissions.reject');
        Route::post('{id}/bulk-approve', [\App\Http\Controllers\User\WorkSubmissionController::class, 'bulkApprove'])->name('submissions.bulk-approve');
    });

    // My Submissions (applied to)
    Route::prefix('submissions')->name('submissions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\WorkSubmissionController::class, 'mySubmissions'])->name('index');
        Route::get('{id}/proof', [\App\Http\Controllers\User\WorkSubmissionController::class, 'showProofForm'])->name('proof');
        Route::post('{id}/proof', [\App\Http\Controllers\User\WorkSubmissionController::class, 'submitProof'])->name('proof.submit');
    });

    // Wallet
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\WalletController::class, 'overview'])->name('overview');
        Route::get('ledger', [\App\Http\Controllers\User\WalletController::class, 'ledger'])->name('ledger');
        Route::get('topup', [\App\Http\Controllers\User\WalletController::class, 'topupMethods'])->name('topup');
        Route::post('topup', [\App\Http\Controllers\User\WalletController::class, 'topupInsert'])->name('topup.insert');
        Route::get('topup/manual-confirm', [\App\Http\Controllers\User\WalletController::class, 'manualTopupConfirm'])->name('topup.manual-confirm');
        Route::post('topup/{id}/update', [\App\Http\Controllers\User\WalletController::class, 'manualTopupUpdate'])->name('topup.update');
        Route::get('cashout/history', [\App\Http\Controllers\User\WalletController::class, 'cashoutHistory'])->name('cashout.history');
        Route::get('cashout/{id}/receipt', [\App\Http\Controllers\User\WalletController::class, 'cashoutReceipt'])->name('cashout.receipt');
        Route::get('cashout', [\App\Http\Controllers\User\WalletController::class, 'cashoutMethods'])->middleware('kyc')->name('cashout');
        Route::post('cashout/preview', [\App\Http\Controllers\User\WalletController::class, 'cashoutPreview'])->middleware('kyc')->name('cashout.preview');
        Route::post('cashout/submit', [\App\Http\Controllers\User\WalletController::class, 'cashoutSubmit'])->middleware('kyc')->name('cashout.submit');
        Route::get('payout-accounts', [\App\Http\Controllers\User\WalletController::class, 'payoutAccounts'])->name('payout-accounts');
        Route::post('payout-accounts', [\App\Http\Controllers\User\WalletController::class, 'payoutAccountStore'])->name('payout-accounts.store');
        Route::delete('payout-accounts/{id}', [\App\Http\Controllers\User\WalletController::class, 'payoutAccountDelete'])->name('payout-accounts.delete');
        Route::post('payout-accounts/{id}/default', [\App\Http\Controllers\User\WalletController::class, 'payoutAccountSetDefault'])->name('payout-accounts.default');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\ProfileController::class, 'settings'])->name('settings');
        Route::put('/', [\App\Http\Controllers\User\ProfileController::class, 'update'])->name('update');
        Route::get('password', [\App\Http\Controllers\User\ProfileController::class, 'password'])->name('password');
        Route::put('password', [\App\Http\Controllers\User\ProfileController::class, 'updatePassword'])->name('password.update');
        Route::get('two-factor', [\App\Http\Controllers\User\ProfileController::class, 'twoFactor'])->name('2fa');
        Route::post('two-factor/enable', [\App\Http\Controllers\User\ProfileController::class, 'enableTwoFactor'])->name('2fa.enable');
        Route::post('two-factor/confirm', [\App\Http\Controllers\User\ProfileController::class, 'confirmTwoFactor'])->name('2fa.confirm');
        Route::post('two-factor/disable', [\App\Http\Controllers\User\ProfileController::class, 'disableTwoFactor'])->name('2fa.disable');
        Route::get('kyc', [\App\Http\Controllers\User\ProfileController::class, 'kyc'])->name('kyc');
        Route::post('kyc', [\App\Http\Controllers\User\ProfileController::class, 'submitKyc'])->name('kyc.submit');
        Route::post('skills', [\App\Http\Controllers\User\ProfileController::class, 'updateSkills'])->name('skills.update');
    });

    // Referral
    Route::prefix('referral')->name('referral.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\ReferralController::class, 'index'])->name('index');
        Route::get('referred', [\App\Http\Controllers\User\ReferralController::class, 'referred'])->name('referred');
        Route::get('earnings', [\App\Http\Controllers\User\ReferralController::class, 'earnings'])->name('earnings');
    });

    // ── Job Listings (employer side) ─────────────────────────────
    Route::prefix('jobs/listings')->name('jobs.listings.')->group(function () {
        Route::get('/',                                       [\App\Http\Controllers\User\JobListingController::class, 'index'])->name('index');
        Route::get('create',                                  [\App\Http\Controllers\User\JobListingController::class, 'create'])->name('create');
        Route::post('/',                                      [\App\Http\Controllers\User\JobListingController::class, 'store'])->name('store');
        Route::get('{id}',                                    [\App\Http\Controllers\User\JobListingController::class, 'show'])->name('show');
        Route::get('{id}/edit',                               [\App\Http\Controllers\User\JobListingController::class, 'edit'])->name('edit');
        Route::put('{id}',                                    [\App\Http\Controllers\User\JobListingController::class, 'update'])->name('update');
        Route::delete('{id}',                                 [\App\Http\Controllers\User\JobListingController::class, 'destroy'])->name('delete');
        Route::post('{id}/close',                             [\App\Http\Controllers\User\JobListingController::class, 'close'])->name('close');
        Route::post('{id}/boost',                             [\App\Http\Controllers\User\JobListingController::class, 'boost'])->name('boost');
        Route::get('{listingId}/applications/{appId}',        [\App\Http\Controllers\User\JobListingController::class, 'reviewApplication'])->name('applications.review');
        Route::post('{listingId}/applications/{appId}/status',[\App\Http\Controllers\User\JobListingController::class, 'updateApplicationStatus'])->name('applications.status');
    });

    // ── Contracts ────────────────────────────────────────────────
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('create',              [\App\Http\Controllers\User\ContractController::class, 'create'])->name('create');
        Route::post('/',                  [\App\Http\Controllers\User\ContractController::class, 'store'])->name('store');
        Route::get('sent',                [\App\Http\Controllers\User\ContractController::class, 'sent'])->name('sent');
        Route::get('received',            [\App\Http\Controllers\User\ContractController::class, 'received'])->name('received');
        Route::get('{id}',                [\App\Http\Controllers\User\ContractController::class, 'show'])->name('show');
        Route::post('{id}/accept',        [\App\Http\Controllers\User\ContractController::class, 'accept'])->name('accept');
        Route::post('{id}/decline',       [\App\Http\Controllers\User\ContractController::class, 'decline'])->name('decline');
        Route::post('{id}/submit',        [\App\Http\Controllers\User\ContractController::class, 'submit'])->name('submit');
        Route::post('{id}/approve',       [\App\Http\Controllers\User\ContractController::class, 'approve'])->name('approve');
        Route::post('{id}/dispute',       [\App\Http\Controllers\User\ContractController::class, 'dispute'])->name('dispute');
        Route::post('{id}/cancel',        [\App\Http\Controllers\User\ContractController::class, 'cancel'])->name('cancel');
        Route::post('{id}/milestones/{msId}/submit',  [\App\Http\Controllers\User\ContractController::class, 'submitMilestone'])->name('milestones.submit');
        Route::post('{id}/milestones/{msId}/approve', [\App\Http\Controllers\User\ContractController::class, 'approveMilestone'])->name('milestones.approve');
        Route::post('{id}/milestones/{msId}/dispute', [\App\Http\Controllers\User\ContractController::class, 'disputeMilestone'])->name('milestones.dispute');
    });

    // ── Browse Jobs & My Applications (job seeker side) ──────────
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\User\JobApplicationController::class, 'browse'])->name('browse');
        Route::get('{id}',                [\App\Http\Controllers\User\JobApplicationController::class, 'show'])->name('show');
        Route::post('{id}/apply',         [\App\Http\Controllers\User\JobApplicationController::class, 'apply'])->name('apply');
        Route::get('my/applications',     [\App\Http\Controllers\User\JobApplicationController::class, 'myApplications'])->name('my-applications');
        Route::delete('applications/{id}/withdraw', [\App\Http\Controllers\User\JobApplicationController::class, 'withdraw'])->name('applications.withdraw');
        Route::get('applications/{appId}/thread',  [\App\Http\Controllers\User\JobApplicationMessageController::class, 'thread'])->name('applications.thread');
        Route::post('applications/{appId}/thread', [\App\Http\Controllers\User\JobApplicationMessageController::class, 'send'])->name('applications.thread.send');
        Route::post('{id}/bookmark',      [\App\Http\Controllers\User\JobBookmarkController::class, 'toggle'])->name('bookmark');
        Route::get('my/saved',            [\App\Http\Controllers\User\JobBookmarkController::class, 'index'])->name('saved');
    });

    // Help Desk
    Route::prefix('helpdesk')->name('helpdesk.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\HelpDeskController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\User\HelpDeskController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\User\HelpDeskController::class, 'store'])->name('store');
        Route::get('{number}', [\App\Http\Controllers\User\HelpDeskController::class, 'show'])->name('show');
        Route::post('{number}/reply', [\App\Http\Controllers\User\HelpDeskController::class, 'reply'])->name('reply');
        Route::post('{number}/close', [\App\Http\Controllers\User\HelpDeskController::class, 'close'])->name('close');
        Route::get('files/{id}/download', [\App\Http\Controllers\User\HelpDeskController::class, 'download'])->name('download');
    });

    // Ratings
    Route::post('ratings', [\App\Http\Controllers\User\RatingController::class, 'store'])->name('ratings.store');

});
