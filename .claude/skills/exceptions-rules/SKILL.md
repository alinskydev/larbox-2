---
name: exceptions-rules
description: Mandatory standards for business exceptions in modules/<Module>/Exceptions (extending App\Exceptions\BusinessException) and for throwing user-facing domain errors from services, observers, requests, helpers and controllers. Use when creating or editing an exception class or adding a new business error.
---

# Exceptions Rules

Location: `modules/<Module>/Exceptions/<Entity>Exception.php`, namespace `Modules\<Module>\Exceptions`, always extends `App\Exceptions\BusinessException`.
Canonical examples: `modules/Trade/Exceptions/SaleException.php`, `modules/Stock/Exceptions/StockException.php`, `modules/User/Exceptions/RoleException.php`.

## Rules
- One class per entity/feature: `<Entity>Exception` (`SaleException`, `RoleException`, `DailyLimitException`). Add a new error to the existing class of that entity instead of creating a new class.
- Each error = `public const <ERROR> = '<error>';` — UPPER_SNAKE name, value is the same name in lower snake_case. Constants sorted alphabetically.
- `protected function messages(): array` maps every constant to a Russian message, same order as the constants: `self::NOT_ENOUGH_PRODUCT => 'Недостаточно товара ":name" на складе'`.
- Placeholders use Laravel `:name` syntax; quote names/statuses inside the message (`":name"`), leave numbers bare (`:limit`, `:available`).
- Throw with the constant and named replacements: `throw new StockException(StockException::NOT_ENOUGH_PRODUCT, ['name' => $name]);`. The base constructor translates via `__($messages[$error], $replace)` — never call `__()` yourself.
- `$exception->error` holds the constant value; domain tests assert it (`DomainTestCase::assertChanges(..., error: SaleException::PRICE_NOT_SET)`), see http-tests-rules.
- Thrown from: services (`Trade\Services\SaleService`), observers (`User\Observers\RoleObserver`, `Company\Observers\Subscription\SubscriptionCheckLimitObserver`), requests (`User\Requests\RoleRequest`), helpers (`System\Helpers\DailyLimitHelper`), controllers (`http/Admin/User/Controllers/UserController.php`).
- Rendering is automatic: the fallback in `bootstrap/app.php` returns `{"message": "..."}` with status 400 (skipped when `APP_DEBUG=true`). `BusinessException` implements `ShouldntReport`, so business errors are not logged.
- Thrown inside `safely*()` / `safelyDBProcess()` the exception rolls back the transaction on `main` and additional connections (`App\Base\Model\ModelSafelyTrait`).

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
- Don't use `abort(400, ...)` or generic `\Exception` / `\RuntimeException` for business errors. `abort()` is only for 403/404 access checks and 409 conflicts (`RoleMiddleware`, `ResourceFormRequest`, `ConcurrentWritesPreventionMiddleware`).
- Don't add `render()`, `report()`, `getStatusCode()` or custom constructors to exception classes; don't register handlers for them in `bootstrap/app.php`.
- Don't create exceptions outside `modules/<Module>/Exceptions`. `app/Exceptions` holds only the abstract base. Integration errors use `App\Integration\IntegrationException` (see integrations-rules).
- Don't pass raw strings as `$error`; always the class constant.
