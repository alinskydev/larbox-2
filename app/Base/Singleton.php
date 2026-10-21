<?php

namespace App\Base;

abstract class Singleton
{
    protected function __construct() {}

    abstract public static function init(): static;
}
