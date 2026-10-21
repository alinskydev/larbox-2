<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StringHelper
{
    public static function uuid(): string
    {
        return Str::uuid7()->toString() . config('app.id');
    }

    public static function escapeTags(array $data, array $rawFields = []): array
    {
        $data = Arr::dot($data);

        foreach ($data as $key => &$value) {
            if (is_string($value) && !Str::is($rawFields, $key)) {
                $value = str_replace(['<', '>'], ['&lt;', '&gt;'], $value);
            }
        }

        return Arr::undot($data);
    }

    public static function escapeForLikeCondition(string $value): string
    {
        return is_string($value) ? str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) : $value;
    }

    public static function escapeForMarkdownV2(string $value): string
    {
        return addcslashes($value, '\\_*[]()~`>#+-=|{}.!');
    }

    public static function normalizeSpaces(?string $value): ?string
    {
        if ($value === null) return null;

        $value = str_replace("\u{00A0}", ' ', $value);
        $value = preg_replace('/[\p{Z}\s]+/u', ' ', $value);
        $value = trim($value);

        return $value;
    }
}
