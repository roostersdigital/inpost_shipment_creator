<?php

namespace App\Contracts;

use Throwable;

interface LoggerInterface
{
    public function log(string $message): void;
    public function logApiResponse(string $context, int $statusCode, array $responseBody): void;
    public function logError(string $context, Throwable $e, ?array $errorResponse = null): void;
}