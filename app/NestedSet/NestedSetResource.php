<?php

namespace App\NestedSet;

use App\Base\Resource;

class NestedSetResource extends Resource
{
    public function toArray($request): array
    {
        return array_replace_recursive(parent::toArray($request), [
            'descendants' => $this->when(false, null),
        ]);
    }

    protected function ancestorsPath(string $field, string $separator): string
    {
        $result = [
            ...$this->ancestors->pluck($field)->toArray(),
            $this->{$field},
        ];

        return implode($separator, $result);
    }
}
