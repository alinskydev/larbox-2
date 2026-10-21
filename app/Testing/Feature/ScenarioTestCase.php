<?php

namespace App\Testing\Feature;

use App\Testing\CreatesApplicationTrait;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class ScenarioTestCase extends BaseTestCase
{
    use CreatesApplicationTrait;

    protected function processTestMethod(string $class, string $method, ?\Closure $callback = null): void
    {
        $test = new $class('_tmp');
        $test->connectionsToTransact = [];
        $test->refreshApplication();
        $test->setUp();

        if ($callback) {
            $callback($test);
        }

        $test->$method();
    }
}
