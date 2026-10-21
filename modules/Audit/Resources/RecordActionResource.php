<?php

namespace Modules\Audit\Resources;

use App\Base\Resource;

class RecordActionResource extends Resource
{
    public function toArray($request): array
    {
        if (!$this->isCollection) {
            $this->resource->makeVisible(['old_values', 'new_values']);
            $this->resource->old_values = $this->replaceFieldsWithLabels($this->old_values);
            $this->resource->new_values = $this->replaceFieldsWithLabels($this->new_values);
        }

        return parent::toArray($request);
    }

    private function replaceFieldsWithLabels(array $data): array
    {
        $languages = array_keys(singletons()->system->languages->all);
        $result = [];

        foreach ($data as $field => $value) {
            $label = !is_int($field) && !in_array($field, $languages) ? __("fields.$field") : $field;
            $result[$label] = is_array($value) ? $this->replaceFieldsWithLabels($value) : $value;
        }

        return $result;
    }
}
