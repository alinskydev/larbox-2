<?php

namespace Modules\System\Singletons;

use App\Base\Singleton;
use Illuminate\Support\Facades\Cache;
use Modules\System\Models\Setting;

/**
 * @property string $admin_email
 * @property string $favicon
 * @property string $logo
 * @property string $project_name
 */
class SettingSingleton extends Singleton
{
    protected static self $instance;

    public array $items;

    public static function init(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();

            static::$instance->items = Cache::remember('system_settings', 86400, function () {
                return Setting::query()->get()->pluck('value', 'name')->toArray();
            });
        }

        return static::$instance;
    }

    public function __get(string $name)
    {
        return $this->items[$name];
    }
}
