<?php

namespace Modules\Product\Jobs;

use App\Base\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Company\Models\Company;
use Modules\Product\Models\Product;
use Modules\Product\Telegram\Templates\ProductMailingTelegramTemplate;

class SendCompanyProductsByTelegramJob extends Job
{
    public function __construct(
        private int $companyId,
    ) {
        parent::__construct();
        $this->queue = 'telegram';
    }

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function handle(): void
    {
        try {
            ProductMailingTelegramTemplate::process(
                company: $this->company(),
                products: $this->products(),
            );
        } catch (\Throwable $e) {
            $this->release(60 * 10);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }

    private function company(): Company
    {
        return Company::query()->findOrFail($this->companyId);
    }

    private function products(): array
    {
        return Product::query()
            ->with([
                'company:id,name',
                'category:id,name',
                'brand:id,name',
            ])
            ->where('company_id', $this->companyId)
            ->whereBetween('created_at', [
                date('Y-m-d 00:00:00'),
                date('Y-m-d 23:59:59'),
            ])
            ->getQueryWithRelations()
            ->get()
            ->toArray();
    }
}
