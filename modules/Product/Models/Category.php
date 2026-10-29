<?php

namespace Modules\Product\Models;

use App\Base\EloquentBuilder;
use App\Base\Model;
use App\Base\Model\ModelSoftDeleteTrait;
use App\NestedSet\NestedSetEloquentBuilder;
use App\NestedSet\NestedSetModelTrait;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use App\Observers\SluggableObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Modules\Company\Models\Company;
use Modules\Company\Observers\Subscription\SubscriptionCheckLimitObserver;
use Modules\Product\Resources\CategoryResource;
use Modules\Seo\Traits\SeoMetaModelTrait;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $company_id
 * @property ?int $parent_id
 * @property int $lft
 * @property int $rgt
 * @property array $name
 * @property string $slug
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 * @property Company $company
 * @property Collection<Product> $products
 *
 * @property class-string<CategoryResource> $resourceClass
 * @method static EloquentBuilder<static>|NestedSetEloquentBuilder<static> query()
 */
class Category extends Model
{
    use NestedSetModelTrait;
    use ModelSoftDeleteTrait;
    use SeoMetaModelTrait;

    protected $table = 'product_category';

    public string $resourceClass = CategoryResource::class;

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
        ];
    }

    public function observerParams(): array
    {
        return [
            'daily_limit' => 500,
            'sluggable' => [
                'source' => 'name',
                'destination' => 'slug',
            ],
        ];
    }

    public function getLocalizedNameAttribute(): ?string
    {
        return Arr::get($this->name, app()->getLocale());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id')->withTrashed();
    }

    public function newEloquentBuilder($query, $builderClass = null): NestedSetEloquentBuilder
    {
        return parent::newEloquentBuilder($query, NestedSetEloquentBuilder::class);
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            SluggableObserver::class,
            DailyLimitCreateObserver::class,
            SubscriptionCheckLimitObserver::class,
        ]);
    }
}
