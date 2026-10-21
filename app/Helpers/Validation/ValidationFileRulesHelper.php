<?php

namespace App\Helpers\Validation;

class ValidationFileRulesHelper
{
    public static function image(): array
    {
        return static::generate(
            mimes: ['jpg', 'png', 'webp'],
            max: 1024 * 2,
            additional: [
                'dimensions:max_width=3840,max_height=2160',
            ],
        );
    }

    public static function logo(): array
    {
        return static::generate(
            mimes: ['jpg', 'png', 'webp'],
            max: 1024,
            additional: [
                'dimensions:max_width=1920,max_height=1080',
            ],
        );
    }

    public static function document(): array
    {
        return static::generate(
            mimes: ['doc', 'docx', 'xls', 'xlsx', 'pdf'],
            max: 1024 * 10,
        );
    }

    public static function pdf(): array
    {
        return static::generate(
            mimes: ['pdf'],
            max: 1024 * 5,
        );
    }

    public static function excel(): array
    {
        return static::generate(
            mimes: ['xlsx'],
            max: 1024 * 10,
        );
    }

    public static function media(): array
    {
        return static::generate(
            mimes: ['jpg', 'png', 'webp', 'mp3', 'wav', 'mp4'],
            max: 1024 * 10,
        );
    }

    public static function audio(): array
    {
        return static::generate(
            mimes: ['mp3', 'wav'],
            max: 1024 * 5,
        );
    }

    public static function imageOrDocument(): array
    {
        return static::generate(
            mimes: ['jpg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'pdf'],
            max: 1024 * 10,
        );
    }

    protected static function generate(array $mimes, int $max, array $additional = []): array
    {
        return [
            'file',
            'mimes:' . implode(',', $mimes),
            "max:$max",
            ...$additional,
        ];
    }
}
