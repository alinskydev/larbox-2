<?php

namespace Http\Admin\Company\Requests\Company;

use App\Requests\ResourceFormRequest;
use Illuminate\Support\Arr;

class CompanyUpdateCredentialRequest extends ResourceFormRequest
{
    protected array $rawFields = [
        'credential.store.new_password',
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'credential.store.username' => 'nullable|string|max:255',
            'credential.store.new_password' => 'nullable|string|max:255',
        ];
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        if (isset($this->validatedData['credential']['store'])) {
            $store = &$this->validatedData['credential']['store'];

            $store['password'] = array_key_exists('new_password', $store)
                ? Arr::pull($store, 'new_password')
                : $this->model->credential->store['password'];
        }

        $this->model->fillRelations(
            oneToOne: [
                'credential' => $this->validatedData['credential'] ?? [],
            ],
        );
    }
}
