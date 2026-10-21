<?php

namespace Http\Emulator\Telegram\Controllers;

use App\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TelegramController extends Controller
{
    public function sendMessage(): JsonResponse
    {
        return $this->successResponse();
    }

    public function sendDocument(): JsonResponse
    {
        return $this->successResponse();
    }

    public function sendPhoto(): JsonResponse
    {
        return $this->successResponse();
    }

    public function sendMediaGroup(): JsonResponse
    {
        return $this->successResponse();
    }
}
