<?php

namespace App\Mail;

class MailHelper
{
    public static function send(Mail $mail, string|array $to): void
    {
        dispatch(new SendMailJob(
            mail: $mail,
            to: $to,
        ));
    }
}
