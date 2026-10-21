<?php

namespace Http\Emulator\Store\Controllers;

use App\Controllers\Controller;
use App\Helpers\StringHelper;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(): JsonResponse
    {
        return response()->json(['token' => StringHelper::uuid()]);
    }
}
