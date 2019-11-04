<?php

namespace SMIT\SDK\Exceptions;

use Exception;

class UnauthorizedScopeException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
