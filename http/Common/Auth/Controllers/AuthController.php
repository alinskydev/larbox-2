<?php

namespace Http\Common\Auth\Controllers;

use App\Controllers\Controller;
use Http\Common\Auth\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        user()->getService()->createNewAccessToken();
        return $this->successResponseWithAccessToken(user());
    }

    public function logout(): JsonResponse
    {
        user()->currentAccessToken()->delete();
        return $this->successResponse();
    }
}
