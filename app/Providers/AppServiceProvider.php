<?php

namespace App\Providers;

use App\Contracts\IObjectService;
use App\Services\ObjectService;
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
        $this->app->bind(IObjectService::class, ObjectService::class);
    }
}
