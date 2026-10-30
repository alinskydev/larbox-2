<?php

namespace App\Jobs;

use App\Base\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\SerializableClosure\SerializableClosure;

class RemoveOldDataJob extends Job
{
    public function __construct(
        private string $conn,
        private string $table,
        private string $period,
        private ?SerializableClosure $additionalQuery = null,
    ) {
        parent::__construct();
        $this->queue = 'maintenance';
    }

    public function handle(): void
    {
        try {
            $query = DB::connection($this->conn)
                ->table($this->table)
                ->where('created_at', '<', date('Y-m-d 00:00:00', strtotime($this->period)))
                ->when($this->additionalQuery, function ($query) {
                    $closure = $this->additionalQuery->getClosure();
                    $query->where($closure($query));
                });

            $query->delete();
        } catch (\Throwable $e) {
            $this->fail($e);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
