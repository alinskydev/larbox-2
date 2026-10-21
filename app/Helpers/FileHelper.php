<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileHelper
{
    public static function save(string $folder, string $content, ?string $extension = null): string
    {
        [$path, $absolutePath, $name] = static::prepareFolder($folder, $extension);

        file_put_contents("$absolutePath/$name", $content);
        chown("$absolutePath/$name", 'www-data');

        return "$path/$name";
    }

    public static function upload(UploadedFile $file, string $folder, ?callable $callback = null): string
    {
        [$path, $absolutePath, $name] = static::prepareFolder($folder, $file->guessExtension());

        if ($callback) {
            $callback($file, "$absolutePath/$name");
        } else {
            $file->move($absolutePath, $name);
            chown("$absolutePath/$name", 'www-data');
        }

        return "$path/$name";
    }

    private static function prepareFolder(string $folder, ?string $extension): array
    {
        $path = "storage/uploads/$folder/" . date('Y/m/d');
        $name = StringHelper::uuid() . ($extension ? ".$extension" : '');

        $absolutePath = public_path($path);
        File::makeDirectory($absolutePath, 0755, true, true);

        return [$path, $absolutePath, $name];
    }
}
