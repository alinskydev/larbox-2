---
name: http-filters-rules
description: Mandatory standards for access-scoping Filter classes in http/<Area>/<Module>/Filters (extending App\Base\Filter) and the builder scopes they use from app/Base/EloquentBuilder (EloquentBuilderFilterByUserTrait, EloquentBuilderFilterByPermissionsTrait). Use when creating or editing a Filter or when a resource controller needs per-user/company record scoping.
---

# HTTP Filters Rules

Location: `http/<Area>/<Module>/Filters/<Entity>Filter.php`, namespace `Http\<Area>\<Module>\Filters`, extends `App\Base\Filter` (single abstract `process(Builder $query): Builder`).
Canonical: `http/Company/Trade/Filters/SaleFilter.php`, `http/Company/Product/Filters/BrandFilter.php`, `http/Admin/Company/Filters/CompanyFilter.php`.

## How it is applied (`App\Controllers\ResourceController`)
- `process()` runs on `$this->search->query` (index, show, restore, custom actions using it) and inside `Route::bind('model')` for every `{model}` route (update, delete, custom `{model}` actions, `ResourceFormRequest::$model`). Foreign records are unreachable by id.
- Not applied to `$this->model->query()` or `X::query()` in custom code (`NestedSetController::tree/move`) — only model `$autoFilters` apply there.
- The list call ignores the return value → `process()` must modify the given builder in place and return it; never clone or build a new query.
- It narrows model `$autoFilters` (`App\Base\Model::newQuery()`): Product/Stock/Trade/Audit models auto-scope reads to `holding_companies` (`modules/Trade/Models/Sale.php`). `Company` and `User` have no auto filters — their Filters are the only scoping.
- Client filtering/sorting (`?filter[...]`, `?sort[...]`) belongs to Search classes (`Modules\<Module>\Search`), not Filters.

## Rules
- One Filter per resource controller, named after the controller entity (`EmployeeFilter` for `EmployeeController` over `User`), passed as `filter: new <Entity>Filter()`.
- Only `public function process(Builder $query): Builder`, typed with `Illuminate\Database\Eloquent\Builder`; return the chain.
- Scope only with `App\Base\EloquentBuilder` methods (`app/Base/EloquentBuilder/EloquentBuilderFilterByUserTrait.php`, `EloquentBuilderFilterByPermissionsTrait.php`):
  - `filterByNestedCompanies(path = 'company_id')` — director/employee: own company + children; admins unrestricted. Default for the Company area (`Company/Trade/Filters/*`, `Company/Audit/Filters/RecordActionFilter.php`).
  - `filterByCurrentCompany(path)` — own company only (`Company/User/Filters/RoleFilter.php`).
  - `filterByHoldingCompanies(path)` — parent holding + its children (used by `$autoFilters`, `NestedSetEloquentBuilder`; no Filter needs it).
  - `filterByAdministeredCompanies(path)` — Admin area: admins limited to responsible companies + children; superadmin and company users unrestricted (`Admin/Company/Filters/CompanyFilter.php`).
  - `filterByUser(field)` — personal records (`Common/User/Filters/NotificationFilter.php` → `'recipient_id'`); `filterByCreator(fields = 'creator_id')`.
  - `filterByOwnRecords(RolePermissionHelper::ROUTE_<AREA>_<MODULE>_<ENTITY>, fields = 'creator_id')` — creator-only unless the role has `<route>.permission-all-records` (`BrandFilter`); requires the pseudo-route and const (see http-routes-rules).
- Path: `'id'` when filtering the companies table itself (`Company/Company/Filters/CompanyFilter.php`, Admin `CompanyFilter`); table-qualified when the Search joins (`Company/Stock/Filters/StockFilter.php` → `'stock.company_id'`); `'relation->company_id'` filters through `whereHas`.
- Fixed type constraints belong here: `->where('type', UserTypeEnum::COMPANY_EMPLOYEE->value)` (`EmployeeFilter`, Company `RoleFilter`), `->whereIn('type', [...])` (`Admin/User/Filters/RoleFilter.php`).
- Shared catalogs (readable across the holding, writable only by own companies) scope non-read actions only: `->when(!in_array(request()->route()->getActionMethod(), ['index', 'show']), function ($q) { $q->filterByNestedCompanies(); })` (`Company/Product/Filters/ProductFilter.php`, `OptionFilter`, `SpecificationFilter`); list extra read actions (`'tree'` in `CategoryFilter`). Combine with other scopes before `->when()` (`BrandFilter`).
- Role/action-dependent eager loading is allowed: `$query->when(user()->getService()->isSuperadmin() && ..., function ($q) { $q->with(['credential']); });` then `return $query->filterBy...()` (Admin `CompanyFilter`).
- Every Company-area resource controller has a Filter; omit it only for global or superadmin-only Admin data (`SubscriptionController`, `LanguageController`, `UserController`, `StoreHttpLogController`).
- Closures inside `when()` are multiline `function ($q) { ... }`, matching existing Filters.

## Template
```php
<?php

namespace Http\<Area>\<Module>\Filters;

use App\Base\Filter;
use Illuminate\Database\Eloquent\Builder;

class <Entity>Filter extends Filter
{
    public function process(Builder $query): Builder
    {
        return $query->filterByNestedCompanies();
    }
}
```

Write-only scoping (shared catalog):
```php
    public function process(Builder $query): Builder
    {
        return $query->when(
            !in_array(request()->route()->getActionMethod(), ['index', 'show']),
            function ($q) {
                $q->filterByNestedCompanies();
            },
        );
    }
```

## Don't
- Don't read `request()->query('filter')`, sort or paginate in a Filter — that is Search.
- Don't write raw `where('company_id', user()->company_id)` / `whereIn(..., nestedCompanyIds())` — `filterBy*()` handle every user type.
- Don't add constructors, properties or extra methods; don't share one Filter across controllers or areas.
- Don't scope in the controller instead of a Filter — the `{model}` binding would leak foreign records.
- Don't call `queryWithoutAutoFilters()` (zero usages in the codebase).
