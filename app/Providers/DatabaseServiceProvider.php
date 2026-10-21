<?php

namespace App\Providers;

use App\Database\ConnectionFactory;
use App\Database\DatabaseManager;
use Illuminate\Contracts\Database\ConcurrencyErrorDetector as ConcurrencyErrorDetectorContract;
use Illuminate\Contracts\Database\LostConnectionDetector as LostConnectionDetectorContract;
use Illuminate\Database\ConcurrencyErrorDetector;
use Illuminate\Database\DatabaseServiceProvider as ServiceProvider;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\LostConnectionDetector;

class DatabaseServiceProvider extends ServiceProvider
{
    protected function registerConnectionServices()
    {
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });

        $this->app->bind('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });

        $this->app->singleton('db.transactions', function ($app) {
            return new DatabaseTransactionsManager();
        });

        $this->app->singleton(ConcurrencyErrorDetectorContract::class, function () {
            return new ConcurrencyErrorDetector();
        });

        $this->app->singleton(LostConnectionDetectorContract::class, function () {
            return new LostConnectionDetector();
        });
    }
}
