<?php

namespace App\Providers;

use App\Base\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Laravel\Sanctum\Sanctum;
use Laravel\SerializableClosure\SerializableClosure;
use Laravel\Telescope\TelescopeServiceProvider as TelescopePackageServiceProvider;
use Modules\User\Helpers\RoleHelper;
use Modules\User\Models\AccessToken;

/**
 * @property Application $app
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn() => new ImageManager(new Driver()));

        if ($this->app->environment(['local', 'testing']) && class_exists(TelescopePackageServiceProvider::class)) {
            $this->app->register(TelescopePackageServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->registerDeprecationHandler();

        Schema::defaultStringLength(255);
        Model::preventLazyLoading($this->app->environment(['local', 'testing']));
        SerializableClosure::setSecretKey(config('app.key'));

        Password::defaults(function () {
            return Password::min(8)
                ->max(32)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        Http::globalOptions([
            'connect_timeout' => 20,
            'timeout' => 180,
            // 'verify' => false,
        ]);

        Sanctum::usePersonalAccessTokenModel(AccessToken::class);
        Sanctum::authenticateAccessTokensUsing(function (AccessToken $accessToken, bool $isValid) {
            if (!$isValid) return false;

            $user = $accessToken->tokenable;

            if (!$user) return false;

            $company = $user->company;

            if ($company && $company->deleted_at) return false;

            return true;
        });

        $this->app->booted(function () {
            if (!$this->app->routesAreCached()) {
                RoleHelper::fullRouteNames($this->app['router']->getRoutes());
            }
        });
    }

    private function registerDeprecationHandler(): void
    {
        if (!app()->isProduction()) {
            $previousHandler = set_error_handler(function ($level, $message, $file, $line) use (&$previousHandler) {
                if ($level !== E_DEPRECATED && $level !== E_USER_DEPRECATED) {
                    return $previousHandler($level, $message, $file, $line);
                }

                if (str_contains(str_replace('\\', '/', $file), 'vendor/laravel/framework/src/Illuminate')) {
                    return true;
                }

                throw new \ErrorException($message, 0, $level, $file, $line);
            });
        }
    }
}
