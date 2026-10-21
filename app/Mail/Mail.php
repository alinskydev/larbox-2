<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class Mail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function view($view, array $data = []): static
    {
        $calledClass = get_called_class();
        $calledClassReflection = new \ReflectionClass($calledClass);
        $calledClassFile = $calledClassReflection->getFileName();
        $calledClassDir = dirname($calledClassFile);

        $viewNamespace = 'mail_' . md5("$calledClass|$view");

        View::replaceNamespace($viewNamespace, $calledClassDir);
        $view = "$viewNamespace::$view." . app()->getLocale();

        return parent::view($view, $data);
    }
}
