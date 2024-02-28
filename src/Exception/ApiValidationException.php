<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiValidationException extends HttpException
{
    private array $errors;

    public function __construct(array $errors, int $statusCode = 422, \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct($statusCode, 'Validation Failed', $previous, $headers, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
