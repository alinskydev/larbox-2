<?php

namespace App\Helpers;

use Illuminate\Support\Arr;

class ArrayHelper
{
    public static function fillByTemplate(array $data, array $template): array
    {
        return Arr::map($template, fn($value, $key) => $data[$key] ?? $value);
    }

    public static function asMultilineString(array $data, string $separator = ' '): string
    {
        $result = Arr::map($data, fn($value, $key) => $key ? ($key . $separator . $value) : $value);
        return implode("\n", $result);
    }
}
