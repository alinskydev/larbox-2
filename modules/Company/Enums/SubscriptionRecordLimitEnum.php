<?php

namespace Modules\Company\Enums;

use App\Base\EnumTrait;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Specification;
use Modules\User\Models\Role;
use Modules\User\Models\User;

enum SubscriptionRecordLimitEnum: string
{
    use EnumTrait;

    case PRODUCT = 'product';
    case PRODUCT_BRAND = 'product_brand';
    case PRODUCT_CATEGORY = 'product_category';
    case PRODUCT_SPECIFICATION = 'product_specification';

    case USER = 'user';
    case USER_ROLE = 'user_role';

    public static function labels(): array
    {
        return [
            self::PRODUCT->value => __('Продукты'),
            self::PRODUCT_BRAND->value => __('Бренды'),
            self::PRODUCT_CATEGORY->value => __('Категории'),
            self::PRODUCT_SPECIFICATION->value => __('Характеристики'),

            self::USER->value => __('Пользователи'),
            self::USER_ROLE->value => __('Роли'),
        ];
    }

    public static function valueByClassName(string $className): string
    {
        $classes = array_flip([
            self::PRODUCT->value => Product::class,
            self::PRODUCT_BRAND->value => Brand::class,
            self::PRODUCT_CATEGORY->value => Category::class,
            self::PRODUCT_SPECIFICATION->value => Specification::class,

            self::USER->value => User::class,
            self::USER_ROLE->value => Role::class,
        ]);

        return $classes[$className];
    }

    public static function defaults(): array
    {
        $values = self::values();
        $values = array_fill_keys($values, 0);

        return $values;
    }
}
