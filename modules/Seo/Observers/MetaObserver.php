<?php

namespace Modules\Seo\Observers;

use Mews\Purifier\Facades\Purifier;
use Modules\Seo\Models\Meta;

class MetaObserver
{
    public function saving(Meta $model): void
    {
        if ($model->value) {
            $model->value = array_map(
                fn($value) => $value ? Purifier::clean($value, ['HTML.Allowed' => 'meta[name|property|content]']) : null,
                $model->value,
            );

            $model->parsed_value = array_map(
                function ($value) {
                    if (!$value || $value === strip_tags($value)) return [];

                    $result = [];

                    $dom = new \DOMDocument();
                    $dom->loadHTML('<?xml encoding="UTF-8">' . $value);
                    $elements = $dom->getElementsByTagName('meta');

                    foreach ($elements as $key => $element) {
                        foreach ($element->attributes as $attribute) {
                            $result[$key][$attribute->nodeName] = $attribute->nodeValue;
                        }
                    }

                    return $result;
                },
                $model->value,
            );
        } else {
            $model->parsed_value = array_map(fn($value) => [], singletons()->system->languages->all);
        }
    }
}
