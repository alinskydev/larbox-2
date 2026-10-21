<?php

namespace App\Testing\Feature\Helpers;

use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;

class FormHelper
{
    public static function localized(string $string): array
    {
        return array_map(fn($value) => "$string " . $value['code'], singletons()->system->languages->all);
    }

    public static function files(
        string $name = 'image.jpg',
        string $mime = 'image/jpeg',
        int $size = 100,
        int $count = 1,
    ): File|array {
        $fileClosure = match ($mime) {
            'image/jpeg' => fn() => UploadedFile::fake()->image($name, 100, 100),
            'image/png' => fn() => UploadedFile::fake()->image($name, 100, 100),
            default => fn() => UploadedFile::fake()->create($name, $size, $mime),
        };

        return match ($count) {
            1 => $fileClosure(),
            default => array_map($fileClosure, range(1, $count)),
        };
    }

    public static function deletableFiles(
        string $field,
        File|array $files,
        array $oldKeys = [],
    ): array {
        return [
            $field => $files,
            "{$field}_old_keys" => json_encode($oldKeys),
        ];
    }

    public static function realImage(): File
    {
        return UploadedFile::fake()->createWithContent(
            '1.png',
            file_get_contents(public_path('test_data/images/1.png')),
        );
    }

    public static function seoMeta(): array
    {
        return [
            'seo_meta' => array_map(
                fn($value) => '<meta name="description" content="Description" />',
                singletons()->system->languages->all,
            ),
        ];
    }
}
