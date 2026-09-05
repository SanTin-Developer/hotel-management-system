<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginException extends HttpException
{
    private array $errors;

    public function __construct(
        int $statusCode,
        string $message,
        array $errors,
        ?\Throwable $previous = null
    ) {
        parent::__construct($statusCode, $message, $previous, [], 0);

        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
