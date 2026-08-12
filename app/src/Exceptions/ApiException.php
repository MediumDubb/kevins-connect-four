<?php

namespace MediumDubb\ConnectFour\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected string $errorType;

    public function __construct(string $errorType, string $message, int $statusCode = 400)
    {
        parent::__construct($message, $statusCode);
        $this->errorType = $errorType;
    }

    public function getErrorType(): string {
        return $this->errorType;
    }
}