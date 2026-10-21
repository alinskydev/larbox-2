<?php

namespace Modules\Company\Resources;

use App\Base\Resource;

class CredentialResource extends Resource
{
    public function toArray($request): array
    {
        return array_replace_recursive(parent::toArray($request), [
            'store' => [
                'password' => $this->store['password'] !== '',
            ],
        ]);
    }
}
