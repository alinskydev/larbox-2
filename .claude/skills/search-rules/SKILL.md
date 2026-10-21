---
name: search-rules
description: Mandatory standards for list-query Search classes in modules/*/Search (extending App\Search\Search), the app/Search engine (SearchFilter, SearchSort, SearchShow, Filter/Types, Enums) and how ResourceController wires them. Use when creating or editing a Search class, adding filters/sorts/relations/show options, or wiring a search into a controller.
---

# Search Rules

Location: `modules/<Module>/Search/<Entity>Search.php`, namespace `Modules\<Module>\Search`, base `App\Search\Search`. Engine: `App\Search\SearchFilter`, `SearchSort`, `SearchShow`, filter types `App\Search\Filter\Types\*` (base `SearchFilterType`), enums `App\Search\Enums\SearchFilterTypeEnum`, `SearchFilterConditionEnum`, `SearchSortTypeEnum`.
Canonical: `modules/Trade/Search/SaleSearch.php` (plain), `modules/Product/Search/ProductSearch.php` (groups, relation filter, overrides), `modules/User/Search/UserSearch.php` (custom builder).

## Request contract (`App\Controllers\ResourceController` middleware)
- `setQuery($this->model->query())` → `with(?with[])` → `filter(?filter[...])` → `sort(?sort[])` → `show(?show[])` → `setPageSize(?page_size)`, then the HTTP `Filter::process($this->search->query)`.
- `index()` paginates `$this->search->query` by `$this->search->pageSize` (`?page=`); `show()`, `restore()`, `deleteMultiple()`, `restoreMultiple()` also use `$this->search->query`.
- Wire it in the controller: `parent::__construct(model: new <Entity>(), search: new <Entity>Search(), ...)`. Default is a bare `new Search()` (nothing whitelisted).

## Properties
- One Search per model, named after the model class (`modules/Stock/Search/ProductSearch.php` for `Modules\Stock\Models\Product`). Order: `public const` (if any), `$defaultSort` / `$pageSize` (only to override), `$relations`, `$filters`, `$sorts`.
- `$relations`: exact whitelist for `?with[]=` (`array_intersect`); nested paths must be listed one by one (`'category'`, `'category.ancestors'`, `'options'`, `'options.specification'`). Others are silently dropped. Tenant models start with `'creator.profile'`, `'company'`, then FK relations (`'stock'`, `'batch'`, `'product'`).
- `$filters`: `param => SearchFilterTypeEnum` for `?filter[param]=`; unknown params ignored. Order: `id`, `creator_id`, `company_id`, FK ids, own fields, `created_at => BETWEEN_DATE` last; relation filters and OR-groups after a blank line.
- `$sorts`: `field => SearchSortTypeEnum` for `?sort[]=field` / `-field` (desc). `RAW` for columns, `LOCALIZED` for JSON-localized columns (`"$field->$locale"`). Always `'id' => RAW` (exception `CategorySearch`: `$defaultSort = ['lft']`, `'lft' => RAW`). Default `$defaultSort = ['-id']`; `SearchSort` always appends `orderBy(<qualified pk>)` as tiebreaker.
- `$pageSize` (default 20) is the max; `?page_size` can only lower it. Override for heavy lists (`StoreHttpLogSearch`, `NotificationSearch`: 10).
- Every `$relations` and `$sorts` entry must work: `App\Testing\Feature\Traits\SearchFeatureTestTrait` (`test_available_relations`, `test_available_sorts`) requests all of them in each `IndexTest` (`$searchClass`).

## Filter types (`SearchFilterTypeEnum`)
- ids / FKs / statuses / enums / flags → `EQUAL_RAW`; multi-select → `IN` (`UserSearch` `id`, `roles.id`).
- strings → `LIKE` (`whereIlike()`: escaped, `%v%`); exact case-insensitive → `EQUAL_STRING` (`LanguageSearch` `code`); localized JSON → `LOCALIZED_LIKE` (`field->>'<locale>'`).
- numbers / prices / quantities → `BETWEEN_NUMBER` (`[from, to]`, each optional); dates → `BETWEEN_DATE` (`[from, to]`, whole days).
- Available, currently unused: `BETWEEN_DATETIME`, `DATE`, `DATETIME`, `JSON_CONTAINS_ANY`, `JSON_CONTAINS_ALL`, `NOT_EQUAL_RAW`, `SIMILARITY` (pg_trgm `%`).
- Columns are auto-qualified with the query table (`SearchFilterType::__construct`), safe with joins.
- Relation filter: dotted key; last segment = column, prefix = `whereHas` path (`'price.price' => BETWEEN_NUMBER`, `'responsible_companies.id' => IN`).
- OR-group (one param over several columns, wrapped in `where(fn)`): `'search' => ['condition' => SearchFilterConditionEnum::OR_WHERE, 'filters' => ['full_text' => LIKE, 'barcode' => EQUAL_RAW]]` (`ProductSearch` `search` / `category_id`, `LanguageSearch` `search`, `UserSearch` `username_or_phone`).

## Overrides
- Override only `with()`, `filter()`, `show()`: `parent::<method>($params);` first, then modify `$this->query`, then `return $this;`.
- `filter()`: params no enum fits — `if ($value = Arr::get($params, 'parent_id'))` + `$this->query->when(...)` (`ProductSearch` `parent_id = 'none'`), `isset($params['is_root'])` + `(bool)` (`CompanySearch`), alias → class via `match` (`RecordActionSearch` `auditable_type`), builder scopes (`UserSearch` → `whereFullNameLike()`), raw JSON text via `whereIlike(DB::raw(...), $value)` (`StoreHttpLogSearch`). Unconditional constraints too (`CategorySearch`: `whereNotNull('parent_id')`; `StoreHttpLogSearch`: mandatory interval from `Modules\History\Helpers\SearchHelper::createdAtInterval()`).
- `show()`: opt-in computed columns via `?show[]=<key>`: `if (in_array('<key>', $params))` → `withCount(['<rel>' => function ($query) { $query->withoutTrashed(); }])` (`BrandSearch`), `withAggregate('<rel> as <alias>', DB::raw('coalesce(sum(<table>.<col>), 0)'))` (`StockSearch`, `ProductSearch`), or `addSelect(['<alias>' => <subquery>])` (`CategorySearch`). Built-in `with_deleted` / `only_deleted` are handled by the parent (soft-delete models only).
- `with()`: relations the resource always needs (`CategorySearch`: `ancestors`).
- Custom builder: class PHPDoc `@property <Model>Builder $query` (`UserSearch`).
- `public const` read by other layers is allowed (`StoreHttpLogSearch::REQUEST_PATHS`, used by `StoreHttpLogResource`).

## Controller extras
- Totals: override `index()`: `$response = parent::index(); $data = $response->getData(true); $data['summary'] = $this->search->query->cloneForSummary()->...->getQuery()->first(); return $response->setData($data);` (`http/Company/Stock/Controllers/StockController.php`, `http/Company/Product/Controllers/ProductController.php`).
- Exports reuse the filtered query: `ExcelExportHelper::process(jobClass: ..., query: $this->search->query)` (`ProductController::index`).
- Company/permission scoping → `http/<Area>/<Module>/Filters/*Filter` (`StockFilter`: `filterByNestedCompanies('stock.company_id')`) or model `$autoFilters`, never Search.

## Template
```php
<?php

namespace Modules\<Module>\Search;

use App\Search\Enums\SearchFilterTypeEnum;
use App\Search\Enums\SearchSortTypeEnum;
use App\Search\Search;
use Illuminate\Support\Facades\DB;

class <Entity>Search extends Search
{
    public array $relations = [
        'creator.profile',
        'company',
        'stock',
    ];

    public array $filters = [
        'id' => SearchFilterTypeEnum::EQUAL_RAW,
        'creator_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'company_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'stock_id' => SearchFilterTypeEnum::EQUAL_RAW,
        'name' => SearchFilterTypeEnum::LIKE,
        'amount' => SearchFilterTypeEnum::BETWEEN_NUMBER,
        'created_at' => SearchFilterTypeEnum::BETWEEN_DATE,
    ];

    public array $sorts = [
        'id' => SearchSortTypeEnum::RAW,
    ];

    public function show(array $params): static
    {
        parent::show($params);

        if (in_array('<rel>_quantity', $params)) {
            $this->query->withAggregate(
                '<rel> as <rel>_quantity',
                DB::raw('coalesce(sum(<rel_table>.quantity), 0)'),
            );
        }

        return $this;
    }
}
```

## Don't
- Don't put company/permission scoping in Search.
- Don't whitelist relations the endpoint must not expose; a parent entry doesn't allow nested paths.
- Don't add filter type classes or enum cases for one-off needs — override `filter()`.
- Don't escape `%` / `_` manually; `LIKE` / `whereIlike()` do it (`StringHelper::escapeForLikeCondition()`).
- Don't forget `parent::...` and `return $this;` in overrides.
- Don't paginate, `get()` or serialize inside Search.
- Don't use stale names: it's `$sorts` (not `$sortings`), `page_size` (not `page-size`), `with_deleted` / `only_deleted` (not `with-deleted`).
