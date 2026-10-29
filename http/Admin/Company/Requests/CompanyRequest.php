<?php

namespace Http\Admin\Company\Requests;

use App\Helpers\Validation\ValidationFileRulesHelper;
use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use App\Rules\PhoneRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Company\Models\Company;
use Modules\Company\Models\Subscription;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Models\Role;
use Modules\User\Models\User;

class CompanyRequest extends ResourceFormRequest
{
    protected array $rawFields = [
        'director.password',
        'director.password_confirmation',
    ];

    protected array $fileUploads = [
        'logo' => [
            'source' => 'local',
            'folder' => 'images',
        ],
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Company::query()
                        ->filterByAdministeredCompanies('id')
                        ->whereNull('parent_id')
                        ->when($this->model->exists, fn($query) => $query->where('id', '!=', $this->model->id)),
                    errorMessage: __('Максимальный уровень вложенности: 2'),
                ),
            ],
            'subscription_id' => [
                'required',
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Subscription::query(),
                ),
            ],
            'name' => 'required|string|max:255',
            ...$this->deletableFileFieldsSingleValidation(
                field: 'logo',
                rules: ValidationFileRulesHelper::image(),
            ),
            'address' => 'required|string|max:255',

            'director.username' => [
                'required',
                'email',
                'max:255',
                Rule::unique((new User())->getTable(), 'username')->ignore($this->model->director?->id),
            ],
            'director.phone' => [
                'required',
                new PhoneRule(),
            ],
            'director.password' => [
                Rule::requiredIf(!$this->model->exists),
                'nullable',
                Password::defaults(),
            ],
            'director.password_confirmation' => [
                'required_with:director.password',
                'same:director.password',
            ],
            'director.roles' => [
                'required',
                'array',
                'max:10',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Role::query()->where('type', UserTypeEnum::COMPANY_DIRECTOR->value),
                ),
            ],
            'director.roles.*' => 'required|integer|distinct',

            'credential.telegram_chats' => 'array|max:5',
            'credential.telegram_chats.*.name' => 'required|string|max:255',
            'credential.telegram_chats.*.chat_id' => 'required|integer',

            'is_confirmed' => 'accepted',
        ];
    }

    protected function additionalValidation(): void
    {
        if ($this->model->exists && $this->parent_id && $this->model->children()->exists()) {
            $this->validator->errors()->add('parent_id', __('Максимальный уровень вложенности: 2'));
            return;
        }
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        if ($this->model->exists) {
            Arr::forget($this->validatedData, ['parent_id']);
        }

        $director = $this->validatedData['director'];
        $director['type'] = UserTypeEnum::COMPANY_DIRECTOR->value;
        $director['manyToMany']['roles'] = Arr::pull($director, 'roles');

        $this->model->fillRelations(
            oneToOne: [
                'director' => $director,
                'credential' => $this->validatedData['credential'] ?? [],
            ],
        );
    }
}
