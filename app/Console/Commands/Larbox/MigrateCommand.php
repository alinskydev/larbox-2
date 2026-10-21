<?php

namespace App\Console\Commands\Larbox;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\StreamOutput;

class MigrateCommand extends Command
{
    protected $signature = 'larbox:migrate';

    protected $description = 'Migrate base';

    public function handle(): void
    {
        Cache::tags(['db_schema'])->flush();

        $stream = fopen('php://output', 'w');

        $this->alert('Migrations');

        app()->register(DBProjectStructureServiceProvider::class);
        Artisan::call('migrate:fresh', [], new StreamOutput($stream));

        $this->alert('Seeders');

        $files = glob(base_path('modules/*/Database/Seeders/*.php'));
        foreach ($files as $file)(require $file)->run();

        $this->alert('Relations');

        app()->register(DBProjectRelationsServiceProvider::class);
        Artisan::call('migrate', [], new StreamOutput($stream));

        $this->info('Successfully done');
    }
}

class DBServiceProvider extends ServiceProvider
{
    protected string $path;

    public function boot(): void
    {
        $files = glob(base_path($this->path));
        $this->loadMigrationsFrom($files);
    }
}

class DBProjectStructureServiceProvider extends DBServiceProvider
{
    protected string $path = 'modules/*/Database/Migrations/*.php';
}

class DBProjectRelationsServiceProvider extends DBServiceProvider
{
    protected string $path = 'modules/*/Database/*.php';
}
