<?php

namespace App\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Modules\User\Models\User;

class Controller extends BaseController
{
    protected function successResponse(int $status = 200): JsonResponse
    {
        return response()->json(['message' => 'Success'], $status);
    }

    protected function successResponseWithAccessToken(User $user, int $status = 200): JsonResponse
    {
        if (isset($user->newAccessToken) && (!user() || user()->id === $user->id)) {
            return response()->json(['token' => $user->newAccessToken], $status);
        } else {
            return $this->successResponse($status);
        }
    }
}
