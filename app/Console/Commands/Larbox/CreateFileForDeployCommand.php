<?php

namespace App\Console\Commands\Larbox;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateFileForDeployCommand extends Command
{
    protected $signature = 'larbox:create_file_for_deploy';

    protected $description = 'Create file for deploy request';

    public function handle(): void
    {
        $path = base_path('app/Console/Commands/Deploy');
        File::makeDirectory($path, 0777, true, true);

        file_put_contents(
            "$path/" . date('Y_m_d__H_i_s') . '.php',
            implode("\n", [
                '<?php',
                '',
                'use App\Helpers\Database\DatabaseSeederHelper;',
                'use Illuminate\Database\Schema\Blueprint;',
                'use Illuminate\Support\Facades\DB;',
                'use Illuminate\Support\Facades\Schema;',
                '',
                'return new class',
                '{',
                '    public function run(): void',
                '    {',
                '    }',
                '};',
            ]),
        );

        $this->info('Successfully done');
    }
}
