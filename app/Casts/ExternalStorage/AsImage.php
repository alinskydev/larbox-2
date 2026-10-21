<?php

namespace App\Casts\ExternalStorage;

use App\Helpers\ErrorHelper;
use App\Helpers\RequestHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Http;

class AsImage implements CastsAttributes
{
    private string $action;
    private string $sizes;

    public function __construct(string $params)
    {
        $params = explode('_', $params);

        $this->action = $params[0];
        $this->sizes = $params[1];
    }

    public function get($model, $key, $value, $attributes): mixed
    {
        if (!$value) return null;

        $thumbsKey = "{$key}_thumbs";

        if (!$model->$thumbsKey) {
            $result = Http::withHeaders(RequestHelper::storageHttpHeaders())
                ->asForm()
                ->post(config('integrations.storage.url') . '/api/get/images', [
                    'files' => [
                        [
                            'path' => $value,
                            'action' => $this->action,
                            'sizes' => $this->sizes,
                        ],
                    ],
                ])
                ->onError(function ($error) {
                    ErrorHelper::abortProjectNotWorkingError('Storage', $error);
                })
                ->json();

            $model->updateQuietly([$thumbsKey => $result['files'][0]]);
        }

        return $value;
    }

    public function set($model, $key, $value, $attributes): mixed
    {
        return [
            $key => $value,
            "{$key}_thumbs" => '[]',
        ];
    }
}
