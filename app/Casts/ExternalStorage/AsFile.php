<?php

namespace App\Casts\ExternalStorage;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsFile implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): mixed
    {
        return $value ? config('integrations.storage.url') . "/$value" : null;
    }

    public function set($model, $key, $value, $attributes): mixed
    {
        return $value;
    }
}
