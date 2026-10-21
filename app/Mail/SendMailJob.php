<?php

namespace App\Mail;

use App\Base\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail as MailFacade;

class SendMailJob extends Job
{
    public function __construct(
        private Mail $mail,
        private string|array $to,
    ) {
        parent::__construct();
        $this->queue = 'mail';
    }

    public function retryUntil(): Carbon
    {
        return now()->addDay();
    }

    public function handle(): void
    {
        try {
            MailFacade::to($this->to)->locale($this->locale)->send($this->mail);
        } catch (\Throwable $e) {
            $this->release(60 * 10);
            Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
