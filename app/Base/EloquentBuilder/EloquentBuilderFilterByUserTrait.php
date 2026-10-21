<?php

namespace App\Base\EloquentBuilder;

use App\Base\EloquentBuilder;
use Modules\User\Enums\UserTypeEnum;

/**
 * @mixin EloquentBuilder
 */
trait EloquentBuilderFilterByUserTrait
{
    public function filterByUser(string $field): static
    {
        return $this->where($field, user()->id);
    }

    public function filterByCreator(string|array $fields = 'creator_id'): static
    {
        return $this->where(function ($query) use ($fields) {
            foreach ((array)$fields as $field) {
                $query->orWhere($field, user()->id);
            }
        });
    }

    public function filterByCurrentCompany(string $path = 'company_id'): static
    {
        return $this->filterForCompanyUsers(
            path: $path,
            ids: [user()->company_id],
        );
    }

    public function filterByNestedCompanies(string $path = 'company_id'): static
    {
        return $this->filterForCompanyUsers(
            path: $path,
            ids: user()->getService()->nestedCompanyIds(),
        );
    }

    public function filterByHoldingCompanies(string $path = 'company_id'): static
    {
        return $this->filterForCompanyUsers(
            path: $path,
            ids: user()->getService()->holdingCompanyIds(),
        );
    }

    public function filterByAdministeredCompanies(string $path = 'company_id'): static
    {
        if (user()->getService()->isSuperadmin()) {
            return $this->whereRaw('1=1');
        }

        $ids = user()->getService()->administeredCompanyIds();

        return match (user()->type) {
            UserTypeEnum::ADMIN->value => $this->filterPathByIds($path, $ids),
            UserTypeEnum::COMPANY_DIRECTOR->value => $this->whereRaw('1=1'),
            UserTypeEnum::COMPANY_EMPLOYEE->value => $this->whereRaw('1=1'),
        };
    }

    private function filterForCompanyUsers(string $path, array $ids): static
    {
        return match (user()->type) {
            UserTypeEnum::ADMIN->value => $this->whereRaw('1=1'),
            UserTypeEnum::COMPANY_DIRECTOR->value => $this->filterPathByIds($path, $ids),
            UserTypeEnum::COMPANY_EMPLOYEE->value => $this->filterPathByIds($path, $ids),
        };
    }

    private function filterPathByIds(string $path, array $ids): static
    {
        $pathParts = explode('->', $path);

        if (count($pathParts) === 1) {
            $this->whereIn($pathParts[0], $ids);
        } else {
            $field = array_pop($pathParts);

            $this->whereHas(
                implode('.', $pathParts),
                function ($query) use ($field, $ids) {
                    $query->whereIn($field, $ids);
                },
            );
        }

        return $this;
    }
}
