<?php

use Modules\System\Singletons\LanguageSingleton;
use Modules\System\Singletons\SettingSingleton;

if (!function_exists('singletons')) {
    /**
     * @return object{
     *     system: object{
     *         languages: LanguageSingleton,
     *         settings: SettingSingleton
     *     }
     * }
     */
    function singletons(): object
    {
        static $items;

        if (!isset($items)) {
            $items = new stdClass();
            $items->system = new stdClass();
            $items->system->languages = LanguageSingleton::init();
            $items->system->settings = SettingSingleton::init();
        }

        return $items;
    }
}
