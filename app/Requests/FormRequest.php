<?php

namespace App\Requests;

use App\Helpers\ErrorHelper;
use App\Helpers\FileHelper;
use App\Helpers\HtmlPurifierHelper;
use App\Helpers\ImageHelper;
use App\Helpers\RequestHelper;
use App\Helpers\StringHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FormRequest extends BaseFormRequest
{
    public array $validatedData;

    protected array $defaults = [];
    protected array $rawFields = ['-'];

    /** @var array<string, array{source: string, folder: string}> */
    protected array $fileUploads = [];

    /** @var array<string, array{source: 'local'|'external', folder: string, sizes: array<int, int>}> */
    protected array $imageUploads = [];

    private array $cachedRules = [];

    public function getValidator(): Validator
    {
        return $this->getValidatorInstance();
    }

    public function attributes(): array
    {
        $languageCodes = Arr::pluck(singletons()->system->languages->all, 'code');
        $languagesRegex = implode('|', $languageCodes);
        $languagesRegex = "/\.($languagesRegex)$/";

        $attributes = array_keys($this->rules());
        $attributes = array_combine($attributes, $attributes);

        $attributes = array_map(function ($value) use ($languagesRegex) {
            $value = preg_replace($languagesRegex, '', $value);

            return __("fields.$value");
        }, $attributes);

        return $attributes;
    }

    protected function nonLocalizedRules(): array
    {
        return [];
    }

    protected function localizedRules(): array
    {
        return [];
    }

    public function rules(): array
    {
        if (!$this->cachedRules) {
            $rules = $this->nonLocalizedRules();

            $localizedRules = $this->localizedRules();
            $languages = array_keys(singletons()->system->languages->all);

            foreach ($localizedRules as $key => $rule) {
                foreach ($languages as $language) {
                    $rules["$key.$language"] = is_array($rule) ? array_merge($rule, ['present']) : "present|$rule";
                }
            }

            $this->cachedRules = $rules;
        }

        return $this->cachedRules;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $data = parent::validationData();
        $data = StringHelper::escapeTags($data, $this->rawFields);
        // $data = HtmlPurifierHelper::process($data);

        $this->merge($data);
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        if (method_exists($this, 'additionalValidation')) {
            $this->additionalValidation();

            if ($this->validator->errors()->messages()) {
                $this->failedValidation($this->validator);
            }
        }

        $this->validatedData = $this->validated();
        $this->validatedData = Arr::dot($this->validatedData);

        $this->saveFiles($this->fileUploads, 'files');
        $this->saveFiles($this->imageUploads, 'images');

        $this->validatedData = array_filter($this->validatedData, fn($value) => !($value instanceof UploadedFile));
        $this->validatedData = Arr::undot($this->validatedData);

        foreach ($this->defaults as $field => $value) {
            data_fill($this->validatedData, $field, $value);
        }
    }

    private function saveFiles(array $savePaths, string $type): void
    {
        if (!$savePaths) return;

        foreach ($this->validatedData as $field => &$value) {
            if (!($value instanceof UploadedFile)) continue;

            foreach ($savePaths as $path => $uploadParams) {
                if (!Str::is($path, $field)) continue;

                switch ($uploadParams['source']) {
                    case 'local':
                        if (isset($uploadParams['folder'])) {
                            $value = match ($type) {
                                'files' => FileHelper::upload(
                                    file: $value,
                                    folder: $uploadParams['folder'],
                                ),
                                'images' => ImageHelper::resizeAndUpload(
                                    file: $value,
                                    folder: $uploadParams['folder'],
                                    sizes: $uploadParams['sizes'],
                                ),
                            };
                        } else {
                            $value = null;
                        }

                        break;

                    case 'external':
                        $response = Http::withHeaders(RequestHelper::storageHttpHeaders())
                            ->attach(
                                name: 'files[]',
                                contents: $value->getContent(),
                                filename: $value->getFilename() . '.' . $value->guessExtension(),
                            )
                            ->post(config('integrations.storage.url') . '/api/upload', [
                                'type' => $type,
                                'folder' => $uploadParams['folder'],
                                'size' => implode('x', $uploadParams['sizes'] ?? []),
                            ])
                            ->onError(function ($error) {
                                ErrorHelper::abortProjectNotWorkingError('Storage', $error);
                            })
                            ->json();

                        $value = $response['files'][0];
                        break;

                    default:
                        throw new \Exception('Files save source should be "local" or "external"');
                }
            }
        }
    }
}
