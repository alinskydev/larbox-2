---
name: jobs-rules
description: Mandatory standards for queued jobs in app/Jobs, modules/*/Jobs, integrations/*/Jobs and feature jobs (app/Mail, app/Excel), queue names, queue config and _bats queue workers. Use when creating or editing a job, dispatching one, or adding a queue.
---

# Jobs Rules

Jobs live in `app/Jobs/` (maintenance: `ProcessDBJob`, `RemoveOldDataJob`), `modules/<Module>/Jobs/` (domain), `integrations/<Name>/Jobs/` (transport: `TelegramJob`) and next to their feature (`app/Mail/SendMailJob.php`, `app/Excel/Import/ExcelImportJob.php`, `app/Excel/Export/ExcelExportJob.php`, `modules/<Module>/Excel/Export/Jobs/`). All extend `App\Base\Job`.
Canonical: `modules/Product/Jobs/SendCompanyProductsByTelegramJob.php`, `modules/User/Jobs/CreateNotificationsJob.php`, `app/Jobs/ProcessDBJob.php`, `app/Excel/Import/ExcelImportJob.php`.

## Base class (`App\Base\Job`)
- `implements ShouldQueue` + `Dispatchable`, `InteractsWithQueue`, `Queueable`; `public int $timeout = 600`, `public bool $failOnTimeout = false` (timed-out job is retried after `retry_after` = 660); `retryUntil()` = `now()->addYear()`.
- Constructor captures `protected ?int $userId = user()?->id` and `protected ?string $locale = app()->getLocale()`.
- `final middleware()` runs `setUser($userId, true)` (with trashed) + `app()->setLocale($locale)` before `handle()` and `setUser(null)` in `finally`. Inside `handle()` `user()` is the dispatcher; `$this->userId` / `$this->locale` are available (`CreateNotificationsJob`: `creatorId: $this->userId`, `ExcelExportJob`: `recipientIds: [$this->userId]`, `SendMailJob`: `->locale($this->locale)`, `ExcelImportJob`: `user()->updateQuietly(...)`).
- `final failed()` is empty on purpose: Laravel may call it without user/locale. All failure handling goes into `handle()`.
- A job built without a current user (console, scheduler, deploy) has `userId = null`, runs with `user() === null`, so model `$autoFilters` are skipped.

## Rules
- Extend `App\Base\Job` (Excel exports: abstract `App\Excel\Export\ExcelExportJob` with `createWriter()` + static `$instantLimit` / `$totalLimit`, see `excel-rules`). Never implement `ShouldQueue` or use the queue traits directly.
- Name `<Verb><Object>Job`: `CreateNotificationsJob`, `PrepareAndCreateNotificationsJob`, `SendCompanyProductsByTelegramJob`, `RemoveOldDataJob`, `ProcessDBJob`. One job = one `handle()` action.
- Constructor: promoted properties with trailing comma, `private` by default (`app/Jobs/*`, `ExcelImportJob`, `SendMailJob`, `TelegramJob`, `SendCompanyProductsByTelegramJob`); `modules/User/Jobs/*` and abstract `ExcelExportJob` use `protected` — match the file. Body is always `parent::__construct();` then `$this->queue = '<name>';` (never `onQueue()`).
- Never pass the user/creator id — it is captured by the base constructor.
- Pass scalars, id arrays, plain arrays, class-strings (`ExcelImportJob::$modelClass`) and SQL + bindings (`ExcelExportJob`); reload models inside the job through private query methods (`SendCompanyProductsByTelegramJob::company()` / `products()`). Allowed objects: a mailable (`SendMailJob::$mail`) and closures wrapped in `Laravel\SerializableClosure\SerializableClosure` (`TelegramJob::$callback`, `RemoveOldDataJob::$additionalQuery`).
- Eager-load everything the job reads: `->with([...])->getQueryWithRelations()` (`ProductExcelExportJob`, `SendCompanyProductsByTelegramJob`). `Model::preventLazyLoading()` is on in `local`/`testing`, so lazy loading on a collection throws there. Big sets: `lazyByIdDesc(10000)`.
- Queues in code: `excel` (Excel import/export), `mail` (`SendMailJob`), `maintenance` (`ProcessDBJob`, `RemoveOldDataJob`), `notification` (User notifications), `telegram` (`TelegramJob`, Telegram mailings). Reuse one before adding a new queue; a new queue needs `_bats/queue - <name>.bat`.
- Override `retryUntil(): Carbon` (`Illuminate\Support\Carbon`) only to shorten: `now()->addDay()` for external delivery (Telegram, mail), `now()->addMonth()` for notifications. Maintenance and Excel jobs keep the base.
- `handle(): void` wraps the whole body in `try { ... } catch (\Throwable $e) { ... }`. In the catch first decide, then log:
  - `$this->release(60 * 10);` for transient/external failures (notifications, mail, Telegram);
  - `$this->fail($e);` for deterministic failures (DB statements, Excel import/export, Telegram 4xx except 429 via `TelegramJob::isClientError()`);
  - then `Log::error($e->getMessage(), ['exception' => $e]);`;
  - user-facing failure side effects follow in the same catch (`ExcelImportJob` / `ExcelExportJob` insert an `EXCEL_*_FAILED` notification).
- Per-item errors that must not stop the batch: inner `try/catch` per record collecting messages (`ExcelImportJob::$failedRecords`, keyed by `$recordLabel`), reported once after the loop. Cleanup that must run on success and failure goes into `finally` (`ExcelImportJob`: on `isLast` resets `user()->is_excel_import_running` — a `user` column, set by `ExcelImportFormRequest::passedValidation()` — and sends `EXCEL_IMPORT_COMPLETED`).
- Keep `handle()` thin: delegate to a Helper / Template / Service with named arguments (`NotificationHelper::insertMultiple(...)`, `ProductMailingTelegramTemplate::process(...)`).
- Fan out large sets into child jobs of 1000: `chunkById(1000, ...)` in `PrepareAndCreateNotificationsJob`, `array_chunk(..., 1000)` + `isLast` flag in `ProductControllerExcelImportTrait::excelImportProcess()`.

## Dispatching
- Always `dispatch(new <Name>Job(...));`. Named arguments when there is more than one param (`new CreateNotificationsJob(userIds: ..., type: ..., text: ...)`); a single param may be positional (`new SendCompanyProductsByTelegramJob($company->id)`, `new PrepareAndCreateNotificationsJob($request->validatedData)`).
- Sequential maintenance steps: one `Bus::chain($jobs)->dispatch()` per table (`ProcessDBDailyCommand::processDB()`). Batches (`job_batches`) are not used.
- Use the feature wrappers instead of dispatching their jobs yourself: `MailHelper::send()` (`SendMailJob`), `TelegramHelper::sendMessage()/sendFile()/sendGroupedFiles()` (one `TelegramJob` per chat/group), `ExcelExportHelper::process()` (instant `handleSync()` up to `$instantLimit`, else dispatch + `abort(202)`).
- `config/queue.php`: every async connection has `after_commit => true` (jobs dispatched inside `safely*()` are pushed after commit) and `retry_after` 660 (`database`, `beanstalkd`, `redis`). Driver `database`: table `job_active`, failed jobs `job_failed` (uuids, purged after 7 days by `app:process_db_daily`). Default connection falls back to `sync`; `.env.example` and `phpunit.xml*` use `QUEUE_CONNECTION=sync`.
- With `sync` (tests, dev) the job runs inline inside `dispatch()` and its middleware leaves `user() === null` afterwards: dispatch at the end of the flow, never rely on `user()` after `dispatch()`.

## Queue workers (_bats)
- `_bats/queue - <name>.bat`: `cd ../` + `php artisan queue:work --queue=<name>`; `queue - default.bat` runs plain `queue:work`.
- Existing bats: default, excel, mail, maintenance, notification, recalculation, telegram, tmp (`default`, `recalculation`, `tmp` have no jobs in code).

## Template
```php
<?php

namespace Modules\<Module>\Jobs;

use App\Base\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\<Module>\Helpers\<Entity>Helper;

class <Verb><Object>Job extends Job
{
    public function __construct(
        private array $ids,
        private string $type,
    ) {
        parent::__construct();
        $this->queue = '<queue>';
    }

    public function retryUntil(): Carbon
    {
        return now()->addMonth();
    }

    public function handle(): void
    {
        try {
            <Entity>Helper::<action>(
                ids: $this->ids,
                type: $this->type,
                creatorId: $this->userId,
            );
        } catch (\Throwable $e) {
            $this->release(60 * 10);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
```

## Don't
- Don't skip `parent::__construct()` — user and locale won't be restored.
- Don't override `failed()` or `middleware()` (final); don't add job middleware, `$tries`, `$backoff`, `ShouldBeUnique`. Retries are `release()` + `retryUntil()`.
- Don't raise `$timeout` to or above `retry_after` (660); don't lengthen `retryUntil()` past the base year.
- Don't pass Eloquent models, requests or raw `\Closure` into a job; don't pass `creatorId`/`userId` or call `setUser()` in `handle()`.
- Don't lazy-load relations in a job; don't let exceptions escape `handle()`; don't log without the `['exception' => $e]` context.
- Don't use `<Job>::dispatch()`, `dispatch_sync()` or call `->handle()` yourself (instant Excel export uses `ExcelExportJob::handleSync()`).
