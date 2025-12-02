<?php

namespace App\Providers;

use App\MessagePipeline\Consumer\ConsumerDiscovery;
use App\MessagePipeline\Consumer\ConsumerRegistry;
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
        // automatically discover and register consumers
        ConsumerDiscovery::discover();

        // register message pipeline consumers from config
        foreach (config('consumers', []) as $pattern => $class) {
            ConsumerRegistry::register($pattern, $class);
        }
    }
}
