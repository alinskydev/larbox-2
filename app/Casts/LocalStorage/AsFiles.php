<?php

namespace App\Casts\LocalStorage;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsFiles implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): mixed
    {
        $value = json_decode($value, true);
        return $value ? array_map(fn($v) => asset($v), $value) : [];
    }

    public function set($model, $key, $value, $attributes): mixed
    {
        $value = array_values($value);
        $value = json_encode($value);

        return $value;
    }
}
