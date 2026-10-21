<?php

namespace App\Casts\LocalStorage;

use App\Helpers\ImageHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsImages implements CastsAttributes
{
    private array $sizes = [];

    public function __construct(string $sizes)
    {
        $this->sizes = explode('|', $sizes);
        $this->sizes = array_filter($this->sizes, fn($value) => $value !== null);
    }

    public function get($model, $key, $value, $attributes): mixed
    {
        $value = json_decode($value, true);
        return $value ? array_map(fn($v) => ImageHelper::populateSizes($v, $this->sizes), $value) : [];
    }

    public function set($model, $key, $value, $attributes): mixed
    {
        $value = array_values($value);
        $value = json_encode($value);

        return $value;
    }
}
