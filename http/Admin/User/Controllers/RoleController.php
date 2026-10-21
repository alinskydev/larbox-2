<?php

namespace Http\Admin\User\Controllers;

use App\Controllers\ResourceController;
use Http\Admin\User\Filters\RoleFilter;
use Http\Admin\User\Requests\RoleRequest;
use Illuminate\Http\JsonResponse;
use Modules\User\Helpers\RoleHelper;
use Modules\User\Models\Role;
use Modules\User\Search\RoleSearch;

class RoleController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Role(),
            search: new RoleSearch(),
            filter: new RoleFilter(),
            formRequestClass: RoleRequest::class,
        );
    }

    public function availableRoutes(): JsonResponse
    {
        $response = RoleHelper::routeTree();
        return response()->json($response);
    }
}
