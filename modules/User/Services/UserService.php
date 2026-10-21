<?php

namespace Modules\User\Services;

use App\Base\ModelService;
use Modules\Company\Models\Company;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Helpers\RoleHelper;
use Modules\User\Models\User;

/**
 * @property User $model
 */
class UserService extends ModelService
{
    public function isSuperadmin(): bool
    {
        return $this->model->id === 1;
    }

    public function createNewAccessToken(string $name = 'default'): void
    {
        $token = $this->model
            ->createToken(
                name: $name,
                expiresAt: now()->addDays(3),
            )
            ->plainTextToken;

        $this->model->newAccessToken = "Bearer $token";
    }

    public function allRoleRoutes(): array
    {
        if (!isset($this->model->allRoleRoutes)) {
            $this->model->allRoleRoutes = $this->model->roles
                ->flatMap(fn($role) => RoleHelper::routeList($role))
                ->unique()
                ->toArray();
        }

        return $this->model->allRoleRoutes;
    }

    public function nestedCompanyIds(): array
    {
        if (!UserTypeEnum::isCompanyType($this->model->type)) return [];

        if (!isset($this->model->nestedCompanyIds)) {
            $this->model->nestedCompanyIds = $this->companyIdsWithChildren([$this->model->company_id]);
        }

        return $this->model->nestedCompanyIds;
    }

    public function holdingCompanyIds(): array
    {
        if (!UserTypeEnum::isCompanyType($this->model->type)) return [];

        if (!isset($this->model->holdingCompanyIds)) {
            if ($parentId = $this->model->company->parent_id) {
                $this->model->holdingCompanyIds = $this->companyIdsWithChildren([$parentId]);
            } else {
                $this->model->holdingCompanyIds = $this->nestedCompanyIds();
            }
        }

        return $this->model->holdingCompanyIds;
    }

    public function administeredCompanyIds(): array
    {
        if ($this->model->type !== UserTypeEnum::ADMIN->value) return [];

        if (!isset($this->model->administeredCompanyIds)) {
            $ids = $this->model->responsible_companies()->allRelatedIds()->toArray();
            $this->model->administeredCompanyIds = $this->companyIdsWithChildren($ids);
        }

        return $this->model->administeredCompanyIds;
    }

    private function companyIdsWithChildren(array $ids): array
    {
        return Company::query()
            ->select(['id'])
            ->whereIn('id', $ids)
            ->orWhereIn('parent_id', $ids)
            ->getQuery()
            ->get()
            ->pluck('id')
            ->toArray();
    }
}
