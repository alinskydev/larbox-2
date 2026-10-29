---
name: dto-rules
description: Mandatory standards for DTO classes in app/Integration/DTOs, http/<Area>/<Module>/DTOs and any future <layer>/DTOs folder, and for choosing a DTO vs. a typed array. Use when creating or editing a DTO, adding a field to one, or passing structured params between layers.
---

# DTO Rules

DTOs live in a `DTOs/` folder next to their consumer (namespace = path): `app/Integration/DTOs/` (`App\Integration\DTOs`), `http/Common/Record/DTOs/` (`Http\Common\Record\DTOs`). No base class, no interfaces, no traits. There are no `modules/*/DTOs` now.
All DTOs: `app/Integration/DTOs/IntegrationServiceConfigDTO.php`, `app/Integration/DTOs/IntegrationRequestOptionsDTO.php`, `http/Common/Record/DTOs/RecordIndexParamsDTO.php`, `http/Common/Record/DTOs/RecordCreateParamsDTO.php`.

## When a DTO is used (only these patterns exist)
- Contract between an abstract base and its implementations: `IntegrationService::getConfig(): IntegrationServiceConfigDTO` (built in `StoreBaseService::getConfig()`), `IntegrationService::sendRequest(IntegrationRequestOptionsDTO $options, array $data = [])` (built per call in `StoreProductService`).
- Per-type config registry in a controller: `private function params(string $type): Record<Action>ParamsDTO` assigns `$params = match ($type) { '<type>' => new ...DTO(...), default => throw new NotFoundHttpException() };`, then `if (!$params->isAccessible) { throw new NotFoundHttpException(); }` (`http/Common/Record/Controllers/IndexController.php`, `CreateController.php`). A DTO may be passed into an anonymous class (`CreateController`: `new class($params) extends FormRequest` with `private RecordCreateParamsDTO $params` in its constructor, returning `$this->params->nonLocalizedRules` / `localizedRules`).
- Common trait: a fixed set of named, typed values that includes objects or closures (`Model`, `EloquentBuilder`, `\Closure`), produced in many places and read by one mechanism.

## When an array is used instead (default)
- Model observer params: `observerParams(): array` with the shape documented on `App\Base\Model::observerParams()` (`daily_limit`, `sluggable`, `audit_record_action`); the former `Modules\Audit\DTOs\RecordActionParamsDTO` was replaced by it.
- Service/helper inputs and outputs: plain arrays + array-shape PHPDoc (`StockService::takeProducts()` `array<array{product_id: int, quantity: float}>`, `SaleService`, `PurchaseService`); scalars/enums as separate params (`NotificationHelper::insertMultiple()`).
- Request data (`$request->validatedData`), job payloads (`PrepareAndCreateNotificationsJob::$payload`), resources, settings (`singletons()->system->settings->items`).

## Rules
- Class name `<Owner><Subject>DTO`, owner = feature folder: `Integration` + `ServiceConfig` / `RequestOptions`, `Record` + `Index` / `Create` + `Params`. File name = class name.
- Current fields: `IntegrationServiceConfigDTO(name, url, username, password, hasOwnAccount, historyModel, additional = [])`, `IntegrationRequestOptionsDTO(method, path, shouldSaveHistory, additional = [])`, `RecordIndexParamsDTO(query, field, additionalFields = [], isAccessible = true)`, `RecordCreateParamsDTO(model, nonLocalizedRules = [], localizedRules = [], isAccessible = true)`.
- The class is one public constructor with promoted `public` typed properties, body `{}` on the closing-parenthesis line; nothing else.
- Required params first, optional with defaults last (`public array $additional = []`, `public bool $isAccessible = true`).
- Union types when a value genuinely varies: `bool|\Closure $shouldSaveHistory` (evaluated with `is_callable()`), `EloquentBuilder|QueryBuilder $query` (`Kalnoy\Nestedset\QueryBuilder`).
- Class types imported via `use` (`App\Base\Model`, `App\Base\EloquentBuilder`); `\Closure` written with a leading backslash.
- Properties camelCase; booleans `is*` / `has*` / `should*` (`isAccessible`, `hasOwnAccount`, `shouldSaveHistory`).
- PHPDoc only on the constructor and only for array shapes (`IntegrationRequestOptionsDTO`: `@param array{response_callback: \Closure, history_data_callback: \Closure} $additional`); optional keys are read with `?? null`.
- Create with named arguments only (`new IntegrationRequestOptionsDTO(method: 'POST', path: 'product/create', shouldSaveHistory: true)`, closure form `shouldSaveHistory: fn(Response $response) => $response->status() !== 200` in `StoreProductService::list()`); read public properties directly (`$this->config->url`, `$this->requestOptions->additional['response_callback'] ?? null`, `$params->query`, `$params->field`).
- Type the DTO on the consumer side: public property (`IntegrationService::$config`, `::$requestOptions`), param (`sendRequest(IntegrationRequestOptionsDTO $options, array $data = [])`) or return type (`abstract protected function getConfig(): IntegrationServiceConfigDTO`).
- A shared model inside a DTO is a prototype: consumers clone before filling (`(clone $this->config->historyModel)->fill($data)` in `IntegrationService`).

## Template
```php
<?php

namespace Http\<Area>\<Module>\DTOs;

use App\Base\Model;

class <Owner><Subject>DTO
{
    /**
     * @param array{key_one: string, key_two: \Closure} $additional
     */
    public function __construct(
        public Model $model,
        public bool|\Closure $shouldProcess,
        public array $additional = [],
        public bool $isAccessible = true,
    ) {}
}
```

## Don't
- Don't create a DTO for data that is already an array elsewhere (observer params, validated data, service item lists, job payloads) — use an array-shape PHPDoc.
- Don't add methods (getters/setters, `fromArray()`, `toArray()`, static factories, validation), base classes, interfaces, traits, `readonly` or `final`.
- Don't use `spatie/laravel-data` or other DTO packages (not installed).
- Don't name the folder `DTO/` or `Dto/`; don't omit the `DTO` suffix.
- Don't pass DTOs into queued jobs (pass scalars/arrays, see `jobs-rules`).
