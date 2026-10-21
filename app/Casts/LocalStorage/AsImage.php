<?php

namespace App\Casts\LocalStorage;

use App\Helpers\ImageHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsImage implements CastsAttributes
{
    private array $sizes = [];

    public function __construct(string $sizes)
    {
        $this->sizes = explode('|', $sizes);
        $this->sizes = array_filter($this->sizes, fn($value) => $value !== null);
    }

    public function get($model, $key, $value, $attributes): mixed
    {
        return $value ? ImageHelper::populateSizes($value, $this->sizes) : null;
    }

    public function set($model, $key, $value, $attributes): mixed
    {
        return $value;
    }
}
