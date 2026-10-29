---
name: enums-rules
description: Mandatory standards for PHP enums in modules/<Module>/Enums (using App\Base\EnumTrait) and infrastructure enums in app/**/Enums. Use when creating or editing any enum, or code that stores, validates, compares or labels enum values.
---

# Enum Rules

Domain enums live in `modules/<Module>/Enums/<Model><Column>Enum.php`, namespace `Modules\<Module>\Enums`, and always `use App\Base\EnumTrait`. Examples: `modules/Trade/Enums/SaleStatusEnum.php`, `modules/User/Enums/UserTypeEnum.php`, `modules/Company/Enums/SubscriptionRecordLimitEnum.php`, `modules/System/Enums/DailyLimitIntegrationActionNameEnum.php`.

## Rules
- Always string-backed: `enum XEnum: string`.
- Name = model class (or table entity without a model) + column + `Enum`: `SaleTransactionPaymentTypeEnum` (`trade_sale_transaction.payment_type`), `DailyLimitActionTypeEnum` (`system_daily_limit.action_type`), `EventTypeEnum` (`system_event.type`), `PurchaseStatusEnum`, `RecordActionTypeEnum`. Value sets of a column for one sub-type and key sets of a JSON column follow the same scheme (`DailyLimitIntegrationActionNameEnum` = `action_name` when `action_type = integration`; `SubscriptionRecordLimitEnum` = keys of `company_subscription.record_limits`).
- Cases `UPPER_SNAKE_CASE`, values the lowercase snake_case equivalent (`case PARTIALLY_CANCELLED = 'partially_cancelled';`). Values are stored in DB — never rename existing ones.
- Separate case groups with a blank line, mirrored in `labels()` (`NotificationTypeEnum`, `SubscriptionRecordLimitEnum`).
- Every `modules/*/Enums/*.php` is public: `GET system` (route `common.system`, `auth:sanctum`; `http/Common/System/Controllers/SystemController.php::enums()`) globs them, maps `Modules\Trade\Enums\SaleStatusEnum` → `Trade.SaleStatus`, `Arr::undot()`s into `{Trade: {SaleStatus: ...}}` and returns `labels()` if defined, else value => value; cached per locale under tag `system_public_data` (24h, flushed by `larbox:deploy` and `LanguageObserver::saved`). Hence `EnumTrait` is mandatory and nothing secret goes into an enum.
- `public static function labels(): array` keyed by `self::CASE->value` with a literal Russian source string `__('Частично отменено')` (collected into `lang/*.json` by `larbox:collect_translations`). Omit only for internal enums never labelled (`EventTypeEnum`) — `labelByValue()` calls `labels()` and fails without it. Lang-file labels: `__("user_notifications.$type.label")` (`NotificationTypeEnum`; the `user_notifications.` prefix is skipped by the collector).
- Use `EnumTrait` helpers, don't rewrite them: `values()`, `names()`, `pluck()` (name => value), `fillValuesWith($v)` (value => $v), `labelByValue($value)` (falls back to `ucfirst`), `caseByValue($value)` (throws if missing).
- Extra behavior = static methods only: per-case configuration via a `self::CASE->value => ...` map (`DailyLimitIntegrationActionNameEnum::limit(self $integration): int`, `SubscriptionRecordLimitEnum::valueByClassName()` / `defaults()`), predicates on raw values (`UserTypeEnum::isAdminType(string $type)` / `isCompanyType()`).
- Model attributes stay plain strings (`@property string $status`, no cast); compare with `$model->type === UserTypeEnum::ADMIN->value`.
- Usage:
  - migrations — `$table->enum('status', SaleStatusEnum::values())` (`user_notification.type` is a plain `string`);
  - requests — `Rule::in(SaleTransactionPaymentTypeEnum::values())`, or an explicit `XEnum::CASE->value` list for an allowed subset (`http/Admin/User/Requests/RoleRequest.php`, `NotificationRequest.php`); defaults `$defaults = ['status' => SaleStatusEnum::COMPLETED->value]`; forced values `$this->model->type = UserTypeEnum::COMPANY_EMPLOYEE->value`;
  - search — `'status' => SearchFilterTypeEnum::EQUAL_RAW`;
  - messages — `PurchaseStatusEnum::labelByValue($value)`; seeders — `XEnum::CASE->value`;
  - helpers/services/jobs may type-hint the case (`DailyLimitHelper::decrease(DailyLimitEntityTypeEnum $entityType, ..., DailyLimitActionTypeEnum $actionType)`), obtain it via `caseByValue()` (`CreateNotificationsJob`), then use `->value`.
- A model limited by subscription = new case + label + `valueByClassName()` entry in `SubscriptionRecordLimitEnum` (`defaults()` picks it up).
- Infrastructure enums in `app/**/Enums` (`App\Search\Enums\SearchFilterTypeEnum`, `SearchFilterConditionEnum`, `SearchSortTypeEnum`) are internal: no `EnumTrait`, not exposed, values may be class-strings or method names (`EQUAL_RAW = FilterTypes\EqualRaw::class`, `OR_WHERE = 'orWhere'`), passed around as cases. Add there only for `app/` framework code.

## Template
```php
<?php

namespace Modules\<Module>\Enums;

use App\Base\EnumTrait;

enum <Model><Column>Enum: string
{
    use EnumTrait;

    case CREATED = 'created';
    case COMPLETED = 'completed';
    case PARTIALLY_CANCELLED = 'partially_cancelled';

    public static function labels(): array
    {
        return [
            self::CREATED->value => __('Создано'),
            self::COMPLETED->value => __('Завершено'),
            self::PARTIALLY_CANCELLED->value => __('Частично отменено'),
        ];
    }
}
```

## Don't
- Create int-backed or pure enums in modules, or enums outside an `Enums/` dir.
- Use class constants for DB-stored types/statuses (string consts exist only for exception codes in `modules/*/Exceptions` and route/permission names in `RolePermissionHelper`).
- Re-implement `values()`/`labelByValue()`/etc.; key `labels()` by case name; build `__()` text dynamically (breaks `larbox:collect_translations`) — the only dynamic form is a lang-file key (`user_notifications.*`).
- Cast model attributes to enum classes, or use `Rule::enum()` / `new Enum()` — zero usages.
- Use `XEnum::from()`/`tryFrom()` in app code (only tests do); use `caseByValue()`.
- Add instance methods on cases (`$case->label()`) — only static methods exist.
- Put secrets or internal-only values into `modules/*/Enums` — they are published to every authenticated user.
