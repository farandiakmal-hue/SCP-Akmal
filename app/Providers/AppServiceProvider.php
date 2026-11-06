<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        
    }

    
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Di server produksi semua URL (CSS, gambar, form) memakai HTTPS
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
