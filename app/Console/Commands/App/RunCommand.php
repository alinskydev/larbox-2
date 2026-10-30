<?php

namespace App\Console\Commands\App;

use Illuminate\Console\Command;
use Modules\Product\Models\Product;

class RunCommand extends Command
{
    protected $signature = 'app:run';

    protected $description = 'Run any script';

    public function handle(): void
    {
        setUser(2);

        Product::queryWithoutAutoFilters()
            ->getQuery()
            ->get();
    }
}
