<?php

namespace Cerpus\Gdpr;

use Illuminate\Support\ServiceProvider;
use Cerpus\Gdpr\Exceptions\GdprRouteException;
use Cerpus\Gdpr\Exceptions\GdprPublishMigrationsException;

class GdprServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishRoutes();
        $this->publishMigrations();
        $this->publishConfig();

        if (method_exists($this, 'loadMigrationsFrom')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    public function register()
    {
        //
    }

    protected function publishRoutes()
    {
        try {
            if (!method_exists($this, 'loadRoutesFrom')) {
                if (!$this->app->routesAreCached()) {
                    require __DIR__ . '/../routes/gdpr.php';
                }
            } else {
                $this->loadRoutesFrom(__DIR__ . '/../routes/gdpr.php');
            }
        } catch (\Throwable $e) {
            throw new GdprRouteException(__METHOD__ . ": Error registering gdpr routes. " . $e->getMessage());
        }
    }

    protected function publishMigrations()
    {
        try {
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'migrations');
        } catch (\Throwable $e) {
            throw new GdprPublishMigrationsException(__METHOD__ . ": Error publishing gdpr migrations. " . $e->getMessage());
        }
    }

    protected function publishConfig()
    {
        try {
            $this->publishes([
                __DIR__ . '/../config/' => config_path(),
            ], 'config');
        } catch (\Throwable $e) {
            throw new GdprPublishMigrationsException(__METHOD__ . ": Error publishing gdpr config. " . $e->getMessage());
        }
    }
}
