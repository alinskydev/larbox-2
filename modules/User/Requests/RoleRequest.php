<?php

namespace Modules\User\Requests;

use App\Requests\ResourceFormRequest;
use Illuminate\Support\Arr;
use Modules\User\Exceptions\RoleException;
use Modules\User\Helpers\RoleHelper;
use Modules\User\Models\User;

class RoleRequest extends ResourceFormRequest
{
    public function __construct()
    {
        parent::__construct();

        if ($this->model->exists) {
            if ($this->model->id === 1) {
                throw new RoleException(RoleException::UPDATING_FORBIDDEN);
            }

            if (user()->roles->pluck('id')->contains($this->model->id)) {
                throw new RoleException(RoleException::OWN_ROLE_UPDATING);
            }
        }
    }

    public function nonLocalizedRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'routes' => 'present|array|max:1000',
            'routes.*' => 'required|string',

            'is_confirmed' => 'accepted',
        ];
    }

    protected function validateRoutes(string $prefix, ?User $user = null): void
    {
        $availableRoutes = RoleHelper::routeTree($user);
        $availableRoutes = Arr::get($availableRoutes, $prefix) ?? [];
        $availableRoutes = Arr::dot($availableRoutes);

        $unavailableRoutes = array_diff($this->validated('routes'), $availableRoutes);

        if ($unavailableRoutes) {
            $this->validator->errors()->add('routes', __('Права ":routes" недоступны', [
                'routes' => implode(', ', $unavailableRoutes),
            ]));
        }
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'routes' => array_filter((array)$this->routes, fn($value) => !is_string($value) || !str_contains($value, '.*')),
        ]);
    }
}
