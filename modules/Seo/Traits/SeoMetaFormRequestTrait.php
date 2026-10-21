<?php

namespace Modules\Seo\Traits;

use App\Requests\ResourceFormRequest;

/**
 * @mixin ResourceFormRequest
 */
trait SeoMetaFormRequestTrait
{
    protected function seoMetaRules(): array
    {
        return [
            'seo_meta' => 'present|nullable|string|max:5000',
        ];
    }
}
