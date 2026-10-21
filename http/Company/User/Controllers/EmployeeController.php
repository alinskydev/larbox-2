<?php

namespace Http\Company\User\Controllers;

use App\Controllers\ResourceController;
use Http\Company\User\Filters\EmployeeFilter;
use Http\Company\User\Requests\EmployeeRequest;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\JsonResponse;
use Modules\User\Models\User;
use Modules\User\Search\UserSearch;

class EmployeeController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new User(),
            search: new UserSearch(),
            filter: new EmployeeFilter(),
            formRequestClass: EmployeeRequest::class,
        );
    }

    public function update(ValidatesWhenResolved $request): JsonResponse
    {
        parent::update($request);
        return $this->successResponseWithAccessToken($request->model);
    }
}
