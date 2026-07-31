<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('pagination.jobstation');

        // Force HTTPS for all generated URLs in production so links, assets,
        // and redirects never downgrade to http behind a TLS-terminating proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
