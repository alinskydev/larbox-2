<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;

abstract class BusinessException extends \Exception implements ShouldntReport
{
    public function __construct(public string $error, array $replace = [])
    {
        $messages = $this->messages();
        $message = __($messages[$error], $replace);

        parent::__construct($message);
    }

    abstract protected function messages(): array;
}
