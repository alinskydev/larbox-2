---
name: jobs-rules
description: Mandatory standards for queued jobs in app/Jobs, modules/*/Jobs, integrations/*/Jobs and feature jobs (app/Mail, app/Excel), queue names and _bats queue workers. Use when creating or editing a job, dispatching one, or adding a queue.
---

# Jobs Rules

Jobs live in `app/Jobs/` (maintenance), `modules/<Module>/Jobs/` (domain), `integrations/<Name>/Jobs/` (transport) and next to their feature (`app/Mail/SendMailJob.php`, `app/Excel/Import/ExcelImportJob.php`, `modules/<Module>/Excel/Export/Jobs/`). All extend `App\Base\Job`.
Canonical: `modules/Product/Jobs/SendCompanyProductsByTelegramJob.php`, `modules/User/Jobs/CreateNotificationsJob.php`, `app/Jobs/ProcessDBJob.php`.

## Base class (`App\Base\Job`)
- `ShouldQueue` + `Dispatchable`, `InteractsWithQueue`, `Queueable`; `$timeout = 600`, `$failOnTimeout = false` (timed-out job is retried after `retry_after` = 660 in `config/queue.php`); `retryUntil()` = `now()->addYear()`.
- Constructor captures `protected ?int $userId = user()?->id` and `protected ?string $locale = app()->getLocale()`.
- `final middleware()` runs `setUser($userId, true)` + `app()->setLocale($locale)` before `handle()` and `setUser(null)` after. Inside `handle()` `user()` is the dispatcher and `$this->userId` / `$this->locale` are available (`CreateNotificationsJob`: `creatorId: $this->userId`, `ExcelExportJob`: `recipientIds: [$this->userId]`, `SendMailJob`: `->locale($this->locale)`).
- `final failed()` is empty on purpose: Laravel may call it without user/locale. All failure handling goes into the `catch` of `handle()`.
- A job built without a current user (console, scheduler) runs with `user() === null`, so model `$autoFilters` are skipped.

## Rules
- Extend `App\Base\Job` (Excel exports: `App\Excel\Export\ExcelExportJob`, see `excel-rules`). Never implement `ShouldQueue` or use the queue traits directly.
- Name `<Verb><Object>Job`: `CreateNotificationsJob`, `PrepareAndCreateNotificationsJob`, `SendCompanyProductsByTelegramJob`, `RemoveOldDataJob`, `ProcessDBJob`. One job = one `handle()` action.
- Constructor: promoted properties with trailing comma, `private` by default (`app/Jobs/*`, `SendCompanyProductsByTelegramJob`, `TelegramJob`); `modules/User/Jobs/*` and abstract `ExcelExportJob` use `protected` — match the file. Body is always `parent::__construct();` then `$this->queue = '<name>';`.
- Never pass the user/creator id — it is captured by the base constructor.
- Pass scalars, id arrays and plain arrays; reload models inside the job through private query methods (`SendCompanyProductsByTelegramJob::company()` / `products()`). Allowed objects: a mailable (`SendMailJob::$mail`) and closures wrapped in `Laravel\SerializableClosure\SerializableClosure` (`TelegramJob::$callback`, `RemoveOldDataJob::$additionalQuery`).
- Queues in code: `excel` (Excel import/export), `mail` (`SendMailJob`), `maintenance` (`ProcessDBJob`, `RemoveOldDataJob`), `notification` (User notifications), `telegram` (`TelegramJob`, Telegram mailings). Reuse one before adding a new queue; a new queue needs `_bats/queue - <name>.bat`.
- Override `retryUntil(): Carbon` (`Illuminate\Support\Carbon`) only to shorten: `now()->addDay()` for external delivery (Telegram, mail), `now()->addMonth()` for notifications. Maintenance and Excel jobs keep the base.
- `handle(): void` wraps the whole body in `try { ... } catch (\Throwable $e) { ... }`. In the catch first decide, then log:
  - `$this->release(60 * 10);` for transient/external failures (notifications, mail, Telegram);
  - `$this->fail($e);` for deterministic failures (DB statements, Excel import/export, Telegram 4xx except 429 via `TelegramJob::isClientError()`);
  - then `Log::error($e->getMessage(), ['exception' => $e]);`;
  - user-facing failure side effects follow in the same catch (`ExcelImportJob` / `ExcelExportJob` insert a failure notification).
- Keep `handle()` thin: delegate to a Helper / Template / Service with named arguments (`NotificationHelper::insertMultiple(...)`, `ProductMailingTelegramTemplate::process(...)`).
- Fan out large sets into child jobs of 1000: `chunkById(1000, ...)` in `PrepareAndCreateNotificationsJob`, `array_chunk(..., 1000)` + `isLast` flag in `ProductControllerExcelImportTrait::excelImportProcess()`.

## Dispatching
- Always `dispatch(new <Name>Job(...));`. Named arguments when there is more than one param (`new CreateNotificationsJob(userIds: ..., type: ..., text: ...)`); a single param may be positional (`new SendCompanyProductsByTelegramJob($company->id)`).
- Sequential maintenance steps: `Bus::chain([...])->dispatch()` (`ProcessDBDailyCommand::processDB()`).
- Use the feature wrappers instead of dispatching their jobs yourself: `MailHelper::send()` (`SendMailJob`), `TelegramHelper::sendMessage()/sendFile()/sendGroupedFiles()` (`TelegramJob`), `ExcelExportHelper::process()` (instant `handleSync()` below `$instantLimit`, else dispatch + `abort(202)`).
- `QUEUE_CONNECTION=sync` in `phpunit.xml` and `.env.example`: jobs run inline in tests/dev. Driver `database`: table `job_active`, failed jobs `job_failed` (purged after 7 days by `app:process_db_daily`).

## Queue workers (_bats)
- `_bats/queue - <name>.bat`: `cd ../` + `php artisan queue:work --queue=<name>`; `queue - default.bat` runs plain `queue:work`.
- Existing bats: default, excel, mail, maintenance, notification, recalculation, telegram, tmp (`recalculation`, `tmp`, `default` have no jobs in code).

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
- Don't let exceptions escape `handle()`; don't log without the `['exception' => $e]` context.
- Don't use `<Job>::dispatch()`, `dispatch_sync()` or call `->handle()` yourself (instant Excel export uses `ExcelExportJob::handleSync()`).
