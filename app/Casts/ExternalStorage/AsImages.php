<?php

namespace App\Casts\ExternalStorage;

use App\Helpers\ErrorHelper;
use App\Helpers\RequestHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Http;

class AsImages implements CastsAttributes
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
        $value = json_decode($value, true);

        if (!$value) return [];

        $thumbsKey = "{$key}_thumbs";

        if (!$model->$thumbsKey) {
            $result = Http::withHeaders(RequestHelper::storageHttpHeaders())
                ->asForm()
                ->post(config('integrations.storage.url') . '/api/get/images', [
                    'files' => array_map(
                        fn($v) => [
                            'path' => $v,
                            'action' => $this->action,
                            'sizes' => $this->sizes,
                        ],
                        $value,
                    ),
                ])
                ->onError(function ($error) {
                    ErrorHelper::abortProjectNotWorkingError('Storage', $error);
                })
                ->json();

            $model->updateQuietly([$thumbsKey => $result['files']]);
        }

        return $value;
    }

    public function set($model, $key, $value, $attributes): mixed
    {
        $value = array_values($value);
        $value = json_encode($value);

        return [
            $key => $value,
            "{$key}_thumbs" => '[]',
        ];
    }
}
