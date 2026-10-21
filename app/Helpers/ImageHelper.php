<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageHelper
{
    public static function resizeAndUpload(UploadedFile $file, string $folder, array $sizes): string
    {
        return FileHelper::upload(
            file: $file,
            folder: $folder,
            callback: function ($file, $path) use ($sizes) {
                /** @var ImageManager $manager */
                $manager = app(ImageManager::class);

                $image = $manager->decode($file);
                $image->width() >= $image->height() ? $image->scaleDown(width: $sizes[0]) : $image->scaleDown(height: $sizes[1]);
                $image->save($path, 90);
            },
        );
    }

    public static function thumbnail(
        string $sourceUrl,
        string $resizeMethod,
        array $params = [],
        bool $asWebp = false,
    ): string {
        try {
            $sourceFile = public_path($sourceUrl);

            if (!is_file($sourceFile)) {
                throw new \Exception('File does not exist');
            }

            if ($asWebp) {
                $extension = 'webp';
            } else {
                $extension = pathinfo($sourceFile, PATHINFO_EXTENSION);
                $extension = mb_strtolower($extension);
            }

            $thumbPath = "storage/thumbs/$resizeMethod";
            $thumbPath .= $params ? '/' . implode('x', $params) : '';
            $thumbName = md5($sourceFile) . filemtime($sourceFile) . ".$extension";

            $thumbFile = public_path("$thumbPath/$thumbName");
            $thumbUrl = "/$thumbPath/$thumbName";

            if (!is_file($thumbFile)) {
                File::makeDirectory(public_path($thumbPath), 0755, true, true);

                /** @var ImageManager $manager */
                $manager = app(ImageManager::class);

                $image = $manager->decode($sourceFile);

                if ($resizeMethod !== 'original') {
                    $image->{$resizeMethod}(...$params);
                }

                $image->save($thumbFile, 90);
            }

            return $thumbUrl;
        } catch (\Throwable $e) {
            return $sourceUrl;
        }
    }

    public static function populateSizes(string $url, array $sizes): array
    {
        $result = [
            'original' => [
                'raw' => asset($url),
                'webp' => asset(static::thumbnail(
                    sourceUrl: $url,
                    resizeMethod: 'original',
                    asWebp: true,
                )),
            ],
        ];

        $types = ['raw', 'webp'];

        foreach ($sizes as $size) {
            foreach ($types as $type) {
                $sizeParams = explode('_', $size);

                $thumb = static::thumbnail(
                    sourceUrl: $url,
                    resizeMethod: array_shift($sizeParams),
                    params: $sizeParams,
                    asWebp: $type === 'webp',
                );

                $result[$size][$type] = asset($thumb);
            }
        }

        return $result;
    }
}
