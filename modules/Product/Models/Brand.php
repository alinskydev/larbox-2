<?php

namespace Modules\Product\Models;

use App\Base\Model;
use App\Base\Model\ModelSoftDeleteTrait;
use App\Casts\ExternalStorage\AsImage;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use App\Observers\SluggableObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\Models\Company;
use Modules\Company\Observers\Subscription\SubscriptionCheckLimitObserver;
use Modules\Seo\Traits\SeoMetaModelTrait;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $company_id
 * @property string $name
 * @property string $slug
 * @property ?string|array $image
 * @property array $image_thumbs
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 * @property Company $company
 * @property Collection<Product> $products
 */
class Brand extends Model
{
    use ModelSoftDeleteTrait;
    use SeoMetaModelTrait;

    protected $table = 'product_brand';

    protected $casts = [
        'image' => AsImage::class . ':scaleDown_100|500',
        'image_thumbs' => 'array',
    ];

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

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
        return $this->hasMany(Product::class, 'brand_id')->withTrashed();
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
