<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseService
{
    /**
     * Execute a callback within a database transaction.
     *
     * @param callable $callback
     * @param string|null $errorMessage
     * @param int $attempts
     * @return mixed
     * @throws Throwable
     */
    protected function executeInTransaction(callable $callback, ?string $errorMessage = null, int $attempts = 1)
    {
        try {
            return DB::transaction($callback, $attempts);
        } catch (Throwable $e) {
            if ($errorMessage) {
                Log::error($errorMessage, [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            throw $e;
        }
    }

    /**
     * Handle generic service errors with logging.
     *
     * @param Throwable $e
     * @param string $message
     * @return void
     * @throws Throwable
     */
    protected function handleException(Throwable $e, string $message = 'Service Error'): void
    {
        Log::error($message, [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        throw $e;
    }
}
