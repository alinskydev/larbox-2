---
name: observers-rules
description: Mandatory standards for model observers in app/Observers and modules/*/Observers (CreatorId, CompanyId, IP, Sluggable, DailyLimit, Subscription limit, audit record action, model observers) and their registration in model booted() / observerParams(). Use when creating or editing an observer, a model's observer list or observerParams().
---

# Observers Rules

Model observers: `modules/<Module>/Observers/<Model>Observer.php`; feature observers: `modules/<Module>/Observers/<Feature>/<Feature><Action>Observer.php`; generic field fillers: `app/Observers/`. Plain classes (no base class, no interface).
Canonical: `modules/Product/Observers/ProductObserver.php`, `modules/Trade/Observers/PurchaseObserver.php`, `modules/User/Observers/RoleObserver.php`, `modules/Company/Observers/Subscription/SubscriptionCheckLimitObserver.php`.

## Registration
- Only in the model: `protected static function booted(): void { self::observe([...]); }`. Fixed order, include only what applies: `CreatorIdObserver`, `CompanyIdObserver`, `IPObserver`, `SluggableObserver`, `DailyLimitCreateObserver`, `SubscriptionCheckLimitObserver`, `<Model>Observer` last (`modules/Product/Models/Product.php`, `modules/History/Models/StoreHttpLog.php`).
- Params: override `public function observerParams(): array` (shape documented on `App\Base\Model::observerParams()`): `daily_limit` (int), `sluggable` (`['source' => 'name', 'destination' => 'slug']`), `audit_record_action` (`excluded`, `relations.belongsTo|oneToMany|manyToMany`). Observers read them with `$model->getService()->checkAndGetObserverParams('<key>')`, which throws `\Exception` when the key is missing.
- Exception: `Modules\Audit\Traits\RecordActionModelTrait` self-registers `RecordActionCreateObserver` (listener on `eloquent.booted: <class>`). Add the trait + `audit_record_action` param (`Product`, `Specification`); never list the observer in `booted()`. A subclass disables it with `protected static function bootRecordActionModelTrait(): void {}` (`modules/Product/Models/Product/ProductImport.php`).

## Shared observers
- `CreatorIdObserver` / `CompanyIdObserver` / `IPObserver` (`creating`): `creator_id ??= user()?->id`, `company_id ??= user()?->company_id`, `ip ??= request()?->ip()`. Attach to every model with that column; explicit values win (`CompanyObserver::created` builds the root `Category` with explicit ids).
- `SluggableObserver` (`saving`): needs `sluggable`; regenerates only on create or when source is dirty; localized (array) source uses the current locale; on collision (trashed rows included) appends `-` + `StringHelper::uuid()`.
- `DailyLimitCreateObserver` (`creating`, `modules/System/Observers/DailyLimit/`): needs `daily_limit`; counts only company director/employee users per `company_id` via `DailyLimitHelper::decrease()`; skipped when `$model->shouldHandleDailyLimit === false` (base `App\Base\Model`). Attached to user-created entities (Brand, Category, Option, Product, Specification, Stock, Purchase, Sale, RecalculationProduct, Role, User, Company, Subscription).
- `SubscriptionCheckLimitObserver` (`creating` + `restoring`, `modules/Company/Observers/Subscription/`): needs a `company` relation and the class mapped in `SubscriptionRecordLimitEnum::valueByClassName()`; takes `DatabaseTransactionHelper::lock("company_subscription_limit:$limitKey:$companyId")` and throws `SubscriptionException`.
- Child rows written by services (`Batch`, `Price`, `Stock\Product`, `PurchaseProduct`, `SaleProduct`, `SaleTransaction`) get only `CompanyIdObserver`.
- Bulk callers invoke limit observers directly on a template model: `(new SubscriptionCheckLimitObserver())->creating(model: new Product(['company_id' => ...]), additionalCount: $n)`, `(new DailyLimitCreateObserver())->creating(model: ..., decreaseValue: $n)` (`http/Company/Product/Requests/Product/ProductExcelImportProcessRequest.php`).

## Model observer rules
- Type-hint the concrete model, parameter named `$model`, return `void` (`ProductObserver::saving(Product $model): void`). Shared observers type-hint `App\Base\Model`; trait-dependent ones add `@param Model&RecordActionModelTrait $model`.
- Event choice:
  - `creating` — defaults and pre-insert guards (`PurchaseObserver::creating`: `status ??= PENDING`; `AuthCodeObserver::creating`; `CompanyObserver::creating` partition limit).
  - `saving` / `updating` — normalize/derive attributes (`ProductObserver::saving`, `RoleObserver::saving`, `CredentialObserver::saving`, `UserObserver::saving` hashes password) and state guards (`PurchaseObserver::updating` → `$model->getService()->lockAndEnsurePending()`).
  - `created` / `updated` / `saved` — side effects needing the id: child rows, domain processes, external sync, mail, cache flush (`UserObserver::created`, `SaleObserver::saved`, `ProductObserver::created` → `StoreProductService`, `AuthCodeObserver::created` → `MailHelper::send()`, `LanguageObserver::saved`).
  - `deleting` — integrity guards (`RoleObserver`, `OptionObserver`, `SubscriptionObserver`, `UserObserver`); `deleted` / `restored` — cascades (`CompanyObserver`).
- React to real changes only: `isDirty()` / `getOriginal()` in `saving`/`updating`/`saved` (`SaleObserver::saved`, `LanguageObserver::saving`); `wasChanged()` in `updated` / `saved` after an update (`UserObserver::updated`, `ProductObserver::saved`).
- Business errors: throw the module exception (see exceptions-rules): `throw new RoleException(RoleException::ATTACHED_TO_USERS, ['role' => $model->name, 'users' => $userUsernames->join(', ')]);`. List at most 3 blockers: `->orderBy('id', 'desc')->limit(3)->get()->pluck('name')`, then `if (!$names->isEmpty())`.
- Write back without re-firing events: `$model->updateQuietly([...])` or bulk `Model::query()->...->getQuery()->update([...])` (`ProductObserver::saved`, `LanguageObserver::saving`).
- Create a child row once: `if (!$model->profile()->exists()) { $model->profile()->create(); }` (`UserObserver`, `CompanyObserver`).
- Keep observers thin: multi-step domain logic goes to the model service (`PurchaseObserver`, `SaleObserver`, `RecalculationProductObserver` call `$model->getService()->...`); limits to `DailyLimitHelper` (`StoreHttpLogObserver`).
- Long handlers: private methods prefixed with the event (`CredentialObserver::savingDefaults()`, `savingTelegramChats()`); array defaults via `ArrayHelper::fillByTemplate()` (`SubscriptionObserver`, `CredentialObserver`).
- Observers run inside the caller's `safely*()` transaction; `safelySave()` on other models is fine there (`CompanyObserver::created`); locks via `DatabaseTransactionHelper` (see services-rules).
- Non-column input for an observer: public typed model properties filled by the request (`Sale::$saleProducts` / `$salePayments`, set in `SaleRequest::passedValidation()`, consumed in `SaleObserver::saved`).
- Returning `false` from `updating` cancels the update — only `AccessTokenObserver::updating` (no return type) does this.

## Template
```php
<?php

namespace Modules\<Module>\Observers;

use Modules\<Module>\Exceptions\<Model>Exception;
use Modules\<Module>\Models\<Child>;
use Modules\<Module>\Models\<Model>;

class <Model>Observer
{
    public function saving(<Model> $model): void
    {
        $model->full_text ??= $model->name;
    }

    public function created(<Model> $model): void
    {
        if (!$model->detail()->exists()) {
            $model->detail()->create();
        }
    }

    public function deleting(<Model> $model): void
    {
        $childNames = <Child>::query()
            ->where('<model>_id', $model->id)
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get()
            ->pluck('name');

        if (!$childNames->isEmpty()) {
            throw new <Model>Exception(<Model>Exception::ATTACHED_TO_<CHILDREN>, [
                'name' => $model->name,
                '<children>' => $childNames->join(', '),
            ]);
        }
    }
}
```

Model side:
```php
public function observerParams(): array
{
    return [
        'daily_limit' => 500,
    ];
}

protected static function booted(): void
{
    self::observe([
        CreatorIdObserver::class,
        CompanyIdObserver::class,
        DailyLimitCreateObserver::class,
        <Model>Observer::class,
    ]);
}
```

## Don't
- Don't register observers in providers, `#[ObservedBy]`, `boot()` or `static::creating(fn ...)` closures in models (zero usages; closures exist only in base traits `ModelRelationsTrait`, `NestedSetModelTrait`).
- Don't name the method `observersParams()` — it is `observerParams()`.
- Don't set `creator_id` / `company_id` / `ip` / `slug` in controllers or requests when the shared observer is attached.
- Don't call `save()` / `safelySave()` on the same model inside its `saving`/`saved` handlers; assign attributes or use `updateQuietly()`.
- Don't open transactions in observers (`DB::transaction()`, `safelyDBProcess()`); don't use `lockForUpdate()` (zero usages).
- Don't use `abort(400, ...)` or raw `\Exception` for business rules.
- Don't attach `SluggableObserver` / `DailyLimitCreateObserver` / `SubscriptionCheckLimitObserver` without the matching `observerParams()` key / enum mapping.
- Don't create a shared observer for logic used by a single model.
