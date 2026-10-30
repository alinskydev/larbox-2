<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry) {
            if (App::environment('local')) return true;
            if (App::environment('testing')) return true;

            return $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->isSlowQuery() ||
                $entry->hasMonitoredTag();
        });

        Telescope::afterRecording(function (Telescope $telescope, IncomingEntry $entry) {
            if ($entry->type !== EntryType::COMMAND) return;

            $duration = defined('LARAVEL_START') ? floor((microtime(true) - LARAVEL_START) * 1000) : 0;
            $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 1);

            $entry->content['options']['duration'] = "$duration ms";
            $entry->content['options']['memory_usage'] = "$memoryUsage MB";
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if (App::environment('local')) return;

        Telescope::hideResponseParameters(['token']);

        Telescope::hideRequestHeaders([
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Configure the Telescope authorization services.
     *
     * @return void
     */
    protected function authorization()
    {
        $this->gate();

        Telescope::auth(function ($request) {
            if (App::environment('local')) return true;

            $systemKey = (string)(config('telescope.secret_key'));
            $userKey = (string)($_COOKIE['telescope_secret_key'] ?? null);

            if ($systemKey !== '' && hash_equals($systemKey, $userKey)) {
                return true;
            }

            throw new NotFoundHttpException();
        });
    }
}
