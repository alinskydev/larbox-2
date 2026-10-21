<?php

namespace App\Requests;

use App\Base\Model;
use App\Helpers\ClassHelper;
use App\Requests\ResourceFormRequest\DeletableFileFieldsTrait;
use Illuminate\Support\Arr;
use Modules\Seo\Traits\SeoMetaFormRequestTrait;

class ResourceFormRequest extends FormRequest
{
    use DeletableFileFieldsTrait;

    public Model $model;
    private bool $hasSeoMeta;

    public function __construct()
    {
        parent::__construct();

        $this->model ??= request()->route('model') ?? request()->route()->controller->model;
        $this->hasSeoMeta = ClassHelper::uses($this, SeoMetaFormRequestTrait::class);

        if ($this->hasSeoMeta) {
            $this->rawFields[] = 'seo_meta.*';
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();

        if ($this->hasSeoMeta) {
            $seoMetaRules = $this->seoMetaRules();
            $languages = array_keys(singletons()->system->languages->all);

            foreach ($seoMetaRules as $key => $rule) {
                foreach ($languages as $language) {
                    $rules["$key.$language"] = $rule;
                }
            }
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->updated_at && $this->model->exists && $this->updated_at !== $this->model->updated_at) {
            abort(409, __('Данные были изменены другим пользователем. Обновите страницу, чтобы увидеть изменения.'));
        }
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->processDeletableFileFields();

        if ($this->hasSeoMeta && isset($this->validatedData['seo_meta'])) {
            $this->model->fillRelations(
                oneToOne: [
                    'seo_meta' => [
                        'value' => Arr::pull($this->validatedData, 'seo_meta'),
                    ],
                ],
            );
        }
    }
}
