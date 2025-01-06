<?php

namespace Helpers\Logging\Contracts;

interface LoggerStrategyInterface
{
    /**
     * Logs a message with the given context.
     *
     * @param string $message      The message to log.
     * @param array  $context      Additional context for the log.
     * @param bool   $clearBefore Whether to clear existing logs before writing.
     *
     * @return bool True on success, false on failure.
     */
    public function log(string $message, array $context = [], bool $clearBefore = false): bool;
}
