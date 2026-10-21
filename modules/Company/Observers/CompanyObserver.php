<?php

namespace Modules\Company\Observers;

use Modules\Company\Exceptions\CompanyException;
use Modules\Company\Models\Company;
use Modules\Product\Models\Category;

class CompanyObserver
{
    public function creating(Company $model): void
    {
        // Partitions limit

        $partitionLimit = 5000;

        if (Company::query()->withTrashed()->max('id') >= $partitionLimit) {
            throw new CompanyException(CompanyException::COMPANIES_LIMIT_REACHED, [
                'limit' => $partitionLimit,
            ]);
        }
    }

    public function created(Company $model): void
    {
        if (!$model->credential()->exists()) {
            $model->credential()->create();
        }

        if (!$model->parent_id) {
            $category = (new Category([
                'creator_id' => user()->id,
                'company_id' => $model->id,
                'name' => array_map(fn($value) => "Root $model->id", singletons()->system->languages->all),
            ]));

            $category->makeRoot()->safelySave();
        }
    }

    public function deleted(Company $model): void
    {
        $model->children()->delete();
    }

    public function restored(Company $model): void
    {
        $model->children()->restore();
    }
}
