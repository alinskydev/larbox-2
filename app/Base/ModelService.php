<?php

namespace App\Base;

class ModelService
{
    public function __construct(protected Model $model) {}

    public function checkAndGetObserverParams(string $key): mixed
    {
        $params = $this->model->observerParams()[$key] ?? null;

        if ($params === null) {
            throw new \Exception("$key in observerParams() is not set for " . get_class($this->model));
        }

        return $params;
    }
}
