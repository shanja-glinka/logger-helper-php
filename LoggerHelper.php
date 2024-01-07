<?php

namespace backend\helpers;

use Exception;

class LoggerHelper
{
    /** @var string */
    private $moduleName;

    /** @var string */
    private $fileName;

    const AS_PRINT = 0;
    const AS_DUMP = 1;
    const AS_EXPORT = 2;


    private $logAs = self::AS_PRINT;



    public function __construct(string $topic = '', string $module = '')
    {
        $this->fileName = $_SERVER['DOCUMENT_ROOT'] . '/logs/log_' . $topic . '.txt';
        $this->moduleName = $module;
    }


    public function setLogType($logType)
    {
        $this->logAs = $logType;
    }

    /**
     * @param mixed $message
     * @param string $topic
     * @param boolean $clear_before
     * @return bool
     */
    public function writeToLog($message, bool $clearBefore = false): bool
    {
        try {
            if ($clearBefore) {
                unlink($this->fileName);
            }

            clearstatcache();

            $fileStream = @fopen($this->fileName, 'a');
            if (!$fileStream) {
                if (!mkdir(dirname($this->fileName), 0755, true)) {
                    return false;
                }
                $fileStream = fopen($this->fileName, 'a');
            }

            $timer = file_exists($this->fileName) ? @filemtime($this->fileName) : 0;
            $timerDelay = abs(time() - $timer);

            if (10 <= $timerDelay) {
                fwrite($fileStream, '- - - - - [' . gmdate('d.m.y H:i:s') . ($timerDelay <= 120 ? ' +' . $timerDelay : '') . '] - - - - -' . "\n");
            }

            $message = $this->transformLogMessage($message);

            $trace = $this->moduleName;

            if (empty($trace)) {
                $backtrace = debug_backtrace();
                $trace = $backtrace[1]['file'] . ':' . $backtrace[1]['line'];
            }

            fwrite($fileStream, '<' . $trace . '> ' . $message . "\n");
            fclose($fileStream);

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }


    /**
     * @param mixed $message
     */
    private function transformLogMessage($message)
    {

        if (!is_array($message) && !is_object($message)) {
            return $message;
        }

        switch ($this->logAs) {
            case self::AS_PRINT:
                return print_r($message, true);
            case self::AS_DUMP:
                return var_export($message, true);
            case self::AS_EXPORT:
                return $this->varexport($message, true);
            default:
                return $message;
        }
    }


    /**
     * @param mixed $expression
     * @param bool $return
     */
    private function varexport($expression, bool $return = false)
    {
        $export = var_export($expression, true);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(['['] + $array));

        if ($return) {
            return $export;
        } else {
            echo $export;
        }
    }

    /**
     * @param mixed $message
     * @param string $topic
     * @param boolean $clear_before
     * @return void
     */
    public static function addToLog($message, string $topic = '', bool $clearBefore = false)
    {
        $logger = new self($topic);
        $logger->setLogType(self::AS_DUMP);

        return $logger->writeToLog($message, $clearBefore);
    }


    /**
     * @param mixed $message
     * @param string $topic
     * @return void
     */
    public static function arrayToLog($message, string $topic = '')
    {
        $logger = new self($topic);
        $logger->setLogType(self::AS_EXPORT);

        return $logger->writeToLog($message);
    }
}
