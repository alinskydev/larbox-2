<?php

namespace App\Helpers\Database;

use Illuminate\Support\Str;

class DatabaseSeederHelper
{
    public static function localized(?string $string, bool $hasLanguageSuffix = true): string
    {
        $languages = array_keys(config('larbox.languages'));
        $languages = array_combine($languages, $languages);

        $result = array_map(fn($language) => $hasLanguageSuffix ? "$string $language" : $string, $languages);

        return json_encode($result);
    }

    public static function slug(string $string, bool $hasLanguageSuffix = true): string
    {
        $string = $hasLanguageSuffix ? "$string " . app()->getLocale() : $string;
        return Str::slug($string);
    }
}
