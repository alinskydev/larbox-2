<?php

namespace App\Observers;

use App\Base\Model;
use App\Helpers\ClassHelper;
use App\Helpers\StringHelper;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SluggableObserver
{
    public function saving(Model $model): void
    {
        $params = $model->getService()->checkAndGetObserverParams('sluggable');

        $source = $params['source'];
        $destination = $params['destination'];

        if ($model->exists && !$model->isDirty($source)) return;

        $sourceValue = $model->$source;
        $sourceValue = is_array($sourceValue) ? Arr::get($sourceValue, app()->getLocale()) : $sourceValue;

        $slug = Str::slug($sourceValue);

        $query = $model->query()
            ->where($destination, $slug)
            ->whereKeyNot($model->getKey());

        if (ClassHelper::uses($model, SoftDeletes::class)) {
            $query->withTrashed();
        }

        if ($query->exists()) {
            $slug .= '-' . StringHelper::uuid();
        }

        $model->$destination = $slug;
    }
}
