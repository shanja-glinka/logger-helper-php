<?php

namespace Helpers\Logging;

use Helpers\Logging\Contracts\LoggerStrategyInterface;
use Helpers\Logging\Strategies\FileLoggerStrategy;
use RuntimeException;

/**
 * Enum for log types
 */
enum LogType: int
{
    case PRINT = 0;
    case DUMP = 1;
    case EXPORT = 2;
}

/**
 * LoggerHelper class implementing Singleton and Strategy patterns.
 */
class LoggerHelper
{
    private static ?LoggerHelper $instance = null;
    private ?LoggerStrategyInterface $strategy = null;
    private string $moduleName = 'unknown';
    private string $topic = '';
    private string $logDirectory;
    private LogType $logType = LogType::PRINT;

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @param string      $topic        The topic name for the log.
     * @param string      $module       The module name.
     * @param string|null $logDirectory The path to the log directory. If null, uses DOCUMENT_ROOT/logs.
     */
    private function __construct(string $topic = '', string $module = '', ?string $logDirectory = null)
    {
        $this->topic = $topic;
        $this->moduleName = $module;
        $this->logDirectory = $logDirectory ?? ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) . '/logs';
    }

    /**
     * Retrieves the singleton instance of LoggerHelper.
     *
     * @param string      $topic        The topic name for the log.
     * @param string      $module       The module name.
     * @param string|null $logDirectory The path to the log directory. If null, uses DOCUMENT_ROOT/logs.
     *
     * @return LoggerHelper The singleton instance.
     */
    public static function getInstance(string $topic = '', string $module = '', ?string $logDirectory = null): LoggerHelper
    {
        if (self::$instance === null) {
            self::$instance = new LoggerHelper($topic, $module, $logDirectory);
        }
        return self::$instance;
    }

    /**
     * Sets the logging strategy.
     *
     * @param LoggerStrategyInterface $strategy The logging strategy to use.
     *
     * @return void
     */
    public function setStrategy(LoggerStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * Sets the log type.
     *
     * @param LogType $logType The type of log formatting.
     *
     * @return void
     */
    public function setLogType(LogType $logType): void
    {
        $this->logType = $logType;
    }

    /**
     * Logs a message using the selected strategy.
     *
     * @param mixed $message      The message to log.
     * @param bool  $clearBefore Whether to clear the log file before writing (applicable only for FileLoggerStrategy).
     *
     * @return bool True on success, false on failure.
     */
    public function log(mixed $message, bool $clearBefore = false): bool
    {
        if ($this->strategy === null) {
            throw new RuntimeException("Logging strategy is not set.");
        }

        // Transform the message based on log type
        $transformedMessage = $this->transformLogMessage($message);

        // Add trace information
        $trace = $this->moduleName;
        if (empty($trace)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[1] ?? null;
            if ($caller) {
                $trace = "{$caller['file']}:{$caller['line']}";
            } else {
                $trace = 'unknown';
            }
        }

        // Prepare the final log message
        $finalMessage = "<{$trace}> {$transformedMessage}";

        // If using FileLoggerStrategy and clearBefore is true, perform specific actions
        if ($this->strategy instanceof FileLoggerStrategy && $clearBefore) {
            // Optionally, log that the log file is being cleared
            // This can help in debugging to know when logs are cleared
            $this->strategy->log("Log file is being cleared.", ['topic' => $this->topic], true);
        }

        // Log the message using the strategy, passing the clearBefore flag
        return $this->strategy->log($finalMessage, ['topic' => $this->topic], $clearBefore);
    }

    /**
     * Transforms the log message based on the log type.
     *
     * @param mixed $message The message to transform.
     *
     * @return string The transformed message.
     */
    private function transformLogMessage(mixed $message): string
    {
        return match ($this->logType) {
            LogType::PRINT => is_string($message) ? $message : print_r($message, true),
            LogType::DUMP => var_export($message, true),
            LogType::EXPORT => $this->varExportPretty($message),
        };
    }

    /**
     * Formats var_export output nicely.
     *
     * @param mixed $expression The expression to export.
     *
     * @return string The nicely formatted export.
     */
    private function varExportPretty(mixed $expression): string
    {
        $export = var_export($expression, true);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $lines = preg_split("/\r\n|\n|\r/", $export);
        $lines = preg_replace([
            "/\s*array\s\($/",
            "/\)(,)?$/",
            "/\s=>\s$/"
        ], [
            '',
            ']$1',
            ' => ['
        ], $lines);
        $export = "[\n" . implode(PHP_EOL, array_filter(['['] + $lines)) . "\n]";
        return $export;
    }

    /**
     * Changes the topic of the logger and updates the file name accordingly for file-based logging.
     *
     * @param string $topic The new topic name.
     *
     * @return void
     */
    public function setTopic(string $topic): void
    {
        $this->topic = $topic;

        // If using FileLoggerStrategy, update the file name
        if ($this->strategy instanceof FileLoggerStrategy) {
            $newFileName = rtrim($this->logDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "log_{$this->topic}.txt";
            $this->strategy = new FileLoggerStrategy($newFileName, $this->moduleName, $this->logType->value);
        }
    }
}
