<?php

namespace Modules\Seo\Traits;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Seo\Models\Meta;

/**
 * @mixin Model
 */
trait SeoMetaModelTrait
{
    public function getSeoMetaAttribute(): array
    {
        return $this->seo_meta->value ?? array_map(fn($value) => null, singletons()->system->languages->all);
    }

    public function getSeoMetaAsArrayAttribute(): array
    {
        return $this->seo_meta->parsed_value ?? array_map(fn($value) => [], singletons()->system->languages->all);
    }

    public function seo_meta(): MorphOne
    {
        return $this->morphOne(Meta::class, 'seo_metable');
    }
}
