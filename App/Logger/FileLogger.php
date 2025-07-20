<?php

namespace App\Logger;

use App\Contracts\LoggerInterface;
use Throwable;

class FileLogger implements LoggerInterface
{
    private string $logFile;

    public function __construct(string $logFile = 'log.json')
    {
        $this->logFile = $logFile;
    }

    /**
     * Logs a simple message to the file in JSON format.
     *
     * @param string $message The message to log.
     */
    public function log(string $message): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'INFO',
            'message' => $message,
        ];

        file_put_contents($this->logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    }

    /**
     * Logs an API response to the file in JSON format.
     *
     * @param string $context Description of the API call.
     * @param int $statusCode The HTTP status code.
     * @param array $responseBody The decoded JSON response body.
     */
    public function logApiResponse(string $context, int $statusCode, array $responseBody): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'API_RESPONSE',
            'context' => $context,
            'status_code' => $statusCode,
            'response_body' => $responseBody,
        ];

        file_put_contents($this->logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    }

    /**
     * Logs an error to the file in JSON format.
     *
     * @param string $context Description of where the error occurred.
     * @param Throwable $e The exception object.
     * @param array|null $errorResponse Optional: decoded JSON error response body.
     */
    public function logError(string $context, Throwable $e, ?array $errorResponse = null): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'ERROR',
            'context' => $context,
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString()),
        ];

        if ($errorResponse !== null) { $logEntry['api_error_details'] = $errorResponse; }

        file_put_contents($this->logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    }
}