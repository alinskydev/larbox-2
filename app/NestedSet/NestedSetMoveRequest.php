<?php

namespace App\NestedSet;

use App\Base\Model;
use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use Illuminate\Validation\Rule;

class NestedSetMoveRequest extends ResourceFormRequest
{
    public function nonLocalizedRules(): array
    {
        return [
            'position' => [
                'required',
                Rule::in(['before', 'after', 'into']),
            ],
            'source_id' => [
                'required',
                'integer',
                new ExistsWithOldRule(
                    model: new Model(),
                    query: $this->model->query()->whereNotNull('parent_id'),
                ),
            ],
            'destination_id' => [
                'required',
                'integer',
                new ExistsWithOldRule(
                    model: new Model(),
                    query: $this->model->query()->whereNotNull('parent_id'),
                ),
            ],
        ];
    }
}
