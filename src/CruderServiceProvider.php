<?php

namespace SgFlores\Cruder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class CruderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge the package configuration with the application configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/cruder.php', 'cruder'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/cruder.php' => config_path('cruder.php'),
        ], 'cruder-config');

        // Enable query logging if configured
        $this->enableQueryLoggingIfConfigured();
    }

    /**
     * Enable query logging if configured in the config file.
     */
    protected function enableQueryLoggingIfConfigured(): void
    {
        if (config('cruder.query_logging.enabled', false)) {
            DB::enableQueryLog();
        }
    }
}
