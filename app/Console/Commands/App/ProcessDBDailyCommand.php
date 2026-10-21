<?php

namespace App\Console\Commands\App;

use App\Jobs\ProcessDBJob;
use App\Jobs\RemoveOldDataJob;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Laravel\SerializableClosure\SerializableClosure;
use Modules\User\Enums\NotificationTypeEnum;

class ProcessDBDailyCommand extends Command
{
    protected $signature = 'app:process_db_daily';

    protected $description = 'Remove old data and process DB daily command';

    public function handle(): void
    {
        $this->processDB();
        $this->removeFromFS();
    }

    private function processDB(): void
    {
        $severalDaysAgo = date('Y-m-d 00:00:00', strtotime('-7 days'));

        $chains = [

            // Main

            'audit_error_log' => [
                new RemoveOldDataJob(conn: 'main', table: 'audit_error_log', period: '-1 days'),
                new ProcessDBJob(conn: 'main', command: 'vacuum audit_error_log'),
                new ProcessDBJob(conn: 'main', command: 'reindex index concurrently audit_error_log_pkey'),
            ],
            'audit_record_action' => [
                new RemoveOldDataJob(conn: 'main', table: 'audit_record_action', period: '-1 months'),
                new ProcessDBJob(conn: 'main', command: 'vacuum audit_record_action'),
                new ProcessDBJob(conn: 'main', command: 'reindex index concurrently audit_record_action_pkey'),
            ],
            'job_failed' => [
                new ProcessDBJob(conn: 'main', command: "delete from job_failed where failed_at <= '$severalDaysAgo'"),
                new ProcessDBJob(conn: 'main', command: 'vacuum job_failed'),
            ],
            'system_daily_limit' => [
                new ProcessDBJob(conn: 'main', command: 'truncate table system_daily_limit restart identity'),
            ],
            'user_access_token' => [
                new RemoveOldDataJob(
                    conn: 'main',
                    table: 'user_access_token',
                    period: '0 days',
                    additionalQuery: new SerializableClosure(function (Builder $query) {
                        $query->where('expires_at', '<', date('Y-m-d 00:00:00'));
                    }),
                ),
            ],
            'user_notification' => [
                new RemoveOldDataJob(
                    conn: 'main',
                    table: 'user_notification',
                    period: '0 days',
                    additionalQuery: new SerializableClosure(function (Builder $query) {
                        $query->where('type', NotificationTypeEnum::EXCEL_EXPORT_COMPLETED->value);
                    }),
                ),
                new RemoveOldDataJob(conn: 'main', table: 'user_notification', period: '-1 months'),
                new ProcessDBJob(conn: 'main', command: 'vacuum user_notification'),
                new ProcessDBJob(conn: 'main', command: 'reindex index concurrently user_notification_pkey'),
            ],

            // History

            'history_store' => [
                new RemoveOldDataJob(conn: 'history', table: 'history_store', period: '-1 months'),
                new ProcessDBJob(conn: 'history', command: 'vacuum history_store'),
            ],
        ];

        foreach ($chains as $jobs) {
            Bus::chain($jobs)->dispatch();
        }
    }

    private function removeFromFS(): void
    {
        $paths = [
            storage_path('larbox/_tmp'),
        ];

        foreach ($paths as $path) {
            foreach (File::files($path) as $file) {
                if ($file->getMTime() < now()->subDays(1)->getTimestamp()) {
                    File::delete($file->getPathname());
                }
            }
        }
    }
}
