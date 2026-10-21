<?php

namespace App\Casts\LocalStorage;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsFile implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): mixed
    {
        return $value ? asset($value) : null;
    }

    public function set($model, $key, $value, $attributes): mixed
    {
        return $value;
    }
}
