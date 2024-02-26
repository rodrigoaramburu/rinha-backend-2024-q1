<?php

declare(strict_types=1);

namespace Rinha\Exception;

class HttpException extends \Exception
{
    public function __construct(int $code, string $message)
    {
        parent::__construct($message, $code);
    }
}
