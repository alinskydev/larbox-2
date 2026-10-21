<?php

namespace App\Helpers;

class ReflectionHelper
{
    public static function classByFile(string $file): \ReflectionClass
    {
        $file = str_replace(base_path(), '', $file);
        $file = mb_substr($file, 1);
        $file = preg_replace("/\.php$/", '', $file);

        $class = str_replace('/', '\\', $file);
        $class = ucfirst($class);
        $class = new \ReflectionClass($class);

        return $class;
    }
}
