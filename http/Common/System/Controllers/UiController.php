<?php

namespace Http\Common\System\Controllers;

use App\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\System\Helpers\UiSchemaHelper;

class UiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $locale = app()->getLocale();

        $schema = Cache::tags('system_public_data')->remember(
            "system_public_data:ui:$locale",
            86400,
            fn() => UiSchemaHelper::build(),
        );

        $data = UiSchemaHelper::forUser(user(), $schema);

        return response()->json($data);
    }
}
