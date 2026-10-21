<?php

namespace Modules\History\Resources;

use App\Base\Resource;
use Illuminate\Support\Str;
use Modules\History\Search\StoreHttpLogSearch;

class StoreHttpLogResource extends Resource
{
    public function toArray($request): array
    {
        $requestPath = $this->request_path;

        if (Str::is('product/update/*', $requestPath)) {
            $requestPath = 'product/update/*';
        }

        if (Str::is('product/delete/*', $requestPath)) {
            $requestPath = 'product/delete/*';
        }

        $requestPathType = StoreHttpLogSearch::REQUEST_PATHS[$requestPath];

        return array_replace_recursive(parent::toArray($request), [
            'request_path_type' => $requestPathType,
        ]);
    }
}
