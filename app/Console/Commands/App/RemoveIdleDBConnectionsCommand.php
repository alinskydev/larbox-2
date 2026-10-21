<?php

namespace App\Console\Commands\App;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveIdleDBConnectionsCommand extends Command
{
    protected $signature = 'app:remove_idle_db_connections';

    protected $description = 'Remove idle DB connections';

    public function handle(): void
    {
        $datetime = date('Y-m-d H:i:s', strtotime('-10 minutes'));

        $databases = array_map(
            fn($connection) => config("database.connections.$connection.database"),
            ['main', 'history', 'telescope'],
        );

        DB::table('pg_stat_activity')
            ->selectRaw('pg_terminate_backend(pid)')
            ->whereIn('state', ['idle', 'idle in transaction'])
            ->where('application_name', '')
            ->where('query_start', '<', $datetime)
            ->whereIn('datname', $databases)
            ->get();
    }
}
