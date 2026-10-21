<?php

namespace Http\Common\Auth\Controllers;

use App\Controllers\Controller;
use Http\Common\Auth\Requests\ResetPassword\CompleteRequest;
use Http\Common\Auth\Requests\ResetPassword\SendCodeRequest;
use Modules\User\Models\AuthCode;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordController extends Controller
{
    public function sendCode(SendCodeRequest $request): Response
    {
        (new AuthCode())->getService()->create($request->validatedData['username']);
        return $this->successResponse();
    }

    public function complete(CompleteRequest $request): Response
    {
        $user = user();
        $user->password = $request->validatedData['password'];
        $user->safelySave();

        return $this->successResponseWithAccessToken($user);
    }
}
