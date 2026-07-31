<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Web Routes
|--------------------------------------------------------------------------
*/

// Payment gateway return/cancel callbacks
Route::prefix('payment')->name('payment.')->group(function () {
    Route::match(['get', 'post'], '{gateway}/return', [\App\Http\Controllers\Payment\GatewayCallbackController::class, 'returnPay'])->name('return');
    Route::match(['get', 'post'], '{gateway}/cancel', [\App\Http\Controllers\Payment\GatewayCallbackController::class, 'cancel'])->name('cancel');
});

// Subcategory AJAX (public)
Route::get('subcategories', function (\Illuminate\Http\Request $request) {
    return \App\Models\WorkSubcategory::where('category_id', $request->category_id)
        ->where('status', 1)
        ->get(['id', 'name']);
})->name('subcategories.json');

// SEO: XML sitemap + robots.txt (dynamic so the Sitemap URL uses APP_URL)
Route::get('sitemap.xml', [\App\Http\Controllers\Web\SitemapController::class, 'index'])->name('sitemap');
Route::get('robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /dashboard/',
        'Disallow: /admin/',
        'Disallow: /install',
        'Disallow: /secure/',
        'Allow: /',
        '',
        'Sitemap: ' . route('sitemap'),
        '',
    ];

    return response(implode("\n", $lines))->header('Content-Type', 'text/plain');
})->name('robots');

// Convenience redirects: the user auth pages live under /dashboard, but people
// commonly type the bare paths.
Route::redirect('/login', '/dashboard/login');
Route::redirect('/register', '/dashboard/register');

// Homepage
Route::get('/', [\App\Http\Controllers\Web\HomeController::class, 'index'])->name('home');

// Featured opportunities page
Route::get('featured', [\App\Http\Controllers\Web\FeaturedController::class, 'index'])->name('featured');

// Browse works
Route::get('works', [\App\Http\Controllers\Web\WorkController::class, 'index'])->name('works.index');

// Browse jobs (public)
Route::get('jobs', [\App\Http\Controllers\Web\JobController::class, 'index'])->name('jobs.index');
Route::get('jobs/{slug}', [\App\Http\Controllers\Web\JobController::class, 'show'])->name('jobs.show');
Route::get('works/{slug}', [\App\Http\Controllers\Web\WorkController::class, 'show'])->name('works.show');

// Apply to a work (requires auth)
Route::post('works/{slug}/apply', [\App\Http\Controllers\Web\WorkController::class, 'apply'])
    ->middleware('auth')
    ->name('works.apply');

// Blog
Route::get('blog', [\App\Http\Controllers\Web\BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{id}', [\App\Http\Controllers\Web\BlogController::class, 'show'])->name('blog.show');

// Contact
Route::get('contact', [\App\Http\Controllers\Web\PageController::class, 'contact'])->name('contact');
Route::post('contact', [\App\Http\Controllers\Web\PageController::class, 'submitContact'])->name('contact.submit');

// Newsletter
Route::post('subscribe', [\App\Http\Controllers\Web\PageController::class, 'subscribe'])->name('subscribe');

// Language switch
Route::get('language/{code}', [\App\Http\Controllers\Web\PageController::class, 'changeLanguage'])->name('language');

// Cookie consent
Route::post('cookie/accept', [\App\Http\Controllers\Web\PageController::class, 'acceptCookie'])->name('cookie.accept');
Route::post('cookie/reject', [\App\Http\Controllers\Web\PageController::class, 'rejectCookie'])->name('cookie.reject');

// Public user profiles — no auth required
Route::get('u/{username}', [\App\Http\Controllers\User\PublicProfileController::class, 'show'])
    ->name('user.public-profile');

// Private KYC document streaming — access controlled inside the controller
// (owner, admin, or temporary signed URL). Files live outside the web root.
Route::get('secure/kyc/{user}/{side}', [\App\Http\Controllers\SecureFileController::class, 'kyc'])
    ->where('side', 'front|back')
    ->name('secure.kyc');
Route::get('secure/resume/{application}', [\App\Http\Controllers\SecureFileController::class, 'resume'])
    ->whereNumber('application')->name('secure.resume');
Route::get('secure/topup-proof/{topup}', [\App\Http\Controllers\SecureFileController::class, 'topupProof'])
    ->whereNumber('topup')->name('secure.topupProof');
Route::get('secure/help-file/{file}', [\App\Http\Controllers\SecureFileController::class, 'helpFile'])
    ->whereNumber('file')->name('secure.helpFile');
Route::get('secure/contract-proof/{contract}/{milestone?}', [\App\Http\Controllers\SecureFileController::class, 'contractProof'])
    ->whereNumber('contract')->whereNumber('milestone')->name('secure.contractProof');
Route::get('secure/work-proof/{submission}/{index}', [\App\Http\Controllers\SecureFileController::class, 'workProof'])
    ->whereNumber('submission')->whereNumber('index')->name('secure.workProof');

// Dynamic pages (about, privacy, terms, etc.)
// Exclude reserved prefixes so admin/dashboard/payment routes are not swallowed
Route::get('{slug}', [\App\Http\Controllers\Web\PageController::class, 'show'])
    ->where('slug', '^(?!admin|dashboard|payment|api|ipn|up|install)[a-zA-Z0-9\-_]+$')
    ->name('pages.show');
