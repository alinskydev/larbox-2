<?php

namespace Http\Common\System\Controllers;

use App\Controllers\Controller;
use App\Helpers\ReflectionHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Modules\System\Resources\SettingResource;

class SystemController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $locale = app()->getLocale();

        $result = Cache::tags('system_public_data')->remember("system_public_data:$locale", 86400, fn() => [
            'settings' => $this->settings(),
            'enums' => $this->enums(),
            'translations' => $this->translations(),
        ]);

        return response()->json($result);
    }

    private function settings()
    {
        return Arr::except(
            SettingResource::make(singletons()->system->settings->items)->resolve(),
            [
                'admin_email',
            ],
        );
    }

    private function enums(): array
    {
        $result = [];

        $files = glob(base_path("modules/*/Enums/*.php"));
        $pattern = "/^Modules\\\|\\\\Enums\\\|Enum$/";

        foreach ($files as $file) {
            $class = ReflectionHelper::classByFile($file);
            $class = $class->getName();

            $name = preg_replace_array($pattern, ['', '.', ''], $class);

            $values = array_combine($class::values(), $class::values());
            $result[$name] = method_exists($class, 'labels') ? $class::labels() : $values;
        }

        $result = Arr::undot($result);
        ksort($result);

        return $result;
    }

    private function translations(): array
    {
        $path = lang_path(app()->getLocale());

        return [
            'fields' => require("$path/fields.php"),
        ];
    }
}
