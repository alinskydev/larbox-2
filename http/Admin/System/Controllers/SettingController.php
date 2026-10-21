<?php

namespace Http\Admin\System\Controllers;

use App\Controllers\Controller;
use Http\Admin\System\Requests\SettingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Modules\System\Models\Setting;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(singletons()->system->settings->items);
    }

    public function update(SettingRequest $request): JsonResponse
    {
        $data = Arr::map(
            $request->validatedData,
            fn($value, $key) => [
                'name' => $key,
                'value' => $value,
            ],
        );

        Setting::query()->upsert($data, 'name');

        Cache::forget('system_settings');
        Cache::tags('system_public_data')->flush();

        return $this->successResponse();
    }
}
