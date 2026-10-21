<?php

namespace Modules\System\Singletons;

use App\Base\Singleton;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Modules\System\Models\Language;

class LanguageSingleton extends Singleton
{
    protected static self $instance;

    public array $all;
    public array $active;
    public array $main;

    public static function init(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();

            static::$instance->all = Cache::remember('system_languages', 86400, function () {
                return Language::query()->orderBy('id')->get()->keyBy('code')->toArray();
            });

            static::$instance->active = array_filter(static::$instance->all, fn($value) => $value['is_active']);
            static::$instance->main = Arr::keyBy(static::$instance->active, 'is_main')[1];
        }

        return static::$instance;
    }
}
