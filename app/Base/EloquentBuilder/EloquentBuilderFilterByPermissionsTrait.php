<?php

namespace App\Base\EloquentBuilder;

use App\Base\EloquentBuilder;
use Modules\User\Helpers\Role\RolePermissionHelper;
use Modules\User\Helpers\RoleHelper;

/**
 * @mixin EloquentBuilder
 */
trait EloquentBuilderFilterByPermissionsTrait
{
    public function filterByOwnRecords(string $route, string|array $fields = 'creator_id'): static
    {
        return $this->when(
            !RoleHelper::canAccessRoute(
                user: user(),
                route: "$route." . RolePermissionHelper::PERMISSION_ALL_RECORDS,
            ),
            function ($query) use ($fields) {
                $query->filterByCreator($fields);
            },
        );
    }
}
