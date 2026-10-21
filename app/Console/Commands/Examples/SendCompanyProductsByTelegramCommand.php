<?php

namespace App\Console\Commands\Examples;

use Illuminate\Console\Command;
use Modules\Company\Models\Company;
use Modules\Product\Jobs\SendCompanyProductsByTelegramJob;

class SendCompanyProductsByTelegramCommand extends Command
{
    protected $signature = 'examples:send_company_products_by_telegram';

    protected $description = 'Send company products by Telegram';

    public function handle(): void
    {
        $this->error('This command is disabled and cannot be executed.');
        return;

        Company::query()
            ->leftJoin('company_credential', 'company_credential.company_id', '=', 'company.id')
            ->select(['company.id'])
            ->whereRaw("jsonb_array_length(company_credential.telegram_chats) > 0")
            ->withoutTrashed()
            ->getQuery()
            ->get()
            ->each(function ($company) {
                dispatch(new SendCompanyProductsByTelegramJob($company->id));
            });
    }
}
