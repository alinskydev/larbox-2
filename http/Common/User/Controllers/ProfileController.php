<?php

namespace Http\Common\User\Controllers;

use App\Controllers\Controller;
use Http\Common\User\Requests\ProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function index(): JsonResponse
    {
        $user = user()->loadMissing([
            'profile',
            'company',
            'roles',
        ]);

        $user->all_role_routes = $user->getService()->allRoleRoutes();
        $data = $user->resourceClass::make($user);

        return response()->json($data);
    }

    public function update(ProfileRequest $request): JsonResponse
    {
        $request->model->safelySave($request->validatedData);
        return $this->successResponseWithAccessToken($request->model);
    }
}
