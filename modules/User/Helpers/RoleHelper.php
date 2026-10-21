<?php

namespace Modules\User\Helpers;

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Models\Role;
use Modules\User\Models\User;

class RoleHelper
{
    protected static array $excludedRoutes = [

        // Framework

        '',
        '_ignition.*',
        'telescope',
        'telescope.*',
        'sanctum.*',

        // Common, emulator

        'common.*',
        'emulator.*',

        // Main

        'admin.company.company.update-credential',
        'admin.history.*',
        'admin.system.*',
        'admin.user.*',

        'company.user.role.available-routes',
    ];

    private static array $availableRoutes;

    public static function routeList(User|Role|null $entity = null): array
    {
        if (!isset(static::$availableRoutes)) {
            $result = Route::getRoutes()->getRoutes();
            $result = array_map(fn($route) => $route->getName(), $result);
            $result = array_filter($result, fn($name) => !Str::is(static::$excludedRoutes, $name));
            $result = array_unique($result);

            static::$availableRoutes = array_values($result);
        }

        $roleRoutes = match (true) {
            $entity instanceof User => $entity->roles->pluck('routes')->collapse()->toArray(),
            $entity instanceof Role => $entity->routes,
            default => null,
        };

        if ($roleRoutes === null) return static::$availableRoutes;

        $result = array_filter(static::$availableRoutes, fn($name) => Str::is($roleRoutes, $name));
        $result = array_values($result);

        return $result;
    }

    public static function routeTree(User|Role|null $entity = null): array
    {
        $result = static::routeList($entity);
        $result = array_combine($result, $result);
        $result = Arr::undot($result);

        return $result;
    }

    public static function countRoutesPerGroup(array $routes): array
    {
        $result = [];

        foreach ($routes as $route) {
            $parts = explode('.', $route);

            $groupParts = [];

            foreach ($parts as $index => $part) {
                $groupParts[] = $part;
                $group = implode('.', $groupParts);

                $result[$group] ??= 0;

                if (array_key_last($parts) !== $index) {
                    $result[$group]++;
                }
            }
        }

        $result = array_filter($result, fn($count) => $count > 0);

        return $result;
    }

    public static function canAccessRoute(User $user, string $route): bool
    {
        $userRoutes = $user->getService()->allRoleRoutes();

        if (!$userRoutes) abort(403);

        $isCorrectRoutePrefix = match (true) {
            Str::startsWith($route, 'admin.') => UserTypeEnum::isAdminType($user->type),
            Str::startsWith($route, 'company.') => UserTypeEnum::isCompanyType($user->type),
            default => false,
        };

        if (!$isCorrectRoutePrefix) return false;

        if (Str::is(static::$excludedRoutes, $route)) return true;

        $directorRoutes = match ($user->type) {
            UserTypeEnum::COMPANY_EMPLOYEE->value => $user->company->director->getService()->allRoleRoutes(),
            default => $userRoutes,
        };

        $userHasAccess = in_array($route, $userRoutes);
        $directorHasAccess = in_array($route, $directorRoutes);

        return $userHasAccess && $directorHasAccess;
    }

    public static function fullRouteNames(RouteCollection $routes): void
    {
        foreach ($routes as $route) {
            $name = $route->getName();

            if (!$name) continue;

            $prefix = str_replace('/', '.', trim($route->getPrefix() ?? '', '/'));
            $full = trim(($prefix !== '' ? $prefix . '.' : '') . $name, '.');

            if ($full !== $name) {
                $route->setAction(array_merge($route->getAction(), [
                    'as' => $full,
                ]));
            }
        }

        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }
}
