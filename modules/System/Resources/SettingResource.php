<?php

namespace Modules\System\Resources;

use App\Base\Resource;
use Illuminate\Support\Arr;

class SettingResource extends Resource
{
    public function toArray($request): mixed
    {
        return Arr::map($this->resource, fn($value, $key) => match ($key) {
            'favicon', 'logo' => $value ? asset($value) : null,
            default => $value,
        });
    }
}
