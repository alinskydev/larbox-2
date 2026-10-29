---
name: exceptions-rules
description: Mandatory standards for business exceptions in modules/<Module>/Exceptions (extending App\Exceptions\BusinessException) and for throwing user-facing domain errors from services, observers, requests, helpers and controllers. Use when creating or editing an exception class or adding a new business error.
---

# Exceptions Rules

Location: `modules/<Module>/Exceptions/<Entity>Exception.php`, namespace `Modules\<Module>\Exceptions`, always extends `App\Exceptions\BusinessException` (`abstract`, `extends \Exception implements ShouldntReport`, `__construct(public string $error, array $replace = [])`, `abstract protected function messages(): array`).
Canonical examples: `modules/Trade/Exceptions/SaleException.php`, `modules/Company/Exceptions/SubscriptionException.php`, `modules/User/Exceptions/RoleException.php`.

## Rules
- One class per entity/feature: `<Entity>Exception` (`SaleException`, `RoleException`, `DailyLimitException`). Add a new error to the existing class of that entity. Same-named errors are duplicated per class (`UNDELETABLE` in `RoleException` and `UserException`), not shared.
- Each error = `public const <ERROR> = '<error>';` — UPPER_SNAKE name, value is the same name in lower snake_case. Constants sorted alphabetically.
- `protected function messages(): array` maps every constant to a Russian message, same order as the constants: `self::NOT_ENOUGH_PRODUCT => 'Недостаточно товара ":name" на складе'`.
- Placeholders use Laravel `:name` syntax; quote names/statuses/labels (`":name"`, `":status"`), leave numbers and times bare (`:limit`, `ID :id`, `:time`).
- Throw with the constant and named replacements: `throw new StockException(StockException::NOT_ENOUGH_PRODUCT, ['name' => $name]);`. Labels are prepared by the caller (`PurchaseStatusEnum::labelByValue(...)`); a conditional error goes into `$error` first (`SubscriptionCheckLimitObserver`).
- The base constructor translates via `__($messages[$error], $replace)` — never call `__()` yourself. `larbox:collect_translations` scans only `__('...')` literals, so add each new message with `en` / `uz` values to `lang/*.json` manually.
- `$exception->error` holds the constant value; domain tests assert it (`assertChanges(..., error: SaleException::PRICE_NOT_SET)`), see http-tests-rules.
- Thrown from services (`SaleService`, `PurchaseService`, `StockService`, `AuthCodeService`), observers (`RoleObserver`, `UserObserver`, `LanguageObserver`, `OptionObserver`, `SubscriptionCheckLimitObserver`), requests (`modules/User/Requests/RoleRequest.php`, `http/Admin/User/Requests/UserRequest.php`), helpers (`DailyLimitHelper`, `History\Helpers\SearchHelper`), controllers (`http/Admin/User/Controllers/UserController.php`, `http/Common/User/Controllers/NotificationController.php`).
- Rendering is automatic: the `bootstrap/app.php` fallback returns `{"message": "..."}` with 400 (no `getStatusCode()`); skipped when `APP_DEBUG=true`. `ShouldntReport` keeps them out of `audit_error_log`. Inside `safely*()` / `safelyDBProcess()` they roll back `main` and `additionalConnections`.

## Non-business errors (instead of abort())
- 404: `throw new NotFoundHttpException()` (`http/Common/Record/Controllers/IndexController.php`, `RolePermissionHelper`); record not found / not applicable: `throw new ModelNotFoundException()` or `findOrFail()` → 400 `Запись не найдена` (`NotificationController::download`); 403: `throw new AccessDeniedHttpException()` (`RoleMiddleware`, `RoleHelper::canAccessRoute`); 429: `throw new ThrottleRequestsException()` (`ConcurrentWritesPreventionMiddleware`).
- Legacy `abort()` left: `abort(409)` in `ResourceFormRequest`, `abort(403, ...)` in `UserSuperadminMiddleware`, `abort(202, ...)` in `ExcelExportHelper::process`.
- `app/` core cannot import module exceptions: user-facing errors there are `throw new \Exception(__('...'))` → 400, but logged (`ControllerModelHelper::checkCondition`, `ExcelExportHelper`, `ExcelImportFormRequest`, `NestedSetModelTrait`). Developer misconfiguration: `\Exception('...')` without `__()` (`ModelService`, `ResourceController`, `QueryBuilder`). External APIs: `App\Integration\IntegrationException`.

## Template
```php
<?php

namespace Modules\<Module>\Exceptions;

use App\Exceptions\BusinessException;

class <Entity>Exception extends BusinessException
{
    public const ATTACHED_TO_PRODUCTS = 'attached_to_products';
    public const NOT_PENDING = 'not_pending';

    protected function messages(): array
    {
        return [
            self::ATTACHED_TO_PRODUCTS => '<Сущность> ":name" не подлежит удалению, так как она привязана к продуктам ":products"',
            self::NOT_PENDING => 'Изменять можно только <сущность> в статусе ":status"',
        ];
    }
}
```

## Don't
- Don't use `abort(400, ...)`, `\Exception` / `\RuntimeException` for business errors in `modules/` or `http/`; don't add new `abort()` calls at all — throw the matching HTTP exception.
- Don't add `render()`, `report()`, `getStatusCode()` or custom constructors to exception classes; don't register handlers for them in `bootstrap/app.php`.
- Don't create exceptions outside `modules/<Module>/Exceptions`. `app/Exceptions` holds only the abstract base.
- Don't pass raw strings as `$error`; always the class constant.
