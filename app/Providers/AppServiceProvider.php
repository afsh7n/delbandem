<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default locale to Persian
        app()->setLocale('fa');
        
        // Set timezone to Tehran
        config(['app.timezone' => 'Asia/Tehran']);
    }
}
