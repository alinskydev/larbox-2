<?php

namespace App\Console\Commands\Larbox;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\StreamOutput;

class TestCommand extends Command
{
    protected $signature = 'larbox:test';

    protected $description = 'Testing';

    public function handle(): int
    {
        Artisan::call('optimize:clear');

        $stream = fopen('php://output', 'w');

        Artisan::call('larbox:migrate');
        $exitCode = Artisan::call('test', [], new StreamOutput($stream));

        return $exitCode;
    }
}
