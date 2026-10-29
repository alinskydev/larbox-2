---
name: commands-rules
description: Mandatory standards for artisan commands in app/Console/Commands (App/, Larbox/, Examples/, Deploy/), the scheduler and local-only command blocking in routes/console.php, and one-time deploy scripts (larbox:create_file_for_deploy / larbox:deploy). Use when creating or editing a console command, a scheduled task, or a one-time production data/schema change.
---

# Commands Rules

Location: `app/Console/Commands/<Group>/<Name>Command.php`, namespace `App\Console\Commands\<Group>`, extends `Illuminate\Console\Command`. Auto-discovered by Laravel (no registration; `bootstrap/app.php` only loads `routes/console.php`). Commands exist only in `app/Console/Commands`, never in modules.
Canonical: `app/Console/Commands/App/ProcessDBDailyCommand.php`, `app/Console/Commands/Larbox/DeployCommand.php`, `app/Console/Commands/Larbox/CreateFileForDeployCommand.php`.

## Groups
- `App/` — project commands, prefix `app:`: `app:process_db_daily`, `app:remove_idle_db_connections` (scheduled), `app:run` (`RunCommand`, scratch "Run any script", body is `setUser(2);`).
- `Larbox/` — starter-kit tooling, prefix `larbox:`: `migrate`, `test`, `deploy`, `create_file_for_deploy`, `collect_translations`, `export_postman_collection`.
- `Examples/` — demo commands, prefix `examples:` (`send_company_products_by_telegram`, `test_file_upload_security`); disabled by `$this->error('This command is disabled and cannot be executed.'); return;` as the first lines of `handle()`.
- `Deploy/` — not commands: one-time scripts created by `larbox:create_file_for_deploy`, run by `larbox:deploy` (directory is created on demand, absent now).

## Rules
- Signature `<group>:<snake_case_action>` matching the folder (`app:process_db_daily`, `examples:send_company_products_by_telegram`); class `<Action>Command` (`ProcessDBDailyCommand`, `RemoveIdleDBConnectionsCommand`).
- Properties `protected $signature = '...';` and `protected $description = '...';` (untyped, English). No arguments/options are used in the codebase.
- `public function handle(): void`; `int` only to return an exit code (`TestCommand` returns the `test` exit code, `DeployCommand` returns `self::SUCCESS` / `self::FAILURE`). Split steps into private methods (`ProcessDBDailyCommand::processDB()/removeFromFS()`, `DeployCommand::runDeployScripts()/sendMessageToTelegram()`).
- Output via `$this->info('Successfully done')` at the end of Larbox commands, `$this->error(...)` for refusals, `$this->alert(...)` for stage headers (`MigrateCommand`), `$this->ask(...)` for input (`ExportPostmanCollectionCommand`).
- Commands run without a user (`$autoFilters` skipped, dispatched jobs get `userId = null`): call `setUser(<id>)` first when code relies on `user()` (`CollectTranslationsCommand` uses `setUser(1)`).
- Heavy or long DB work is dispatched as jobs, not run inline: an array of chains keyed by table, each `Bus::chain($jobs)->dispatch()` on queue `maintenance` (see `jobs-rules`).
- Call other commands with `Artisan::call('<name>')`; pass `[], new StreamOutput(fopen('php://output', 'w'))` only when their output must be shown (`MigrateCommand`, `TestCommand` for `test`, `ExportPostmanCollectionCommand`).

## Scheduler (routes/console.php)
- Format: `Schedule::command('<signature>')->cron('<expr>')->runInBackground()->withoutOverlapping()->onOneServer();`
- Current: `app:process_db_daily` at `1 0 * * *`, `app:remove_idle_db_connections` at `1 * * * *`, `telescope:prune --hours=48` `->daily()->withoutOverlapping()->onOneServer()` inside `if (App::environment('local'))`.
- `app:process_db_daily`: per-table chains of `RemoveOldDataJob` / `ProcessDBJob` (`vacuum`, `reindex index concurrently <table>_pkey`) — `audit_error_log` (1 day), `audit_record_action` (1 month), `job_failed` (7 days), `system_daily_limit` (truncate), `user_access_token` (expired), `user_notification` (`EXCEL_EXPORT_COMPLETED` at once, others after 1 month), `history_store` on `history` (1 month); then deletes `storage/larbox/_tmp` files older than 1 day.
- `app:remove_idle_db_connections`: `pg_terminate_backend` for `idle` / `idle in transaction` connections with empty `application_name`, older than 10 min, on the `main`, `history`, `telescope` databases.

## Local-only commands (routes/console.php)
- Under `if (App::environment() !== 'local')` each destructive/dev command is overridden by `Artisan::command('<signature>', $callback)` printing "Not allowed with non-local ENV...": `db:seed`, `db:wipe`, `migrate`, `migrate:fresh|refresh|reset|rollback`, `larbox:export_postman_collection`, `larbox:migrate`, `larbox:test`.
- A new command that wipes or rebuilds data must be added to this list. Never remove entries or change `APP_ENV` on servers.

## Larbox commands
- `larbox:migrate`: flushes `Cache::tags(['db_schema'])`; registers the file-local `DBProjectStructureServiceProvider` and runs `migrate:fresh` (`database/migrations` + `modules/*/Database/Migrations/*.php`); `(require $file)->run()` for `modules/*/Database/Seeders/*.php`; registers `DBProjectRelationsServiceProvider` and runs `migrate` over `modules/*/Database/*.php` (relations).
- `larbox:test`: `optimize:clear` → `larbox:migrate` → `test` (streamed), returns the test exit code.
- `larbox:export_postman_collection`: asks a name, deletes `storage/larbox/_postman.json`, runs `larbox:test` (continues on exit code 0/1), builds `storage/larbox/<slug>_<Y_m_d__H_i_s>_collection.json` (Postman v2.1, `{{host}}` variable).
- `larbox:collect_translations`: collects `__('...')` / `__("...")` strings from `app`, `http`, `integrations`, `modules` into `lang/<lang>.json` (skips `fields.`, `validation.`, `user_notifications.` keys) and `attributes()` of `*Request` classes in `http` into `lang/<lang>/fields.php`; languages from `config('larbox.languages')`; existing translations are kept.

## One-time deploy scripts
- Never put one-time production changes (data fixes, manual schema changes) into a command or migration: run `php artisan larbox:create_file_for_deploy` → `app/Console/Commands/Deploy/<Y_m_d__H_i_s>.php` returning an anonymous class with `run(): void`.
- `larbox:deploy` (after each deploy): inside one `(new Model())->safelyDBProcess()` runs `(require $file)->run()` for every `Deploy/*.php` not yet in `system_event` (`project = config('app.name')`, `type = EventTypeEnum::DEPLOY`, `additional->>'file'` = file name) and inserts the event; then flushes `db_schema` tag, `system_languages`, `system_settings`, `system_public_data` tag, runs `optimize` and `queue:restart`, sends "✅ Successful deploy" via `TelegramHelper::sendMessage()` to `devChats()`. On error: `optimize:clear` + "❌ Error in deploy" with message and trace.
- A script runs once per file name: to change data again, create a new file. A new cached singleton key must also be forgotten in `DeployCommand::handle()`.

## Template
```php
<?php

namespace App\Console\Commands\App;

use App\Jobs\ProcessDBJob;
use App\Jobs\RemoveOldDataJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class <Action>Command extends Command
{
    protected $signature = 'app:<snake_case_action>';

    protected $description = '<What it does>';

    public function handle(): void
    {
        $this->processDB();
    }

    private function processDB(): void
    {
        $chains = [
            '<table>' => [
                new RemoveOldDataJob(conn: 'main', table: '<table>', period: '-1 months'),
                new ProcessDBJob(conn: 'main', command: 'vacuum <table>'),
            ],
        ];

        foreach ($chains as $jobs) {
            Bus::chain($jobs)->dispatch();
        }
    }
}
```

Deploy script (generated by `larbox:create_file_for_deploy`):
```php
<?php

use App\Helpers\Database\DatabaseSeederHelper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class
{
    public function run(): void
    {
    }
};
```

## Don't
- Don't create commands in `modules/`, `http/` or `integrations/`; don't register them manually.
- Don't run `migrate*`, `db:seed`, `db:wipe`, `larbox:migrate`, `larbox:test` outside local, and don't bypass the `routes/console.php` block.
- Don't edit or rename an already deployed `Deploy/*.php` script (it would re-run or never run). No own transaction inside `run()` — all pending scripts share the `larbox:deploy` transaction, so no `vacuum` / `... concurrently` there (dispatch `ProcessDBJob` instead).
- Don't put business logic in `handle()` when a Helper/Service/Job exists for it.
