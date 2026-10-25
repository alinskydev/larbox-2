# AGENTS.md

Guidance for AI coding agents working in this repository.

## Project overview

**Laravel EXP** (`laravel-exp.loc`) — API-only Laravel 13 backend built on the **Larbox** starter kit. Modular, multi-tenant (company-scoped) REST API: soft deletes, audit/history, role permissions, trade (purchases, sales, stock), Excel import/export, PDF, mail, Telegram, external integrations, PostgreSQL tuned for high load. `resources/` is stock skeleton.

- PHP `^8.3`, Laravel `^13.7`, PostgreSQL only (`app/Database/*`, registered by `App\Providers\DatabaseServiceProvider`)
- `App\Base\Application` binds custom `App\Routing\Router` / `ResourceRegistrar`: `apiResource` = index, show (`{id}`), create, update / delete (`{model}`), restore (`{id}/restore`)
- Auth: Sanctum tokens (`Modules\User\Models\AccessToken`) + `Modules\User\Middleware\RoleMiddleware`; superadmin-only groups add `UserSuperadminMiddleware`
- Packages: fast-excel-writer, phpspreadsheet, dompdf, intervention/image, kalnoy/nestedset, mews/purifier, guzzle; dev: telescope (local/testing only), pint
- Dev: OpenServer on Windows, Git Bash; `APP_URL=http://laravel-exp.loc`, no `/api` prefix
- Languages: `en`, `ru`, `uz` — runtime list from DB via `singletons()->system->languages`; `config/larbox.php` is used by seeders/migrations. Locale from `Accept-Language` (`LocaleMiddleware`). User-facing strings via `__('...')` (Russian source text, `lang/*.json`)

## Directory layout (PSR-4)

| Namespace               | Path            | Purpose                                                                                         |
| ----------------------- | --------------- | ----------------------------------------------------------------------------------------------- |
| `App\`                  | `app/`          | Larbox core: base classes, controllers, requests, search engine, casts, helpers, commands        |
| `Modules\<Module>\`     | `modules/`      | Domain: Audit, Company, History, Integration, Product, Seo, Stock, System, Trade, User          |
| `Http\<Area>\<Module>\` | `http/`         | HTTP layer by area: **Admin**, **Company**, **Common**, **Emulator**                            |
| `Integrations\<Name>\`  | `integrations/` | External services: Store (`Services/`), Telegram (`Helpers/`, `Jobs/`)                          |

### `app/` core

- `Base/` — `Application`, `Model` (+ `Model/ModelSafelyTrait`, `ModelSoftDeleteTrait`, `ModelRelationsTrait`), `EloquentBuilder` (+ `EloquentBuilderFilterByPermissionsTrait`, `EloquentBuilderFilterByUserTrait`), `QueryBuilder` (+ `QueryBuilderRelationsTrait`), `ModelService`, `Resource`, `Filter`, `Job`, `Rule`, `EnumTrait`, `Singleton`
- `Controllers/` — `Controller` (`successResponse()`), `ResourceController` (generic CRUD)
- `Helpers/Controller/` — `ControllerModelHelper` (`setValues`, `toggleValue`, `delete`, `checkCondition`), `ControllerStreamHelper` (`download`)
- `Requests/` — `FormRequest`, `ResourceFormRequest` (+ `DeletableFileFieldsTrait`)
- `Search/` — `Search` engine (`SearchFilter`, `SearchSort`, `SearchShow`, `Filter/Types`, `Enums/`)
- `Casts/` (`LocalStorage/`, `ExternalStorage/`: `AsFile(s)`, `AsImage(s)`), `Excel/` (`Export/`, `Import/`), `NestedSet/`, `Observers/` (`CreatorIdObserver`, `CompanyIdObserver`, `SluggableObserver`, `IPObserver`)
- `Exceptions/BusinessException` — abstract base of `modules/*/Exceptions` (not reported)
- `Integration/` — abstract `IntegrationService`, `IntegrationException`, `DTOs/`; `Mail/` — `Mail`, `MailHelper`, `SendMailJob`
- `Helpers/` (incl. `Database/`: `DatabaseTransactionHelper`, `DatabaseMigrationPartitionHelper`, `DatabaseMigrationIndexHelper`, `DatabaseSeederHelper`; `Validation/`, `PdfHelper`, `ImageHelper`), `Jobs/`, `Middleware/`, `Rules/`, `Routing/`, `Loggers/` (`DatabaseLogger` = default log channel), `Database/`, `Providers/`
- `Testing/` — `Feature/PostmanTestCase`, `Feature/ScenarioTestCase`, `Feature/Helpers/`, `Feature/Traits/`, `Domain/DomainTestCase`, `Unit/`, `CreatesApplicationTrait`
- `Utils/*.php` — global functions loaded by composer `autoload.files`: `user()`, `setUser()`, `setUserAsCompanyDirector()`, `singletons()`, `num_round()`, `num_e/ne/gt/gte/lt/lte()`
- `Console/Commands/` — `App/`, `Larbox/`, `Examples/` (+ `Deploy/` for one-time scripts)

### Module (`modules/<Module>/`)

Only the dirs it needs: `Database/Migrations/<table>.php`, `Database/Seeders/<table>.php`, `Database/<module>-relations.php` (FKs), `Models/`, `Builders/`, `Enums/`, `Exceptions/`, `Observers/`, `Search/`, `Resources/`, `Services/`, `Helpers/`, `Singletons/`, `Jobs/`, `Excel/`, `PDF/`, `Mail/`, `Telegram/`, `Middleware/`, `Requests/`, `Traits/`, `Tests/<Entity>/` (domain tests).

### HTTP (`http/<Area>/<Module>/`)

`routes.php`, `Controllers/` (+ `Traits/<Entity>/`), `Requests/`, `Filters/`, `DTOs/`, `Tests/<Entity>/`. `routes/api.php` globs `http/<Area>/*/routes.php` under `LocaleMiddleware`, `api`, `throttle:api`, `ConcurrentWritesPreventionMiddleware`:

- `admin/`, `company/` — `auth:sanctum` + `RoleMiddleware` + `IdempotencyMiddleware`; the full route name is the permission checked by `RoleMiddleware`, so every route must be named. Superadmin-only routes (Admin History/System/User groups) add `UserSuperadminMiddleware` and are listed in `RoleHelper::$excludedRoutes`
- `common/` — no group auth; each `routes.php` adds `auth:sanctum` where needed (User, Record, System, logout); `common.*` skips the role check; `common/auth` uses the `auth` limiter
- `emulator/` — fake Storage/Store/Telegram services, no auth, non-production only

Typical controller: extends `App\Controllers\ResourceController`, calls `parent::__construct(...)` with model, search, filter and form request classes (resource defaults to `$model->resourceClass`) and gets the CRUD actions. When a `filter` is passed, route-model binding re-applies it, so records of other companies are not reachable by ID. Query params: `with[]`, `filter[]`, `sort[]`, `show[]` (`with_deleted`, `only_deleted`), `page`, `page_size`.

## Commands

```bash
php artisan larbox:migrate   # migrate:fresh over modules/*/Database/Migrations -> seeders -> *-relations.php
php artisan larbox:test      # optimize:clear + larbox:migrate + test suite
php artisan larbox:deploy    # production deploy (one-time deploy scripts, cache flush, optimize, queue:restart, Telegram notice)
php artisan larbox:collect_translations   # collect __('...') texts and field names into lang/
php artisan test             # tests only (DB must already be migrated)
vendor/bin/pint              # code style (pint.json)
```

- Queue workers in dev: `_bats/queue - *.bat`. Every job sets its queue: `excel`, `mail`, `maintenance`, `notification`, `telegram`.
- Scheduler (`routes/console.php`): `telescope:prune` (local), `app:process_db_daily` (00:01; chains `RemoveOldDataJob` + `ProcessDBJob` on `maintenance`, cleans `storage/larbox/_tmp`), `app:remove_idle_db_connections` (hourly).
- `db:seed`, `db:wipe`, `migrate*`, `larbox:migrate`, `larbox:test`, `larbox:export_postman_collection` are blocked outside `local` — never bypass.
- One-time production changes: `larbox:create_file_for_deploy` creates a script in `app/Console/Commands/Deploy/`; `larbox:deploy` runs each once (tracked in `system_event`).
- `larbox:migrate` flushes `Cache::tags(['db_schema'])` — needs a tag-capable cache store (redis).

## Testing

Three suites in `phpunit.xml` (`stopOnFailure`/`stopOnError`; directories are toggled manually by commenting).

- **Feature** (API): `http/<Area>/<Module>/Tests/<Entity>/<Action>Test.php`; methods `test_success`, `test_error___<case>`. Each folder has `_TestCase.php` extending `App\Testing\Feature\PostmanTestCase` with `$userId` (seeded: 1 = superadmin, 2 = director of company 1, 3 = director of child company 1-1, 4 = employee of company 1; omit for public) and `$requestUrl`; it may hold shared tests inherited by per-user subclasses. Each test is rolled back (`DatabaseTransactions` on `main` + `history`). Executed requests are collected and saved to `storage/larbox/_postman.json` after the class; `protected bool $shouldExportToPostman = false` excludes a test.
- **Domain**: `modules/<Module>/Tests/<Entity>/<Action>Test.php`, `_TestCase.php` extends `App\Testing\Domain\DomainTestCase` (`getValues()` + `assertChanges($callback, $expected, $error)`); checks state changes (e.g. stock balances) of services independent of HTTP.
- **Unit**: `app/Testing/Unit/Tests/`, `App\Testing\Unit\TestCase` — no transactions, revert changes manually.
- Real PostgreSQL DB from `.env` (no separate test DB, no sqlite) — `larbox:test` / `larbox:migrate` wipe it. `APP_ENV=testing`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `MAIL_MAILER=array`, `APP_DEBUG=false` (tests rely on the JSON error mapping).
- `TESTING_SHOW_DETAILED_ERROR=true` prints the trace of a failed request and stops the run.

## Architecture notes

- **DB connections**: `main` (default), `history` (audit/history), `telescope`. Migrations are per-module, timestamp-less, applied only via `larbox:migrate` (`database/migrations/` holds only framework tables); FKs live in `<module>-relations.php` and run after seeders. Partitioning and indexes via `app/Helpers/Database`.
- **Models**: no `$fillable` — every real column is fillable (column list cached under `db_schema`). Flags `$shouldHandleDailyLimit`, `$shouldSkipAutoFilters`; `$additionalConnections` joins other connections into `safely*()` transactions.
- **Multi-tenancy**: models declare `$autoFilters` (`current_company`, `nested_companies`, `holding_companies`, `administered_companies`), applied in `Model::newQuery()`; skipped without a user or via `queryWithoutAutoFilters()`.
- **Observers** are registered with `self::observe([...])` in model `booted()` (`RecordActionModelTrait` registers its own); params come from `observerParams()` (`daily_limit`, `sluggable`, `audit_record_action`).
- **Persistence**: `safelySave()`, `safelyDelete()`, `safelyRestore()`, `safelyDBProcess()` (transaction-wrapped); related rows via `fillRelations(...)`. Domain processes (sale, purchase, stock) live in `modules/*/Services`.
- **Locks**: only `DatabaseTransactionHelper::lock()` / `lockModel()` inside a transaction (released after commit/rollback); never `lockForUpdate()`.
- **Business errors**: `modules/<Module>/Exceptions/<Entity>Exception extends BusinessException` with error constants; rendered as 400 `{message}`, not reported.
- **Requests**: result in `$validatedData`; `nonLocalizedRules()` / `localizedRules()`, `$defaults`, `$rawFields`, `additionalValidation()`; uploads via `$fileUploads` / `$imageUploads` (local or external storage, `config/integrations.php`). `ResourceFormRequest` returns 409 on stale `updated_at` (optimistic locking). Input escaped in `prepareForValidation()`.
- **Enums**: `modules/*/Enums` are published to the frontend by `SystemController::enums()` (cache tag `system_public_data`).
- **Excel**: import `phpoffice/phpspreadsheet`, export `avadim/fast-excel-writer`; sync up to the job's `$instantLimit`, otherwise queued (202, file delivered via notification); `$totalLimit` caps exports.
- **PDF**: `App\Helpers\PdfHelper` (dompdf), PHP views in `modules/<Module>/PDF/`, `?format=pdf`. **Mail**: `MailHelper::send()` (queued on `mail`, views `modules/<Module>/Mail/<view>/<locale>.php`).
- **Telegram**: `Integrations\Telegram\Helpers\TelegramHelper` over plain HTTP (MarkdownV2), queued `TelegramJob`.
- **Integrations**: extend `App\Integration\IntegrationService` (relogin on 401/403/500, tokens in `Integration\Models\Token`, optional history in `History\Models\StoreHttpLog`). External file storage uses plain `Http`, not `IntegrationService`.
- **Limits**: `Modules\System\Observers\DailyLimit`, `Modules\Company\Observers\Subscription`.
- **Concurrency**: `ConcurrentWritesPreventionMiddleware` (409 on parallel writes, off in local/testing); `IdempotencyMiddleware` (`Idempotency-Key` on POST, replay header `Idempotent-Replayed`).
- **Errors**: JSON, centralized in `bootstrap/app.php` (skipped when `APP_DEBUG`): 400/401/404/422/429, record not found → 400, SQLSTATE `22003`, `22P02`, `23503` rewritten.
- **Rate limit**: `api` limiter (300/min prod, 1000/min otherwise, per user or IP), `auth` limiter for `common/auth` (5/min login, 3/min others, 10/h per username). `id`/`index`/`model` route params are digits only.
- **Security**: secrets only from `.env` (never commit); Sanctum token check rejects users of soft-deleted companies (`AppServiceProvider`).

## Mandatory skills

`.claude/skills/<name>/SKILL.md` define per-layer standards. Read the relevant one before creating or editing a file:

- Data: `model-rules` (incl. builders, casts, nested sets), `database-rules`, `enums-rules`, `observers-rules`, `services-rules`, `resources-rules`, `search-rules`, `exceptions-rules`
- HTTP: `http-controllers-rules`, `http-requests-rules`, `http-filters-rules`, `http-routes-rules`, `http-tests-rules` (incl. domain and unit tests), `middleware-rules`
- Infrastructure: `helpers-rules` (incl. global functions, singletons, locks), `jobs-rules`, `commands-rules`, `dto-rules`
- Features: `excel-rules` (incl. PDF), `mail-rules`, `telegram-rules`, `integrations-rules` (incl. Store, Storage, emulators)

## Rules

- Keep the conversation and reasoning concise — no fluff.
- Summaries must be short.
- Write the simplest possible code. Avoid over-abstraction, premature optimization, and complex design patterns when the task can be solved with basic tools.
- Follow YAGNI (You Aren't Gonna Need It). Implement only what is needed right now. Don't write code "just in case."
- Match the existing codebase style (Coding Style Alignment).
- Always use strict comparison `===` / `!==`, never `==` / `!=`.
- Prepare values in variables before building arrays/calls; move repeated logic into a private method, not a `$fn = fn()` closure.
- When fixing existing code, keep the author's structure and make a minimal diff.
- Change only what was asked. Never revert, comment/uncomment or re-apply code on your own initiative. In renames, rename only — signature/behavior changes must be proposed separately.
- Minimize the number of entities. Don't create new classes, interfaces, or methods unless absolutely necessary. If logic fits cleanly into an existing method/class without violating its responsibility, put it there.
- Strict file size limit. Maximum 300 lines of code per class. If a class approaches this limit, it's a signal to rethink the logic, not to blindly split it. Code should be compact through effective writing, not spread across files.
- No unauthorized class types. Create only the types and categories of classes that are explicitly described and permitted in your system skill set.
- Do not deviate from the architectural template. If your available skills don't describe a specific pattern or component type, its use in code is strictly forbidden.
- Run `vendor/bin/pint` on edited files.
- If you have questions, always ask.

## Prohibited

- Don't go beyond the project scope or touch third-party utilities like Redis. If they're listed as active, they exist and work properly.
- Don't browse project files which aren't needed for the task.
- Don't go out of `/app`, `/http`, `/integrations`, `/modules` folders.
- Don't install third-party packages.
