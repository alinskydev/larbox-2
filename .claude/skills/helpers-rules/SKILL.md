---
name: helpers-rules
description: Mandatory standards for static helper classes in app/Helpers, modules/*/Helpers and integrations/*/Helpers, global functions in app/Utils, singletons (App\Base\Singleton, modules/*/Singletons), module mixin traits in modules/*/Traits and logging in app/Loggers. Use when creating or editing a helper, a global function, a singleton, a module trait, a lock, or deciding where a reusable utility belongs.
---

# Helpers Rules

Helpers: `app/Helpers/[<Group>/]<Subject>Helper.php` (`App\Helpers[\<Group>]`, domain-agnostic), `modules/<Module>/Helpers/[<Entity>/]<Subject>Helper.php` (`Modules\<Module>\Helpers[\<Entity>]`), feature helpers next to their feature (`app/Excel/Export/ExcelExportHelper.php`, `app/Mail/MailHelper.php`, `integrations/Telegram/Helpers/TelegramHelper.php`). No base class.
Canonical: `app/Helpers/StringHelper.php`, `modules/System/Helpers/DailyLimitHelper.php`, `app/Helpers/Database/DatabaseTransactionHelper.php`.

## Rules
- A helper is a plain class with only static methods: `public static` API, `private static` / `protected static` internals (`FileHelper::prepareFolder()`, `ValidationFileRulesHelper::generate()`). No constructor, no instance state, no `extends`.
- Call siblings via `static::` (`static::formatAmount()`, `static::lock()`, `static::thumbnail()`).
- Allowed static state: `public const` (`RolePermissionHelper::PERMISSION_ALL_RECORDS`), `protected static array` config lists (`RoleHelper::$excludedRoutes`), `private static array` per-process memo (`ClassHelper::$uses`, `RoleHelper::$availableRoutes`, `DatabaseTransactionHelper::$locks`).
- Subfolder name prefixes the class: `Database/DatabaseMigrationIndexHelper`, `Validation/ValidationNumberRulesHelper`, `Controller/ControllerStreamHelper`, `modules/User/Helpers/Role/RolePermissionHelper`.
- `app/Helpers` never imports `Modules\*`. Logic on module models/enums goes to `modules/<Module>/Helpers`: cross-entity or bulk work (`DailyLimitHelper::ensure()/decrease()`, `NotificationHelper::insertMultiple()` via `Model::query()->getQuery()->insert()`, `RoleHelper::routeList()/canAccessRoute()/fullRouteNames()`, `SearchHelper::createdAtInterval()`).
- Logic for one model instance goes to its service (`$model->getService()`, e.g. `StockService::takeProducts()`, `UserService::allRoleRoutes()` used by `RoleHelper`), not to a helper.
- Lock-free atomic counters in helpers: `insertOrIgnore()` the default row, then a conditional `->where('remaining', '>=', $n)->decrement('remaining', $n)` and throw when it returns 0 (`DailyLimitHelper::decrease()`).
- Typed signatures; enums instead of raw strings (`DailyLimitEntityTypeEnum $entityType`, `DailyLimitActionTypeEnum $actionType`, `NotificationTypeEnum $type`); one param per line with trailing comma when the list is long; callers use named arguments (`DailyLimitHelper::ensure(entityType: ..., entityId: ..., actionType: ..., actionName: ...)` in `StoreProductService`, `NotificationHelper::insertMultiple(recipientIds: ..., type: ...)` in `ExcelExportJob`, `PdfHelper::download(path: ..., name: ..., params: ...)`).
- Errors (see `exceptions-rules`): module helpers throw the module exception (`DailyLimitException`, `HistoryException` in `SearchHelper`); HTTP status via Symfony exceptions, not `abort()` (`AccessDeniedHttpException` in `RoleHelper::canAccessRoute()`, `NotFoundHttpException` in `RolePermissionHelper::registerAllRecordsRoute()`); `app/Helpers` uses `\Exception(__('...'))` for user-facing errors (`ControllerModelHelper::checkCondition()`) and `IntegrationException` for external failures (`ErrorHelper::abortProjectNotWorkingError()`).
- Reuse before writing: `ClassHelper::uses()` (trait check, memoized), `StringHelper::uuid()/escapeTags()/escapeForLikeCondition()/escapeForMarkdownV2()/normalizeSpaces()`, `ArrayHelper::fillByTemplate()/asMultilineString()`, `NumberHelper::formatAmount()/formatAmountWithCurrency()`, `FileHelper::save()/upload()`, `ImageHelper::resizeAndUpload()/thumbnail()/populateSizes()`, `PdfHelper::create()/download()` (dompdf, views from a module path, e.g. `modules/Product/PDF`), `RequestHelper::page()/pageSize()/storageHttpHeaders()` (`abortUnlessTesting()` has zero usages), `HtmlPurifierHelper::process()`, `ReflectionHelper::classByFile()`, `ControllerModelHelper::setValues()/toggleValue()/delete()/checkCondition()`, `ControllerStreamHelper::download()`, `Database*Helper` (migrations/seeders), `ValidationFileRulesHelper::image()/logo()/document()/pdf()/excel()/media()/audio()/imageOrDocument()`, `ValidationNumberRulesHelper::amount()`, `ExcelExportHelper::process()/sanitizeRow()`, `ExcelImportHelper::downloadExample()`, `MailHelper::send()`, `TelegramHelper::sendMessage()/sendFile()/sendGroupedFiles()/devChats()`.
- Use Laravel `Arr`/`Str` inside helpers (`ArrayHelper` wraps `Arr::map`); don't re-implement them.

## Locks (DatabaseTransactionHelper)
- Lock only via `DatabaseTransactionHelper::lockModel($model)` (key `<table>:<pk>`) or `DatabaseTransactionHelper::lock('<key>')` (cache lock, blocks up to 60 s). Never `lockForUpdate()` / `sharedLock()`.
- Call inside a transaction (`safelyDBProcess()`, `safelySave()`, or an observer fired by them): the lock is released on `DB::afterCommit` / `afterRollBack`; outside a transaction it is released at once. Re-locking the same key in one process is a no-op.
- After locking, re-read state from DB, not from the loaded model: `PurchaseService::lockAndEnsurePending()` (`->whereKey(...)->value('status')`).
- Key examples: `lockModel($this->model)` in `StockService`, `SaleService`; `"company_subscription_limit:$limitKey:$company->id"` in `SubscriptionCheckLimitObserver`; `"{$table}:tree:$companyId"` in `NestedSetModelTrait`, `NestedSetController`.

## Global functions (app/Utils)
- Files `app/Utils/<topic>.php` (lowercase plural: `numbers.php`, `singletons.php`, `users.php`), loaded by composer `autoload.files` — a new file must be added there. No namespace, no class.
- Every function is wrapped in `if (!function_exists('<name>')) { ... }`, fully typed; structured return gets an object-shape PHPDoc (`singletons()`).
- Existing: `user(): ?User` (`request()->user()`), `setUser(User|int|null $user, bool $withTrashed = false): ?User` (sets the request user resolver; `null` clears), `setUserAsCompanyDirector(Company|int $company, bool $withTrashed = false): User`, `singletons(): object`, `num_round()` (10 decimals, `null` → 0), `num_e/num_ne/num_gt/num_gte/num_lt/num_lte()`.
- Current user only via `user()` (no `auth()->user()`, `Auth::user()`, `request()->user()` in code; the only exception is the `api` rate limiter in `bootstrap/app.php`). Set it in console/tests with `setUser($id)` (`CollectTranslationsCommand`, `RunCommand`, `PostmanTestCase`, `DomainTestCase`) or after auth checks (`ResetPassword\CompleteRequest`); jobs restore it themselves (`App\Base\Job::middleware()`: `setUser($job->userId, true)`, then `setUser(null)` in `finally`).
- Compare and round floats (quantities, amounts) with `num_*`: `num_lte($quantity, 0)`, `num_round($quantity - $takenQuantity)` in `StockService::takeProducts()`.
- Naming: snake_case with topic prefix for primitives (`num_gt`), camelCase for context accessors (`user()`, `setUser()`). Add a global function only for tiny, very frequent cross-cutting access; everything else is a helper.

## Singletons
- Base `App\Base\Singleton`: `protected function __construct() {}`, `abstract public static function init(): static`.
- `modules/<Module>/Singletons/<Entity>Singleton.php` extends `Singleton`: `protected static self $instance;`, public typed data props, `init()` fills them once inside `if (!isset(static::$instance))` (`LanguageSingleton`, `SettingSingleton`).
- Data is cached as arrays: `Cache::remember('<module>_<plural>', 86400, fn)` (`system_languages`, `system_settings`).
- Invalidate with `Cache::forget('<key>')` where the data changes (`LanguageObserver::saved()`, `http/Admin/System/Controllers/SettingController.php`) and in `DeployCommand::handle()`.
- `singletons()` memoizes in a function `static $items`, so a forgotten cache key takes effect on the next request/job, not in the current process.
- Expose only through `singletons()` (`app/Utils/singletons.php`): `$items-><module>-><name> = <Entity>Singleton::init();` and extend the `@return object{...}` shape. Access: `singletons()->system->languages->all|active|main`, `singletons()->system->settings->items`.
- Keyed values via `__get(string $name)` over `$items` plus a class `@property` PHPDoc per key (`SettingSingleton`).
- No side effects (locale is set in `LocaleMiddleware`), no business methods.

## Module traits (modules/*/Traits)
- Mixins for one target type, named `<Feature><Target>Trait`: `RecordActionModelTrait`, `SeoMetaModelTrait` (models), `SeoMetaFormRequestTrait` (form requests). Shared infrastructure traits live in `app/` (`app/Base/Model/*Trait.php`, `app/NestedSet/NestedSetModelTrait.php`).
- Class PHPDoc `@mixin <Target>` (`@mixin Model`, `@mixin ResourceFormRequest`); no constructor.
- Content order: typed state props, accessors `get<X>Attribute()`, snake_case relations with return type (`seo_meta(): MorphOne`, `audit_record_actions(): MorphMany`), service getter (`getRecordActionCreateService()`), `protected static function boot<TraitName>(): void` last.
- Consumers detect a trait with `ClassHelper::uses($object, <Trait>::class)` (`ResourceController`, `ResourceFormRequest`).

## Logging (app/Loggers)
- Default channel `database` (`config/logging.php`, `via` `DatabaseLogger`) → `DatabaseLoggerHandler` inserts into `audit_error_log` (user, level, source, `<class> (<message>)`, 7 trace frames, request info with status), masks secrets in `hideSecrets()`, falls back to the `daily` channel.
- Log as `Log::error($e->getMessage(), ['exception' => $e])` in the `catch` of jobs (`ExcelExportJob`, `SendMailJob`, `TelegramJob`, `ProcessDBJob`); the handler takes source and trace from `context['exception']`. Business exceptions are never logged (`ShouldntReport`).

## Template
```php
<?php

namespace Modules\<Module>\Helpers;

use Modules\<Module>\Enums\<Type>Enum;
use Modules\<Module>\Exceptions\<Entity>Exception;
use Modules\<Module>\Models\<Entity>;

class <Subject>Helper
{
    public static function ensure(
        <Type>Enum $type,
        int $entityId,
        string $name,
    ): void {
        $model = <Entity>::query()
            ->where(static::keys($type, $entityId, $name))
            ->first();

        if ($model && $model->remaining <= 0) {
            throw new <Entity>Exception(<Entity>Exception::EXCEEDED);
        }
    }

    public static function decrease(
        <Type>Enum $type,
        int $entityId,
        string $name,
        int $defaultValue,
        int $decreaseValue = 1,
    ): void {
        $keys = static::keys($type, $entityId, $name);

        <Entity>::query()->getQuery()->insertOrIgnore([...$keys, 'remaining' => $defaultValue]);

        $decreased = <Entity>::query()
            ->where($keys)
            ->where('remaining', '>=', $decreaseValue)
            ->decrement('remaining', $decreaseValue);

        if (!$decreased) {
            throw new <Entity>Exception(<Entity>Exception::WILL_BE_EXCEEDED);
        }
    }

    private static function keys(<Type>Enum $type, int $entityId, string $name): array
    {
        return [
            'type' => $type->value,
            'entity_id' => $entityId,
            'name' => $name,
        ];
    }
}
```

## Don't
- Don't add instance methods, constructors, DI or inheritance to helpers; don't name utility classes `*Utils`, `*Tools`, `*Manager`.
- Don't import module models/enums into `app/Helpers`.
- Don't declare global functions outside `app/Utils`, without the `function_exists` guard or without a composer `autoload.files` entry.
- Don't use `lockForUpdate()`, `sharedLock()` or raw `Cache::lock()` for DB consistency — use `DatabaseTransactionHelper`.
- Don't compare floats with raw `===`, `<`, `>`; don't read the user via `auth()`.
- Don't call `abort()` in helpers (legacy `abort(202)` in `ExcelExportHelper::process()` only); throw the module or HTTP exception.
- Don't register singletons in the container or read them via `app('...')`; don't instantiate them with `new` — only `singletons()`.
