---
name: model-rules
description: Mandatory standards for Eloquent models in modules/<Module>/Models (extending App\Base\Model), incl. model builders (modules/*/Builders, App\Base\EloquentBuilder + traits), casts (app/Casts), nested sets (app/NestedSet), self-referencing trees and model traits (app/Base/Model, modules/*/Traits). Use when creating or editing a model, its relations, casts, auto filters, observer params, builder, tree behavior or model trait.
---

# Model Rules

Models live in `modules/<Module>/Models/<Name>.php`, namespace `Modules\<Module>\Models`, extend `App\Base\Model` (sole exception: `User\Models\AccessToken` extends Sanctum `PersonalAccessToken`). Canonical: `modules/Trade/Models/Purchase.php` (+ line model `PurchaseProduct.php`), `modules/Product/Models/Brand.php`, `modules/Product/Models/Product.php` (trait + audit params), `modules/Product/Models/Category.php` (nested set), `modules/User/Models/User.php` (builder + service).

## Base model already provides (`app/Base/Model.php`)
- `__construct()` merges (cached per class) `$guarded` += id/created_at/updated_at/deleted_at, `$hidden` += deleted_at/sort_index/pivot/seo_meta, `$casts` += created_at/updated_at/deleted_at => `string`, `$appends` += `is_deleted` on soft-deletable models. So declare only the extra entries as plain properties.
- No `$fillable`: every real column is mass-assignable (column list from `App\Database\PostgresSchemaBuilder::getColumnListing()`, cached under tag `db_schema`); unknown keys are silently dropped by `fill()`.
- `newQuery()` applies `$autoFilters` (skipped when `!user()`, `$shouldSkipAutoFilters` or `Model::queryWithoutAutoFilters()`); `setKeysForSaveQuery()` adds the original `company_id` to UPDATE/DELETE (partition pruning); `newBaseQueryBuilder()` returns `App\Base\QueryBuilder`; `attributesToArray()` re-reads `<field>_thumbs` of external image casts.
- `ModelSafelyTrait`: `safelySave($attributes)` (fill + `touch()`, or `save()` when `$timestamps = false`), `safelyDelete()`, `safelyRestore()`, `safelyDBProcess($cb)` (reuses an open transaction, also opens `$additionalConnections`; no model sets it yet). `ModelRelationsTrait`: `fillRelations(oneToOne:, oneToMany:, manyToMany:)` (called in FormRequests), written on `created`/`updated`; oneToMany rows get `sort_index`, missing ids are deleted.
- `getService(): ModelService`, `observerParams(): array` (shape in its PHPDoc), `public string $resourceClass = Resource::class`, flags `$shouldHandleDailyLimit`, `$shouldSkipAutoFilters`.

## Rules
- Laravel config stays in properties: `$connection`, `$table`, `$primaryKey`, `$keyType`, `$incrementing`, `$timestamps`, `$hidden`, `$casts`, plus `CREATED_AT`/`UPDATED_AT` consts. No PHP attributes (`#[Table]`, `#[Hidden]`, `#[Guarded]`...) and no `casts()` method — tried and reverted; the base constructor merges only the properties, an attribute would be ignored.
- Always set `protected $table`: `<module>_<entity>` singular snake_case; module's main entity = bare module name (`product`, `stock`, `company`, `user`). Class has no module prefix (`Stock\Models\Product` → `stock_product`, `Trade\Models\SaleProduct` → `trade_sale_product`); only exception `History\Models\StoreHttpLog` → `history_store`. Alias same-named imports `<Module><Name>` (`Product as StockProduct`, `Product as ProductProduct`).
- PHPDoc: every column as `@property` in migration order (`?int` nullable, `float` decimals, `array` JSON/localized, `string` timestamps, `bool` flags, `?string|array` images), blank ` *`, relations (`Collection<X>`, `?X` nullable, `?static $parent`, `Collection<static> $children`), then `@property class-string<XResource> $resourceClass` / `@method static XBuilder query()` when overridden.
- Member order: traits (one `use` per line) → consts (`UPDATED_AT`/`CREATED_AT`, blank line, domain consts `AuthCode::MAX_ATTEMPTS`) → `$connection`/`$table`/`$primaryKey`/`$keyType` → `$incrementing`/`$timestamps` (line tables put `$timestamps = false` above `$table`: `PurchaseProduct`, `SaleProduct`, `Stock\Models\Product`) → `$hidden` → `$casts` → `$resourceClass` → `$autoFilters` → custom public props (`Sale::$saleProducts`, `User::$holdingCompanyIds`) → `observerParams()` → accessors → relations → `getService()` → `newEloquentBuilder()` → `booted()` last.
- Timestamps: append-only `public const UPDATED_AT = null;` (`Batch`, `RecalculationProduct`, `RecordAction`, `Notification`, `Token`, `AuthCode`, `StoreHttpLog`); update-only `CREATED_AT = null` (`Price`); none → `public $timestamps = false;` (line tables, `Profile`, `Credential`, `Meta`, `Setting`, `DailyLimit`).
- 1:1 extension tables: `$primaryKey = '<owner>_id'`, `$incrementing = false`, owner key in `$hidden` (`Profile`, `Credential`). String keys add `$keyType = 'string'` (`Setting`, `AuthCode`).
- Soft delete via `use ModelSoftDeleteTrait` for user-managed entities (`Brand`, `Category`, `Product`, `Specification`, `Stock`, `User`, `Company`); documents, lines, logs, `Option`, `Role`, `Subscription` are hard-deleted. Its `restore()` saves without events (only `restoring`/`restored` fire).
- Non-default DB: `protected $connection = 'history';` (`StoreHttpLog`).
- Company-owned rows (line tables too) keep their own `company_id`, attach `CompanyIdObserver`, declare `protected array $autoFilters = ['holding_companies' => ['company_id']];` (`Price` uses `current_company`). Keys: `current_company`, `nested_companies`, `holding_companies`, `administered_companies`; values = own columns (prefixed with the table, OR-ed). `User`, `Company`, `Role`, `Subscription`, `Notification` have no auto filters — scoped by HTTP Filters.
- `$casts`: `'float'` decimals, `'array'` JSON and localized columns (`name`, `description`), `'encrypted'` / `'encrypted:array'` for secrets (`Token::token`, `Credential::store`). Booleans, dates, enums are not cast.
- Image casts (`app/Casts`) must match the FormRequest upload `source`: `'external'` (`$imageUploads`) → `ExternalStorage\AsImage|AsImages::class . ':scaleDown_100|500'` (`<action>_<sizes>`) plus `'<field>_thumbs' => 'array'`; thumbs are fetched from Storage on first read and saved via `updateQuietly()` (`Brand::image`, `Product::image_list`). `'local'` (`$fileUploads`) → `LocalStorage\AsImage::class . ':scaleDown_25|scaleDown_100|scaleDown_500'`, sizes built by `ImageHelper::populateSizes()`, no thumbs column (`Company::logo`, `Language::image`). `AsFile`/`AsFiles` (both dirs) have zero usages.
- `observerParams()` returns only keys read by attached observers through `ModelService::checkAndGetObserverParams()` (throws if missing): `daily_limit` int (`DailyLimitCreateObserver`), `sluggable` `[source, destination]` (`SluggableObserver`), `audit_record_action` `[excluded, relations: [belongsTo: fk => 'relation.field', oneToMany: relation => [fields], manyToMany: relation => 'relation.*.field']]` (`RecordActionModelTrait`; `Product`, `Specification`).
- Relations: public, typed return, snake_case for multi-word (`stock_products`, `responsible_users`), FK always explicit. `belongsTo` a soft-deletable target always ends with `->withTrashed()` (`Purchase::stock()` yes, `Purchase::batch()` no; only `Credential::company()` omits it); `hasMany`/`belongsToMany` to soft-deletable targets usually too (`Brand::products()`, `Company::children()`). Order: `parent`/`children` → `creator` → `company` → others.
- Standard pair: `creator()` → `User` via `creator_id`, `company()` → `Company` via `company_id`, both `->withTrashed()`.
- `belongsToMany` names the `_ref` pivot and both keys; the partitioned pivot adds `->wherePivotIn('product_company_id', user()->getService()->holdingCompanyIds())` (`Product::options()`, `Option::products()`) — such relations need `user()` set.
- Lazy loading is prevented in local/testing (`Model::preventLazyLoading()` in `app/Providers/AppServiceProvider.php`): a relation read on models fetched as a collection throws `LazyLoadingViolationException`. Eager-load with `with()` / `load()` / `loadMissing()` (or Search `with[]`); lazy reads on a single model (observers, services) are fine.
- Observers only in `protected static function booted(): void` via `self::observe([...])` (see observers-rules for order).
- Accessors use `get<Name>Attribute()` (`Category::getLocalizedNameAttribute()`, `SeoMetaModelTrait::getSeoMetaAttribute()`).
- Domain logic: `getService(): XService { return new XService($this); }`, `XService extends App\Base\ModelService` in `modules/<Module>/Services/` (`Purchase`, `Sale`, `Stock`, `RecalculationProduct`, `User`, `AuthCode`).
- Variant without audit/limits = subclass in `Models/<Model>/` overriding `booted()` and emptying `bootRecordActionModelTrait()` (`Product\Models\Product\ProductImport`).

## Builders (`modules/<Module>/Builders/<Model>Builder.php`)
- Only for reusable chainable queries: extends `App\Base\EloquentBuilder`, `@extends EloquentBuilder<Model>`, methods return `static` (`UserBuilder::whereFullNameLike()`, the only one). Wire with `newEloquentBuilder($query, $builderClass = null): XBuilder` returning `parent::newEloquentBuilder($query, XBuilder::class)` + `@method static XBuilder query()`.
- Already available: `whereLower()`, `getQueryWithRelations()` (manual eager load for exports/jobs), `cloneForSummary()`; `filterByUser()`, `filterByCreator()`, `filterByCurrentCompany()`, `filterByNestedCompanies()`, `filterByHoldingCompanies()`, `filterByAdministeredCompanies()` (`EloquentBuilderFilterByUserTrait`; paths may be `relation->column`), `filterByOwnRecords($route, $fields)` (`EloquentBuilderFilterByPermissionsTrait`); `App\Base\QueryBuilder`: `whereIlike()`/`orWhereIlike()` (escaped `%value%`), `whereInThroughAny()`.

## Trees
- Nested set (`app/NestedSet`, only `Product\Models\Category`): `use NestedSetModelTrait` (first trait, wraps kalnoy `NodeTrait`), `newEloquentBuilder()` returning `NestedSetEloquentBuilder`, `@method static EloquentBuilder<static>|NestedSetEloquentBuilder<static> query()`. Needs `parent_id`/`lft`/`rgt`, `company_id` + `company()`, `getLocalizedNameAttribute()` (appended on retrieve). One root per holding, created in `CompanyObserver::created()` via `makeRoot()->safelySave()`; new nodes auto-append to it under `DatabaseTransactionHelper::lock("<table>:tree:<companyId>")`; global `lft` order scope; restore blocked under a trashed ancestor. HTTP: `NestedSetController` (`tree`, `move`), `NestedSetMoveRequest`, resource extends `NestedSetResource`.
- Self-references without nested set: `parent(): BelongsTo` (`->withTrashed()`) + `children(): HasMany` on `parent_id` (`Company`, `Product`).

## Model traits
- Shared ones in `app/Base/Model/`; feature ones in `modules/<Module>/Traits/<Feature>ModelTrait.php` with `@mixin Model`: `SeoMetaModelTrait` (`seo_meta()` MorphOne + `seo_meta`/`seo_meta_as_array` accessors), `RecordActionModelTrait` (`$recordActionOldData`, `audit_record_actions()` MorphMany, `getRecordActionCreateService()`, `bootRecordActionModelTrait()` attaches `RecordActionCreateObserver`). Order: props → accessors → relations → service getters → `boot<TraitName>()` last.

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
- Use Laravel 13 model attributes (`#[Table]`, `#[Hidden]`, `#[Guarded]`, `#[ObservedBy]`...) or a `casts()` method; mixing styles was reverted.
- Declare `$fillable`/`$guarded`/`$dates`/`$appends`/`protected $with`, re-hide base hidden fields or cast timestamps — zero usages.
- Use `SoftDeletes` directly, `HasFactory`, `Attribute::make()`, `scopeX()` local scopes, enum/bool/datetime casts — zero usages.
- Lazy-load relations inside loops over collections; eager-load them.
- Override `boot()` or register model observers outside `booted()`.
- Wrap saves in `DB::transaction()` or use `lockForUpdate()`; use `safely*()` and `DatabaseTransactionHelper::lock()`/`lockModel()`.
- Write raw `'ilike', "%$v%"` in builders; use `whereIlike()` (escapes wildcards).
- Add `observerParams()` keys without the matching observer/trait, or vice versa.
