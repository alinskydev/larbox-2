<?php

namespace App\Requests\ResourceFormRequest;

use App\Requests\ResourceFormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @mixin ResourceFormRequest
 */
trait DeletableFileFieldsTrait
{
    private array $deletableFileFields = [
        'single' => [],
        'multiple' => [],
    ];

    protected function deletableFileFieldsSingleValidation(
        string $field,
        array $rules,
        bool $areKeysRequired = true,
    ): array {
        $this->deletableFileFields['single'][] = $field;

        return [
            $field => $rules,
            "{$field}_old_keys" => [
                $areKeysRequired ? null : 'sometimes',
                'required',
                'json',
            ],
        ];
    }

    protected function deletableFileFieldsMultipleValidation(
        string $field,
        array $rules,
        bool $areKeysRequired = true,
    ): array {
        $this->deletableFileFields['multiple'][] = $field;

        return [
            $field => [
                'array',
                in_array('required', $rules) ? 'required' : null,
            ],
            "$field.*" => $rules,
            "{$field}_old_keys" => [
                $areKeysRequired ? null : 'sometimes',
                'required',
                'json',
            ],
        ];
    }

    protected function processDeletableFileFields(): void
    {
        $this->validatedData = Arr::dot($this->validatedData);

        $this->processDeletableFileFieldsSingle();
        $this->processDeletableFileFieldsMultiple();

        $this->validatedData = Arr::undot($this->validatedData);
    }

    private function processDeletableFileFieldsSingle(): void
    {
        $fields = array_unique($this->deletableFileFields['single']);

        foreach ($fields as $field) {
            $oldKeysField = "{$field}_old_keys";

            foreach ($this->validatedData as $dataKey => $value) {
                if (!Str::is($oldKeysField, $dataKey)) continue;

                $value = json_decode($value, true);

                if (!$value) {
                    $targetField = preg_replace('/_old_keys$/', '', $dataKey);
                    $this->validatedData[$targetField] = $this->validatedData[$targetField] ?? null;
                }
            }
        }
    }

    private function processDeletableFileFieldsMultiple(): void
    {
        $fields = array_unique($this->deletableFileFields['multiple']);

        foreach ($fields as $field) {
            $oldKeysField = "{$field}_old_keys";

            foreach ($this->validatedData as $dataKey => $value) {
                if (!Str::is($oldKeysField, $dataKey)) continue;

                $targetField = preg_replace('/_old_keys$/', '', $dataKey);

                $newFiles = array_filter(
                    $this->validatedData,
                    fn($key) => Str::is("$targetField.*", $key),
                    ARRAY_FILTER_USE_KEY,
                );

                $oldKeys = json_decode($value, true);

                $oldFiles = $this->model->getRawOriginal($targetField, '[]');
                $oldFiles = json_decode($oldFiles, true);
                $oldFiles = Arr::only($oldFiles, $oldKeys);

                $this->validatedData[$targetField] = array_merge($oldFiles, $newFiles);
            }
        }
    }
}
