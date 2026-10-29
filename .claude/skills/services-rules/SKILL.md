---
name: services-rules
description: Mandatory standards for model services in modules/*/Services (extending App\Base\ModelService, obtained via Model::getService()), incl. domain process services (Trade, Stock) and transactions/locks via App\Helpers\Database\DatabaseTransactionHelper. Use when creating or editing a service, adding domain logic, or deciding whether logic belongs in a service, observer, helper or controller.
---

# Services Rules

Location: `modules/<Module>/Services/<Entity>Service.php` (secondary: `Services/<Feature>/<Feature><Action>Service.php`), namespace `Modules\<Module>\Services[\<Feature>]`, base `App\Base\ModelService` (`__construct(protected Model $model)`, `checkAndGetObserverParams(string $key)`). `App\Base\Model::getService()` returns a plain `ModelService`, so every model has one for observer params.
Canonical: `modules/Trade/Services/SaleService.php`, `modules/Trade/Services/PurchaseService.php`, `modules/Stock/Services/StockService.php`, `modules/User/Services/UserService.php`. All services: these + `Trade/Services/RecalculationProductService.php`, `User/Services/AuthCodeService.php`, `Audit/Services/RecordAction/RecordActionCreateService.php`. External API clients (`integrations/*/Services`, `App\Integration\IntegrationService`) are not covered.

## When a service
- Logic bound to one model instance reused across layers: `UserService::isSuperadmin()`, `holdingCompanyIds()` (used by builders, middleware, requests, relations), `createNewAccessToken()`, `AuthCodeService::create()` / `verifyCode()`.
- Domain processes over a model and its child rows: `SaleService::addProducts()` / `addPayments()` / `return()`, `PurchaseService::addProducts()` / `removeProducts()` / `createBatch()` / `updateProductPrices()` / `lockAndEnsurePending()`, `StockService::takeProducts()` (FIFO by `batch_id`), `RecalculationProductService::updateProductPrice()`.
- A feature process on any model: `RecordActionCreateService::process()`, obtained via `getRecordActionCreateService()` in `modules/Audit/Traits/RecordActionModelTrait.php`.
- Not a service: plain CRUD → `ResourceController` + request + `safelySave()`; defaults/normalization on save → observer; stateless or cross-entity utilities → static helper (`DailyLimitHelper`, `NotificationHelper`, `RoleHelper`); query constraints → builder / Search / HTTP Filter.

## Rules
- Extend `ModelService`; never add a constructor or static methods; class PHPDoc `@property <Entity> $model` (only the generic `RecordActionCreateService` omits it). Per-call state of a secondary service: private typed props set in `process()` (`RecordActionCreateService::$params`, `$type`).
- One main service per model, exposed by overriding `getService()` with the narrowed return type, placed after relations and before `newEloquentBuilder()` / `booted()` (`Sale`, `Purchase`, `Stock`, `RecalculationProduct`, `User`, `AuthCode`). Secondary service: `get<Feature><Action>Service()` on the model/trait with one public `process(...)`.
- Always call through the model: `$request->model->getService()->return($products, $transactions)` (`http/Company/Trade/Controllers/SaleController.php`), `$this->model->stock->getService()->takeProducts(...)` (service → service), `$model->getService()->createBatch()` (`PurchaseObserver`), `(new AuthCode())->getService()->create($username)` (`ResetPasswordController`). Never `new <Entity>Service(...)` outside the model.
- Controllers read validated values into variables, then call (`$products = $request->validatedData['products'];`); observers trigger state-change processes (`PurchaseObserver::updating` on `COMPLETED`, `SaleObserver::saved` on `COMPLETED`).
- Array inputs/outputs carry shape PHPDoc: `@param array<array{product_id: int, quantity: float}> $products`, `@return array<array{batch_id: int, ...}>` (`StockService::takeProducts`).
- Business errors: throw the module exception (see exceptions-rules): `throw new SaleException(SaleException::PRICE_NOT_SET, ['name' => $product->name]);`. Validate before writing. `\Exception` only for developer misconfiguration (`ModelService::checkAndGetObserverParams`).
- Float quantities/amounts: global `num_round()`, `num_e/ne/gt/gte/lt/lte()` (`SaleService::addPayments`, `returnProducts`, `StockService::takeProducts`), never `===`/`>` on raw floats.
- Lazy loading throws in local/testing (`Model::preventLazyLoading()` in `AppServiceProvider`): when a fetched collection is iterated and relations are touched or serialized, eager-load them (`Product::query()->with(['category', 'brand', 'options'])->whereIn(...)->get()->keyBy('id')` in `SaleService::addProducts` before `toArray()` snapshots). Key lookup collections with `keyBy('id')` / `groupBy('product_id')` instead of querying in loops.
- Per-request cache on the model instance: declare `public array $nestedCompanyIds;` on the model, then `if (!isset($this->model->nestedCompanyIds)) { ... }` (`UserService` + `modules/User/Models/User.php`: `nestedCompanyIds`, `holdingCompanyIds`, `administeredCompanyIds`, `allRoleRoutes`). Process output on the model the same way (`User::$newAccessToken`).
- Non-column process input: public typed model properties with defaults (`Sale::$saleProducts = []`, `$salePayments = []`).
- `user()` only for context fallbacks (`RecordActionCreateService`: `$this->model->company_id ?? user()?->company_id`); never `request()`, responses, resources, `abort()` or HTTP exceptions in services.

## Persistence, transactions, locks
- One-model writes: `safelySave()` / `safelyDelete()` / `safelyRestore()`. Several writes: `$this->model->safelyDBProcess(function () { ... })` — opens a transaction (incl. `additionalConnections`) only if `DB::transactionLevel() === 0`, so nesting is safe.
- Entry methods called from controllers wrap their work in `safelyDBProcess()` (`SaleService::return`, `PurchaseService::addProducts` / `removeProducts`); methods called from observers or other services rely on the caller's transaction (`StockService::takeProducts`, `PurchaseService::createBatch`, `lockAndEnsurePending` from `PurchaseObserver::updating`).
- Concurrency: first statement inside the transaction is `DatabaseTransactionHelper::lockModel($this->model)` (key `<table>:<id>`) or `DatabaseTransactionHelper::lock('<scope>:<id>')`. It is a `Cache::lock` (blocks up to 60 s), re-entrant per process, released on `DB::afterCommit` / `afterRollBack` — so call it only inside a transaction.
- After locking, re-read state from the DB, not from `$this->model`: `Purchase::query()->whereKey($this->model->id)->value('status')` (`PurchaseService::lockAndEnsurePending`).
- Bulk writes bypass model events: build `$rows` in a loop, then `Model::query()->insert($rows)`, `upsert(values: $rows, uniqueBy: [...], update: [...])`, `insertUsing(columns: $columns, query: $query)`; set `company_id` explicitly in every row and `json_encode()` JSON columns (`SaleService::addProducts`, `PurchaseService`, `RecalculationProductService`). When child events are needed, create through the relation: `$this->model->transactions()->createMany($rows)` (`SaleService::createTransactions`).
- Atomic counters: `->increment('quantity', $q)` / `->decrement(...)` (`SaleService::returnProducts`, `StockService::takeProducts`).
- Child-table queries always add `->where('company_id', $this->model->company_id)`, also through relations: `$this->model->products()->where('company_id', $this->model->company_id)` (company-partitioned `stock_product`, `trade_*_product`).
- Read-only row sets skip hydration: `->getQuery()->get()` (`PurchaseService::updateProductPrices`, `UserService::companyIdsWithChildren`); single values via `value()` / `pluck()` / `exists()`.

## Structure
- Public entry methods first, private step methods after (`SaleService::returnProducts()`, `refundTransactions()`, `createTransactions()`); early returns (`if (!$transactions) return;`); named arguments for multi-param calls.
- Domain tests: `modules/<Module>/Tests/<Entity>/<Action>Test.php` extending the folder `_TestCase` (`App\Testing\Domain\DomainTestCase`), e.g. `modules/Trade/Tests/Sale/ReturnTest.php`, see http-tests-rules.

## Template
```php
<?php

namespace Modules\<Module>\Services;

use App\Base\ModelService;
use App\Helpers\Database\DatabaseTransactionHelper;
use Modules\<Module>\Enums\<Entity>StatusEnum;
use Modules\<Module>\Exceptions\<Entity>Exception;
use Modules\<Module>\Models\<Entity>;
use Modules\<Module>\Models\<Entity>Item;
use Modules\Product\Models\Product;

/**
 * @property <Entity> $model
 */
class <Entity>Service extends ModelService
{
    /**
     * @param array<array{product_id: int, quantity: float}> $items
     */
    public function addItems(array $items): void
    {
        $products = Product::query()
            ->with(['category'])
            ->whereIn('id', array_column($items, 'product_id'))
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            $rows[] = [
                'company_id' => $this->model->company_id,
                '<entity>_id' => $this->model->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'product_snapshot' => json_encode($product->toArray()),
            ];
        }

        $this->model->safelyDBProcess(function () use ($rows) {
            $this->lockAndEnsurePending();

            <Entity>Item::query()->upsert(
                values: $rows,
                uniqueBy: ['company_id', '<entity>_id', 'product_id'],
                update: ['quantity', 'product_snapshot'],
            );
        });
    }

    public function lockAndEnsurePending(): void
    {
        DatabaseTransactionHelper::lockModel($this->model);

        $status = <Entity>::query()->whereKey($this->model->id)->value('status');

        if ($status !== <Entity>StatusEnum::PENDING->value) {
            throw new <Entity>Exception(<Entity>Exception::NOT_PENDING, [
                'status' => <Entity>StatusEnum::labelByValue(<Entity>StatusEnum::PENDING->value),
            ]);
        }
    }
}
```

Model side:
```php
public function getService(): <Entity>Service
{
    return new <Entity>Service($this);
}
```

## Don't
- Don't use `lockForUpdate()` / `sharedLock()` (zero usages) — only `DatabaseTransactionHelper::lock()` / `lockModel()`.
- Don't use `DB::transaction()` / `DB::beginTransaction()` (only inside `ModelSafelyTrait`) — only `safelyDBProcess()` / `safely*()`.
- Don't call `lock()` / `lockModel()` outside a transaction.
- Don't trust `$this->model` attributes for state checks after taking a lock; re-query.
- Don't iterate a collection and touch unloaded relations (lazy-loading violation in local/testing); don't query inside loops.
- Don't create services for CRUD, validation or response formatting; a stateless static class is a helper.
- Don't put model services in `app/` or `http/`.
