<?php

namespace Modules\Product\Models;

use App\Base\Model;
use App\Base\Model\ModelSoftDeleteTrait;
use App\Casts\ExternalStorage\AsImages;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use App\Observers\SluggableObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Audit\Traits\RecordActionModelTrait;
use Modules\Company\Models\Company;
use Modules\Company\Observers\Subscription\SubscriptionCheckLimitObserver;
use Modules\Product\Observers\ProductObserver;
use Modules\Seo\Traits\SeoMetaModelTrait;
use Modules\Stock\Models\Product as StockProduct;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property ?int $parent_id
 * @property int $creator_id
 * @property int $company_id
 * @property int $category_id
 * @property int $brand_id
 * @property string $name
 * @property string $full_text
 * @property string $slug
 * @property ?string $description
 * @property string $barcode
 * @property array $image_list
 * @property array $image_list_thumbs
 * @property string $created_at
 * @property string $updated_at
 *
 * @property ?static $parent
 * @property Collection<static> $children
 * @property User $creator
 * @property Company $company
 * @property Category $category
 * @property Brand $brand
 * @property Collection<Option> $options
 * @property ?Price $price
 * @property Collection<StockProduct> $stock_products
 */
class Product extends Model
{
    use ModelSoftDeleteTrait;
    use SeoMetaModelTrait;
    use RecordActionModelTrait;

    protected $table = 'product';

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    protected function casts(): array
    {
        return [
            'image_list' => AsImages::class . ':scaleDown_100|500',
            'image_list_thumbs' => 'array',
        ];
    }

    public function observerParams(): array
    {
        return [
            'daily_limit' => 2000,
            'sluggable' => [
                'source' => 'name',
                'destination' => 'slug',
            ],
            'audit_record_action' => [
                'excluded' => ['image_list_thumbs', 'image_list_thumbs.*'],
                'relations' => [
                    'belongsTo' => [
                        'parent_id' => 'parent.full_text',
                        'brand_id' => 'brand.name',
                        'category_id' => 'category.name',
                    ],
                    'manyToMany' => [
                        'options' => 'options.*.name',
                    ],
                ],
            ],
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id')->withTrashed();
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id')->withTrashed();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id')->withTrashed();
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'product_option_ref', 'product_id', 'option_id')
            ->wherePivotIn('product_company_id', user()->getService()->holdingCompanyIds());
    }

    public function price(): HasOne
    {
        return $this->hasOne(Price::class, 'product_id');
    }

    public function stock_products(): HasMany
    {
        return $this->hasMany(StockProduct::class, 'product_id');
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            SluggableObserver::class,
            DailyLimitCreateObserver::class,
            SubscriptionCheckLimitObserver::class,
            ProductObserver::class,
        ]);
    }
}
