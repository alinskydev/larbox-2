---
name: model-rules
description: Mandatory standards for Eloquent models in modules/<Module>/Models (extending App\Base\Model), incl. model builders (modules/*/Builders, App\Base\EloquentBuilder + traits), casts (app/Casts), nested sets (app/NestedSet), adjacency lists and model traits (app/Base/Model, modules/*/Traits). Use when creating or editing a model, its relations, casts, auto filters, observer params, builder, tree behavior or model trait.
---

# Model Rules

Models live in `modules/<Module>/Models/<Name>.php`, namespace `Modules\<Module>\Models`, extend `App\Base\Model` (sole exception: `User\Models\AccessToken` extends Sanctum `PersonalAccessToken`). Canonical: `modules/Trade/Models/Purchase.php` (+ line model `PurchaseProduct.php`), `modules/Product/Models/Brand.php`, `modules/Product/Models/Category.php` (nested set), `modules/User/Models/User.php` (builder + service).

## Base model already provides (`app/Base/Model.php`)
- `$guarded` = id/created_at/updated_at/deleted_at; every other real column is mass-assignable (column list cached by `App\Database\PostgresSchemaBuilder::getColumnListing()`); unknown keys are silently dropped by `fill()`.
- `$hidden` += deleted_at/sort_index/pivot/seo_meta; timestamps cast to `string`; `is_deleted` appended on soft-deletable models; `setKeysForSaveQuery()` adds original `company_id` to UPDATE/DELETE.
- `newQuery()` applies `$autoFilters` (skipped without `user()` or via `Model::queryWithoutAutoFilters()`).
- `ModelSafelyTrait`: `safelySave($attributes)` (fill + `touch()`, or `save()` without timestamps), `safelyDelete()`, `safelyRestore()`, `safelyDBProcess($cb)` (also opens transactions on `$additionalConnections`). `ModelRelationsTrait`: `fillRelations(oneToOne:, oneToMany:, manyToMany:)` (set in FormRequests), written after `created`/`updated`.
- `getService(): ModelService`, `observerParams(): array`, `$resourceClass`, flags `$shouldHandleDailyLimit`, `$shouldSkipAutoFilters`.

## Rules
- Always set `protected $table`: `<module>_<entity>` singular snake_case; module's main entity = bare module name (`product`, `stock`, `company`, `user`). Class name has no module prefix (`Stock\Models\Product` → `stock_product`, `Trade\Models\SaleProduct` → `trade_sale_product`). Alias same-named imports `<Module><Name>` (`Product as StockProduct`, `Product as ProductProduct`).
- PHPDoc: every column as `@property` in migration order (`?int` nullable, `float` decimals, `array` JSON, `string` timestamps, `bool` flags, `?string|array` images), blank ` *`, relations (`Collection<X>`, `?X` nullable, `?static $parent`, `Collection<static> $children`), then `@property class-string<XResource> $resourceClass` / `@method static XBuilder query()` when overridden.
- Member order: traits (one `use` per line) → consts (`UPDATED_AT`, domain consts `AuthCode::MAX_ATTEMPTS`) → `$connection`/`$table`/`$primaryKey`/`$keyType`/`$incrementing`/`$timestamps` → `$hidden` → `$casts` → `$resourceClass` → `$autoFilters` → custom public props (`Sale::$saleProducts`) → `observerParams()` → accessors → relations → `getService()` → `newEloquentBuilder()` → `booted()` last.
- Timestamps: append-only `public const UPDATED_AT = null;` (`Batch`, `RecalculationProduct`, `RecordAction`); update-only `CREATED_AT = null` (`Price`); none → `public $timestamps = false;` (line tables `PurchaseProduct`, `SaleProduct`, `Stock\Models\Product`; `Profile`, `Meta`, `Setting`).
- 1:1 extension tables: `$primaryKey = '<owner>_id'`, `$incrementing = false`, owner key in `$hidden` (`Profile`, `Credential`). String keys add `$keyType = 'string'` (`Setting`, `AuthCode`).
- Soft delete via `use ModelSoftDeleteTrait` for user-managed entities (`Brand`, `Category`, `Product`, `Stock`, `User`, `Company`); documents, lines, logs, `Role`, `Option` are hard-deleted. Its `restore()` saves without events (only `restoring`/`restored` fire).
- Non-default DB: `protected $connection = 'history';` (`History\Models\StoreHttpLog`).
- Company-owned rows (line tables too) keep their own `company_id`, attach `CompanyIdObserver`, declare `protected array $autoFilters = ['holding_companies' => ['company_id']];` (`Price` uses `current_company`). Keys: `current_company`, `nested_companies`, `holding_companies`, `administered_companies`; values = columns or `relation->column` paths (OR-ed).
- `$casts`: `'float'` decimals, `'array'` JSON and localized columns (`name`, `description`), `'encrypted'` / `'encrypted:array'` for secrets (`Integration\Models\Token`, `Company\Models\Credential`).
- Upload casts (`app/Casts`) must match the FormRequest `$imageUploads`/`$fileUploads` `source`: `'external'` → `ExternalStorage\AsImage|AsImages::class . ':scaleDown_100|500'` (`<action>_<sizes>`) plus `'<field>_thumbs' => 'array'` — thumbs fetched from Storage on first read and saved via `updateQuietly()` (`Brand::image`, `Product::image_list`); `'local'` → `LocalStorage\AsImage::class . ':scaleDown_25|scaleDown_100|scaleDown_500'`, size map built by `ImageHelper::populateSizes()`, no thumbs column (`Company::logo`, `Language::image`). `AsFile`/`AsFiles` (both dirs) return URLs; unused so far.
- `observerParams()` returns only keys read by attached observers through `ModelService::checkAndGetObserverParams()` (throws if missing): `daily_limit` int (`DailyLimitCreateObserver`), `sluggable` `[source, destination]` (`SluggableObserver`), `audit_record_action` `[excluded, relations: [belongsTo: fk => 'relation.field', oneToMany: relation => [fields], manyToMany: relation => 'relation.*.field']]` (`RecordActionModelTrait`; see `Product`, `Specification`).
- Relations: public, typed return, snake_case for multi-word (`stock_products`, `responsible_users`), FK always explicit. Append `->withTrashed()` iff the target uses `ModelSoftDeleteTrait` (`Purchase::stock()` yes, `Purchase::batch()` no). Order: `parent`/`children` → `creator` → `company` → others.
- Standard pair: `creator()` → `User` via `creator_id`, `company()` → `Company` via `company_id`, both `->withTrashed()`.
- `belongsToMany` names the `_ref` pivot and both keys; a pivot partitioned by owner company adds `->wherePivotIn('product_company_id', user()->getService()->holdingCompanyIds())` (`Product::options()`).
- Observers only in `protected static function booted(): void` via `self::observe([...])`, order: `CreatorIdObserver`, `CompanyIdObserver`, `IPObserver`, `SluggableObserver`, `DailyLimitCreateObserver`, `SubscriptionCheckLimitObserver`, module observer. `SubscriptionCheckLimitObserver` requires an entry in `SubscriptionRecordLimitEnum::valueByClassName()`.
- Accessors use `get<Name>Attribute()` (`Category::getLocalizedNameAttribute()`).
- Domain logic: `getService(): XService { return new XService($this); }`, `XService extends App\Base\ModelService` in `modules/<Module>/Services/` (`Purchase`, `Sale`, `Stock`, `User`).
- Variant without audit/limits = subclass in `Models/<Model>/` overriding `booted()` and emptying `bootRecordActionModelTrait()` (`Product\Models\Product\ProductImport`).

## Builders (`modules/<Module>/Builders/<Model>Builder.php`)
- Only for reusable chainable queries: extends `App\Base\EloquentBuilder`, `@extends EloquentBuilder<Model>`, methods return `static` (`UserBuilder::whereFullNameLike()`). Wire with `newEloquentBuilder($query, $builderClass = null): XBuilder` returning `parent::newEloquentBuilder($query, XBuilder::class)` + `@method static XBuilder query()`.
- Already available: `whereLower()`, `getQueryWithRelations()` (manual eager load, exports/jobs), `cloneForSummary()`; `filterByUser()`, `filterByCreator()`, `filterByCurrentCompany()`, `filterByNestedCompanies()`, `filterByHoldingCompanies()`, `filterByAdministeredCompanies()` (`EloquentBuilder\EloquentBuilderFilterByUserTrait`), `filterByOwnRecords($route)` (`EloquentBuilderFilterByPermissionsTrait`); `App\Base\QueryBuilder`: `whereIlike()`/`orWhereIlike()` (escaped `%value%`), `whereInThroughAny()`.

## Trees
- Nested set (`app/NestedSet`, only `Product\Models\Category`): `use NestedSetModelTrait` (first trait), `newEloquentBuilder()` returning `NestedSetEloquentBuilder`, `@method static EloquentBuilder<static>|NestedSetEloquentBuilder<static> query()`. Needs `parent_id`/`lft`/`rgt`, `company_id` + `company()`, `getLocalizedNameAttribute()` (appended on retrieve). One root (`parent_id` null) per holding, created in `CompanyObserver::created()` via `makeRoot()->safelySave()`; new nodes auto-append to it under `DatabaseTransactionHelper::lock("<table>:tree:<companyId>")`; global `lft` order; restore blocked under a trashed ancestor. HTTP: `NestedSetController` (`tree`, `move`), `NestedSetMoveRequest`, resource extends `NestedSetResource`.
- Adjacency list: `app/AdjacencyList/` is empty; self-references use `parent(): BelongsTo` (`->withTrashed()`) + `children(): HasMany` on `parent_id` (`Company`, `Product`).

## Model traits
- Shared ones in `app/Base/Model/`; feature ones in `modules/<Module>/Traits/<Feature>ModelTrait.php` with `@mixin Model`: `SeoMetaModelTrait` (`seo_meta()` MorphOne + `seo_meta`/`seo_meta_as_array` accessors), `RecordActionModelTrait` (`$recordActionOldData`, `audit_record_actions()` MorphMany, `bootRecordActionModelTrait()` attaches `RecordActionCreateObserver`). Order: props → accessors → relations → service getters → `boot<TraitName>()` last.

## Template
```php
<?php

namespace Modules\<Module>\Models;

use App\Base\Model;
use App\Base\Model\ModelSoftDeleteTrait;
use App\Observers\CompanyIdObserver;
use App\Observers\CreatorIdObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\Models\Company;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Models\User;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $company_id
 * @property string $name
 * @property float $price
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 * @property Company $company
 * @property Collection<<Name>Product> $products
 */
class <Name> extends Model
{
    use ModelSoftDeleteTrait;

    protected $table = '<module>_<name>';

    protected $casts = [
        'price' => 'float',
    ];

    protected array $autoFilters = [
        'holding_companies' => ['company_id'],
    ];

    public function observerParams(): array
    {
        return [
            'daily_limit' => 100,
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
        return $this->hasMany(<Name>Product::class, '<name>_id');
    }

    protected static function booted(): void
    {
        self::observe([
            CreatorIdObserver::class,
            CompanyIdObserver::class,
            DailyLimitCreateObserver::class,
        ]);
    }
}
```

## Don't
- Declare `$fillable`/`$guarded`/`$dates`/`$appends`/`protected $with`, a `casts()` method, re-hide base hidden fields or cast timestamps — zero usages.
- Use `SoftDeletes` directly, `HasFactory`, `Attribute::make()`, `scopeX()` local scopes, PHP attributes, enum/bool/datetime casts — zero usages.
- Override `boot()` or register model observers outside `booted()`.
- Wrap saves in `DB::transaction()` or use `lockForUpdate()`; use `safely*()` and `DatabaseTransactionHelper::lock()`/`lockModel()`.
- Write raw `'ilike', "%$v%"` in builders; use `whereIlike()` (escapes wildcards).
- Add `observerParams()` keys without the matching observer/trait, or vice versa.
