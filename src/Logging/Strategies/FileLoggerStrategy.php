<?php

namespace Helpers\Logging\Strategies;

use Exception;
use RuntimeException;
use DateTimeImmutable;
use Helpers\Logging\Contracts\LoggerStrategyInterface;

class FileLoggerStrategy implements LoggerStrategyInterface
{
    private string $fileName;
    private string $moduleName;
    private int $logType;

    /**
     * LogType constants.
     */
    public const PRINT = 0;
    public const DUMP = 1;
    public const EXPORT = 2;

    /**
     * FileLoggerStrategy constructor.
     *
     * @param string $fileName    The file path where logs will be written.
     * @param string $moduleName  The name of the module for trace information.
     * @param int    $logType     The type of log formatting.
     */
    public function __construct(string $fileName, string $moduleName = 'unknown', int $logType = self::PRINT)
    {
        $this->fileName = $fileName;
        $this->moduleName = $moduleName;
        $this->logType = $logType;
    }

    /**
     * Logs a message to a file.
     *
     * @param string $message      The message to log.
     * @param array  $context      Additional context (unused in this strategy).
     * @param bool   $clearBefore Whether to clear existing logs before writing.
     *
     * @return bool True on success, false on failure.
     */
    public function log(string $message, array $context = [], bool $clearBefore = false): bool
    {
        try {
            if ($clearBefore && file_exists($this->fileName)) {
                if (!unlink($this->fileName)) {
                    throw new RuntimeException("Failed to clear log file: {$this->fileName}");
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

            // Transform the message based on log type
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
            // Additional error handling can be implemented here
            error_log("FileLoggerStrategy Error: " . $ex->getMessage());
            return false;
        }
    }

    /**
     * Transforms the log message based on the log type.
     *
     * @param string $message The message to transform.
     *
     * @return string The transformed message.
     */
    private function transformLogMessage(string $message): string
    {
        switch ($this->logType) {
            case self::PRINT:
                return $message;
            case self::DUMP:
                return var_export($message, true);
            case self::EXPORT:
                return $this->varExportPretty($message);
            default:
                return $message;
        }
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
}
