<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        $isVercel = (bool) (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL'));

        if ($this->app->environment('production') || $isVercel) {
            URL::forceScheme('https');
        }

        if ($isVercel && ! $this->app->runningInConsole()) {
            try {
                if (! Schema::hasTable('users')) {
                    Artisan::call('migrate', ['--force' => true]);
                    Artisan::call('db:seed', ['--force' => true]);
                }
            } catch (Throwable) {
                // Ignore during build/discovery
            }
        }
    }
}
