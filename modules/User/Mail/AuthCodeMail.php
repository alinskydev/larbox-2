<?php

namespace Modules\User\Mail;

use App\Mail\Mail;

class AuthCodeMail extends Mail
{
    public function __construct(
        public string $code,
    ) {}

    public function build(): static
    {
        return $this->subject(__('Код подтверждения'))->view('auth_code');
    }
}
