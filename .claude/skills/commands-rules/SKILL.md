---
name: commands-rules
description: Mandatory standards for artisan commands in app/Console/Commands (App/, Larbox/, Examples/, Deploy/), the scheduler and local-only command blocking in routes/console.php, and one-time deploy scripts (larbox:create_file_for_deploy / larbox:deploy). Use when creating or editing a console command, a scheduled task, or a one-time production data/schema change.
---

# Commands Rules

Location: `app/Console/Commands/<Group>/<Name>Command.php`, namespace `App\Console\Commands\<Group>`, extends `Illuminate\Console\Command`. Auto-discovered by Laravel (no registration). Commands exist only in `app/Console/Commands`, never in modules.
Canonical: `app/Console/Commands/App/ProcessDBDailyCommand.php`, `app/Console/Commands/Larbox/DeployCommand.php`, `app/Console/Commands/Larbox/CreateFileForDeployCommand.php`.

## Groups
- `App/` — project commands, prefix `app:`: `app:process_db_daily`, `app:remove_idle_db_connections` (scheduled), `app:run` (`RunCommand`, scratch "Run any script", calls `setUser(2)`).
- `Larbox/` — starter-kit tooling, prefix `larbox:`: `migrate`, `test`, `deploy`, `create_file_for_deploy`, `collect_translations`, `export_postman_collection`.
- `Examples/` — demo commands, prefix `examples:`; disabled by `$this->error('This command is disabled and cannot be executed.'); return;` as the first lines of `handle()`.
- `Deploy/` — not commands: one-time scripts created by `larbox:create_file_for_deploy`, run by `larbox:deploy` (directory is created on demand).

## Rules
- Signature `<group>:<snake_case_action>` matching the folder (`app:process_db_daily`, `examples:send_company_products_by_telegram`); class `<Action>Command` (`ProcessDBDailyCommand`, `RemoveIdleDBConnectionsCommand`).
- Properties `protected $signature = '...';` and `protected $description = '...';` (untyped, English). No arguments/options are used in the codebase.
- `public function handle(): void` (`int` only to return an exit code: `TestCommand`). Split steps into private methods (`ProcessDBDailyCommand::processDB()/removeFromFS()`, `DeployCommand::runDeployScripts()/sendMessageToTelegram()`).
- Output via `$this->info('Successfully done')` at the end of Larbox commands, `$this->error(...)` for refusals, `$this->alert(...)` for stage headers (`MigrateCommand`), `$this->ask(...)` for input (`ExportPostmanCollectionCommand`).
- Commands run without a user: call `setUser(<id>)` first when code relies on `user()` (`CollectTranslationsCommand` uses `setUser(1)`).
- Heavy or long DB work is dispatched as jobs, not run inline: `Bus::chain([new RemoveOldDataJob(...), new ProcessDBJob(...)])->dispatch()` on queue `maintenance` (see `jobs-rules`).
- Call other commands with `Artisan::call('<name>', [], new StreamOutput(fopen('php://output', 'w')))` to stream their output (`MigrateCommand`, `TestCommand`).

## Scheduler (routes/console.php)
- Format: `Schedule::command('<signature>')->cron('<expr>')->runInBackground()->withoutOverlapping()->onOneServer();`
- Current: `app:process_db_daily` at `1 0 * * *` (per-table chains: old rows removal, vacuum, reindex, `job_failed` older than 7 days, `system_daily_limit` truncate; deletes `storage/larbox/_tmp` files older than 1 day), `app:remove_idle_db_connections` at `1 * * * *` (terminates idle connections older than 10 min on `main`, `history`, `telescope`), `telescope:prune --hours=48` daily inside `if (App::environment('local'))`.

## Local-only commands (routes/console.php)
- Under `if (App::environment() !== 'local')` each destructive/dev command is overridden by `Artisan::command('<signature>', $callback)` that prints "Not allowed with non-local ENV...": `db:seed`, `db:wipe`, `migrate`, `migrate:fresh|refresh|reset|rollback`, `larbox:export_postman_collection`, `larbox:migrate`, `larbox:test`.
- A new command that wipes or rebuilds data must be added to this list. Never remove entries or change `APP_ENV` on servers.

## Larbox commands
- `larbox:migrate`: flushes `Cache::tags(['db_schema'])`, `migrate:fresh` over `modules/*/Database/Migrations/*.php`, then `(require $file)->run()` for `modules/*/Database/Seeders/*.php`, then `migrate` over `modules/*/Database/*.php` (relations).
- `larbox:test`: `optimize:clear` → `larbox:migrate` → `test`, returns the test exit code.
- `larbox:export_postman_collection`: deletes `storage/larbox/_postman.json`, runs `larbox:test`, builds `storage/larbox/<slug>_<Y_m_d__H_i_s>_collection.json`.
- `larbox:collect_translations`: collects `__('...')` strings from `app`, `http`, `integrations`, `modules` into `lang/<lang>.json` (skips `fields.`, `validation.`, `user_notifications.` keys) and FormRequest `attributes()` from `http` into `lang/<lang>/fields.php`; existing translations are kept.

## One-time deploy scripts
- Never put one-time production changes (data fixes, manual schema changes) into a command or migration: run `php artisan larbox:create_file_for_deploy` → `app/Console/Commands/Deploy/<Y_m_d__H_i_s>.php` returning an anonymous class with `run(): void`.
- `larbox:deploy` (after each deploy): for every `Deploy/*.php` not yet in `system_event` (`project = config('app.name')`, `type = EventTypeEnum::DEPLOY`, `additional->>'file'` = file name) runs `(require $file)->run()` and inserts the event inside `(new Model())->safelyDBProcess()`; then flushes `db_schema` tag, `system_languages`, `system_settings`, `system_public_data` tag, runs `optimize` and `queue:restart`, sends "✅ Successful deploy" to `TelegramHelper::devChats()`. On error: `optimize:clear` + "❌ Error in deploy" with trace.
- A script runs once per file name: to change data again, create a new file. A new cached singleton key must also be forgotten in `DeployCommand::handle()`.

## Template
```php
<?php

namespace App\Console\Commands\App;

use App\Jobs\ProcessDBJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class <Action>Command extends Command
{
    protected $signature = 'app:<snake_case_action>';

    protected $description = '<What it does>';

    public function handle(): void
    {
        $this->process();

        $this->info('Successfully done');
    }

    private function process(): void
    {
        Bus::chain([
            new ProcessDBJob(conn: 'main', command: 'vacuum <table>'),
        ])->dispatch();
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
- Don't edit or rename an already deployed `Deploy/*.php` script (it would re-run or never run); no own transaction is needed inside `run()` — `larbox:deploy` wraps it.
- Don't put business logic in `handle()` when a Helper/Service/Job exists for it.
