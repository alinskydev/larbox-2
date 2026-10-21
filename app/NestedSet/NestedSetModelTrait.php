<?php

namespace App\NestedSet;

use App\Base\Model;
use App\Helpers\Database\DatabaseTransactionHelper;
use Kalnoy\Nestedset\AncestorsRelation;
use Kalnoy\Nestedset\NodeTrait;
use Modules\Company\Models\Company;

/**
 * @mixin Model
 *
 * @property int $company_id
 * @property string $deleted_at
 *
 * @property Company $company
 */
trait NestedSetModelTrait
{
    use NodeTrait {
        ancestors as kalnoyAncestors;
        makeRoot as kalnoyMakeRoot;
    }

    public bool $isRoot = false;

    public function getLftName()
    {
        return 'lft';
    }

    public function getRgtName()
    {
        return 'rgt';
    }

    public function getStateAttribute(): array
    {
        return [
            'disabled' => (bool)$this->deleted_at,
            'hidden' => (bool)$this->deleted_at,
            'opened' => true,
        ];
    }

    public function ancestors(): AncestorsRelation
    {
        return $this->kalnoyAncestors()->whereNotNull('parent_id');
    }

    public function makeRoot(): static
    {
        $this->isRoot = true;
        return $this->kalnoyMakeRoot();
    }

    protected static function bootNestedSetModelTrait(): void
    {
        static::addGlobalScope('order', function ($query) {
            $query->orderBy('lft');
        });

        static::retrieved(function (self $model) {
            $model->append(['localized_name']);
        });

        static::created(function (self $model) {
            if (!$model->isRoot) {
                $model->safelyDBProcess(function () use ($model) {
                    $companyId = $model->company->parent_id ?: $model->company_id;
                    DatabaseTransactionHelper::lock("{$model->getTable()}:tree:$companyId");

                    $model->query()->whereNull('parent_id')->firstOrFail()->appendNode($model);
                });
            }
        });

        static::restoring(function (self $model) {
            $parent = $model->ancestors()->onlyTrashed()->first();
            if ($parent) throw new \Exception(__('Восстановление записи без родителя невозможно'));
        });
    }
}
