<?php

namespace Backend\Helpers;

use Exception;
use RuntimeException;
use DateTimeImmutable;
use DateTimeInterface;

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
 * LoggerHelper class for logging messages
 */
class LoggerHelper
{
    private string $moduleName;
    private string $fileName;
    private LogType $logAs;

    /**
     * LoggerHelper constructor.
     *
     * @param string      $topic        The topic name for the log.
     * @param string      $module       The module name.
     * @param string|null $logDirectory The path to the log directory. If null, uses DOCUMENT_ROOT/logs.
     */
    public function __construct(
        string $topic = '',
        string $module = '',
        ?string $logDirectory = null
    ) {
        $logDirectory = $logDirectory ?? ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) . '/logs';
        $this->fileName = rtrim($logDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "log_{$topic}.txt";
        $this->moduleName = $module;
        $this->logAs = LogType::PRINT;
    }

    /**
     * Sets the log type.
     *
     * @param LogType $logType The type of logging.
     */
    public function setLogType(LogType $logType): void
    {
        $this->logAs = $logType;
    }

    /**
     * Writes a message to the log.
     *
     * @param mixed $message      The message to write.
     * @param bool  $clearBefore Whether to clear the log file before writing.
     *
     * @return bool True on success, false on failure.
     */
    public function writeToLog(mixed $message, bool $clearBefore = false): bool
    {
        try {
            if ($clearBefore && file_exists($this->fileName)) {
                if (!unlink($this->fileName)) {
                    throw new RuntimeException("Failed to delete log file: {$this->fileName}");
                }
            }

            $logDirectory = dirname($this->fileName);
            if (!is_dir($logDirectory) && !mkdir($logDirectory, 0755, true)) {
                throw new RuntimeException("Failed to create log directory: {$logDirectory}");
            }

            $currentTime = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $formattedTime = $currentTime->format('d.m.y H:i:s');

            $timer = file_exists($this->fileName) ? filemtime($this->fileName) : 0;
            $timerDelay = time() - $timer;

            $logEntries = [];

            if ($timerDelay >= 10) {
                $timerInfo = $timerDelay <= 120 ? " +{$timerDelay}" : '';
                $logEntries[] = "- - - - - [{$formattedTime}{$timerInfo}] - - - - -";
            }

            $transformedMessage = $this->transformLogMessage($message);

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

            $logEntries[] = "<{$trace}> {$transformedMessage}";

            $logContent = implode(PHP_EOL, $logEntries) . PHP_EOL;

            // Open the file in append binary mode with locking
            $fileHandle = fopen($this->fileName, 'ab');
            if (!$fileHandle) {
                throw new RuntimeException("Failed to open log file: {$this->fileName}");
            }

            if (flock($fileHandle, LOCK_EX)) {
                fwrite($fileHandle, $logContent);
                fflush($fileHandle);
                flock($fileHandle, LOCK_UN);
                fclose($fileHandle);
                return true;
            } else {
                fclose($fileHandle);
                throw new RuntimeException("Failed to lock log file: {$this->fileName}");
            }
        } catch (Exception $ex) {
            // Additional error handling can be implemented here, such as sending to an external service
            error_log("LoggerHelper Error: " . $ex->getMessage());
            return false;
        }
    }

    /**
     * Transforms the log message into a string.
     *
     * @param mixed $message The message to transform.
     *
     * @return string The transformed message.
     */
    private function transformLogMessage(mixed $message): string
    {
        return match ($this->logAs) {
            LogType::PRINT => print_r($message, true),
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
     * Static method to add a log entry with DUMP type.
     *
     * @param mixed  $message      The message to log.
     * @param string $topic        The topic name for the log.
     * @param bool   $clearBefore Whether to clear the log file before writing.
     *
     * @return bool True on success, false on failure.
     */
    public static function addToLog(mixed $message, string $topic = '', bool $clearBefore = false): bool
    {
        $logger = new self(topic: $topic);
        $logger->setLogType(LogType::DUMP);
        return $logger->writeToLog($message, $clearBefore);
    }

    /**
     * Static method to log an array with EXPORT type.
     *
     * @param mixed  $message The array or object to log.
     * @param string $topic   The topic name for the log.
     *
     * @return bool True on success, false on failure.
     */
    public static function arrayToLog(mixed $message, string $topic = ''): bool
    {
        $logger = new self(topic: $topic);
        $logger->setLogType(LogType::EXPORT);
        return $logger->writeToLog($message);
    }
}
