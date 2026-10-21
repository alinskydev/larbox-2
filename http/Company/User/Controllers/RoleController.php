<?php

namespace Http\Company\User\Controllers;

use App\Controllers\ResourceController;
use Http\Company\User\Filters\RoleFilter;
use Http\Company\User\Requests\RoleRequest;
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
        $response = RoleHelper::routeTree(user());
        return response()->json($response);
    }
}
