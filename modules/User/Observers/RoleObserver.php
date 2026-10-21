<?php

namespace Modules\User\Observers;

use Illuminate\Support\Facades\DB;
use Modules\User\Exceptions\RoleException;
use Modules\User\Helpers\RoleHelper;
use Modules\User\Models\Role;

class RoleObserver
{
    public function saving(Role $model): void
    {
        $routes = $model->routes ?? [];
        $availableRoutes = RoleHelper::routeList();

        $routeGroups = RoleHelper::countRoutesPerGroup($routes);
        $availableRouteGroups = RoleHelper::countRoutesPerGroup($availableRoutes);

        krsort($routeGroups);

        foreach ($routeGroups as $group => $count) {
            if ($availableRouteGroups[$group] === $count) {
                $routes = array_filter($routes, fn($value) => !str_starts_with($value, "$group."));
                $routes[] = "$group.*";
            }
        }

        sort($routes);

        $model->routes = $routes;
    }

    public function deleting(Role $model): void
    {
        if ($model->id === 1) {
            throw new RoleException(RoleException::UNDELETABLE);
        }

        $userUsernames = DB::table('user_role_ref as urr_1')
            ->leftJoin('user', 'user.id', '=', 'urr_1.user_id')
            ->where('urr_1.role_id', $model->id)
            ->whereNotExists(function ($query) use ($model) {
                $query->select(DB::raw(1))
                    ->from('user_role_ref as urr_2')
                    ->whereColumn('urr_2.user_id', 'urr_1.user_id')
                    ->where('urr_2.role_id', '!=', $model->id);
            })
            ->orderBy('urr_1.user_id', 'desc')
            ->limit(3)
            ->get()
            ->pluck('username');

        if (!$userUsernames->isEmpty()) {
            throw new RoleException(RoleException::ATTACHED_TO_USERS, [
                'role' => $model->name,
                'users' => $userUsernames->join(', '),
            ]);
        }
    }
}
