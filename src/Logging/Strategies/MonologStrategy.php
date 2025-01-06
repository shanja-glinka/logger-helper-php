<?php

namespace Helpers\Logging\Strategies;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Helpers\Logging\Contracts\LoggerStrategyInterface;
use Exception;

class MonologStrategy implements LoggerStrategyInterface
{
    private Logger $logger;
    private string $logFile;

    /**
     * MonologStrategy constructor.
     *
     * @param string $logFile The file path where logs will be written.
     * @param string $channel The channel name for Monolog.
     * @param int    $level   The minimum logging level.
     */
    public function __construct(string $logFile, string $channel = 'app', int $level = Logger::DEBUG)
    {
        $this->logFile = $logFile;

        if (!file_exists($this->logFile)) {
            // Ensure the log directory exists
            $logDirectory = dirname($this->logFile);
            if (!is_dir($logDirectory) && !mkdir($logDirectory, 0755, true)) {
                throw new Exception("Failed to create log directory: {$logDirectory}");
            }
        }

        $this->logger = new Logger($channel);

        $handler = new StreamHandler($this->logFile, $level);
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);

        $this->logger->pushHandler($handler);
    }

    /**
     * Logs a message using Monolog.
     *
     * @param string $message      The message to log.
     * @param array  $context      Additional context for the log.
     * @param bool   $clearBefore Whether to clear existing logs before writing.
     *
     * @return bool True on success, false on failure.
     */
    public function log(string $message, array $context = [], bool $clearBefore = false): bool
    {
        try {
            if ($clearBefore && file_exists($this->logFile)) {
                if (!unlink($this->logFile)) {
                    throw new Exception("Failed to clear log file: {$this->logFile}");
                }
                // Reinitialize the handler after clearing the file
                $this->logger->popHandler();
                $handler = new StreamHandler($this->logFile, Logger::DEBUG);
                $formatter = new LineFormatter(null, null, true, true);
                $handler->setFormatter($formatter);
                $this->logger->pushHandler($handler);
            }

            $this->logger->info($message, $context);
            return true;
        } catch (Exception $e) {
            error_log("MonologStrategy Error: " . $e->getMessage());
            return false;
        }
    }
}
