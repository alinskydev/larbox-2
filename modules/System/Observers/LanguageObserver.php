<?php

namespace Modules\System\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\System\Exceptions\LanguageException;
use Modules\System\Models\Language;

class LanguageObserver
{
    public function saving(Language $model): void
    {
        if ($model->getOriginal('is_active') && !$model->is_active) {
            if ($model->is_main) {
                throw new LanguageException(LanguageException::MAIN_LANGUAGE_DEACTIVATION);
            }

            if ($model->code === app()->getLocale()) {
                throw new LanguageException(LanguageException::CURRENT_LANGUAGE_DEACTIVATION);
            }
        }

        if ($model->is_main) {
            $model->is_active = 1;
            $model->query()
                ->where('id', '!=', $model->id)
                ->getQuery()
                ->update(['is_main' => 0]);
        }
    }

    public function saved(Language $model): void
    {
        Cache::forget('system_languages');
        Cache::tags('system_public_data')->flush();
    }
}
