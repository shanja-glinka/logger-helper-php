<?php

namespace helpers;

class LoggerHelper
{
    /** @var string */
    private $moduleName;

    /** @var string */
    private $fileName;



    public function __construct(string $topic = '', string $module = '')
    {
        $this->fileName = $_SERVER['DOCUMENT_ROOT'] . '/logs/log_' . $topic . '.txt';
        $this->moduleName = $module;
    }


    /**
     * @param mixed $message
     * @param string $topic
     * @param boolean $clear_before
     * @return bool
     */
    public function writeToToLog($message, bool $clearBefore = false): bool
    {
        if ($clearBefore) {
            unlink($this->fileName);
        }

        clearstatcache();

        $fileStream = fopen($this->fileName, 'a');
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

        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $trace = $this->moduleName;

        if (empty($trace)) {
            $backtrace = debug_backtrace();
            $trace = $backtrace[0]['file'] . ':' . $backtrace[0]['line'];
        }

        fwrite($fileStream, '<' . $trace . '> ' . $message . "\n");
        fclose($fileStream);

        return true;
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
        return $logger->writeToToLog($message, $clearBefore);
    }
}
