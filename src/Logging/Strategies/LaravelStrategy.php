<?php

namespace Helpers\Logging\Strategies;

use Illuminate\Support\Facades\Log;
use Helpers\Logging\Contracts\LoggerStrategyInterface;
use Exception;

class LaravelStrategy implements LoggerStrategyInterface
{
    /**
     * Logs a message using Laravel's logger.
     *
     * @param string $message      The message to log.
     * @param array  $context      Additional context for the log.
     * @param bool   $clearBefore Whether to clear existing logs before writing (Not applicable for Laravel).
     *
     * @return bool True on success, false on failure.
     */
    public function log(string $message, array $context = [], bool $clearBefore = false): bool
    {
        try {
            // Laravel's logger does not support clearing logs directly.
            // You can implement custom logic here if needed.
            Log::info($message, $context);
            return true;
        } catch (Exception $e) {
            error_log("LaravelStrategy Error: " . $e->getMessage());
            return false;
        }
    }
}
