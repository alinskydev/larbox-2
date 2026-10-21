<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

if (App::environment('local')) {
    Schedule::command('telescope:prune --hours=48')->daily()->withoutOverlapping()->onOneServer();
}

Schedule::command('app:process_db_daily')->cron('1 0 * * *')->runInBackground()->withoutOverlapping()->onOneServer();

Schedule::command('app:remove_idle_db_connections')->cron('1 * * * *')->runInBackground()->withoutOverlapping()->onOneServer();

if (App::environment() !== 'local') {
    $callback = function () {
        $this->error('Not allowed with non-local ENV. Never launch this command on staging or even on production server. Use "php artisan larbox:deploy" instead. You can find information about it in "README.md".');
    };

    Artisan::command('db:seed', $callback);
    Artisan::command('db:wipe', $callback);

    Artisan::command('migrate', $callback);
    Artisan::command('migrate:fresh', $callback);
    Artisan::command('migrate:refresh', $callback);
    Artisan::command('migrate:reset', $callback);
    Artisan::command('migrate:rollback', $callback);

    Artisan::command('larbox:export_postman_collection', $callback);
    Artisan::command('larbox:migrate', $callback);
    Artisan::command('larbox:test', $callback);
}
