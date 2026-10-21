<?php

namespace App\Helpers;

class ClassHelper
{
    private static array $uses = [];

    public static function uses(object|string $class, string $trait): bool
    {
        $class = is_object($class) ? $class::class : $class;

        if (!isset(static::$uses[$class])) {
            static::$uses[$class] = class_uses_recursive($class);
        }

        return in_array($trait, static::$uses[$class]);
    }
}
