<?php

namespace App\Jobs;

use App\Base\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDBJob extends Job
{
    public function __construct(
        private string $conn,
        private string $command,
    ) {
        parent::__construct();
        $this->queue = 'maintenance';
    }

    public function handle(): void
    {
        try {
            DB::connection($this->conn)->statement($this->command);
        } catch (\Throwable $e) {
            $this->fail($e);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
