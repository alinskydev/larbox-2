<?php

namespace Modules\Product\Resources;

use App\NestedSet\NestedSetResource;

class CategoryResource extends NestedSetResource
{
    public function toArray($request): array
    {
        return array_replace_recursive(parent::toArray($request), [
            'full_localized_name' => $this->whenLoaded(
                'ancestors',
                fn() => $this->ancestorsPath('localized_name', ' | '),
            ) ?: $this->localized_name,
        ]);
    }
}
