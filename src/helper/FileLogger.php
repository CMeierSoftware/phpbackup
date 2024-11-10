<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Helper;

if (!defined('ABS_PATH')) {
    return;
}

enum LogLevel: int
{
    case OFF = 0;
    case ERROR = 1;
    case WARNING = 2;
    case INFO = 3;
    case DEBUG = 4;
}

final class FileLogger
{
    public const DEFAULT_LOG_FILE = './logs.log';

    private static ?self $instance = null;
    private string $logFile = self::DEFAULT_LOG_FILE;
    private LogLevel $logLevel = LogLevel::OFF;
    private bool $echoLogs = false;
    private readonly bool $isAjax;

    private function __construct()
    {
        $this->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 'xmlhttprequest' === strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    /**
     * Clones the logger instance (disallowed).
     */
    private function __clone() {}

    /**
     * Gets the singleton instance of the logger.
     *
     * @return FileLogger the singleton instance of the logger
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Function sets a new path to a log file. This new file will be created if it still not exists.
     */
    public function setLogFile(string $log_file): void
    {
        $this->info("set log file to {$log_file}");
        $this->logFile = $log_file;
        FileHelper::makeDir(dirname($log_file));
    }

    /**
     * Set the Log level
     * Type must be from 'LogLevel'.
     */
    public function setLogLevel(LogLevel $newLogLevel): void
    {
        $this->logLevel = $newLogLevel;
        $this->debug('set log level to ' . $this->logLevel->name);

        if (function_exists('CMS_is_debug_mode') && CMS_is_debug_mode()) {
            $this->logLevel = LogLevel::DEBUG;
            $this->info('Debugging is active, overwrite log level to ' . $this->logLevel->name);
        }
    }

    /**
     * activates 'echo logs'.
     * IF you activate 'echo logs', the logger will echo the logs in the file AND will print it as HTML.
     */
    public function activateEchoLogs(): void
    {
        $this->echoLogs = true;
    }

    /**
     * Deactivates 'echo logs'.
     */
    public function deactivateEchoLogs(): void
    {
        $this->echoLogs = false;
    }

    /**
     * Writes a message of the level 'Error'.
     */
    public function error(string $message): void
    {
        $this->writeEntry(LogLevel::ERROR, $message);
    }

    /**
     * Writes a message of the level 'Warning'.
     */
    public function warning(string $message): void
    {
        $this->writeEntry(LogLevel::WARNING, $message);
    }

    /**
     * Writes a message of the level 'Info'.
     *
     * @param string $message The message to be logged
     */
    public function info(string $message): void
    {
        $this->writeEntry(LogLevel::INFO, $message);
    }

    /**
     * Writes a message of the level 'Debug'.
     *
     * @param string $message The message to be logged
     */
    public function debug(string $message): void
    {
        $this->writeEntry(LogLevel::DEBUG, $message);
    }

    /**
     * Writes a log entry to the file if the log level is equal to or greater than the specified level.
     *
     * @param LogLevel $level The log level of the message
     * @param string $message The message to be logged
     */
    private function writeEntry(LogLevel $level, string $message): void
    {
        if ($this->logLevel->value >= $level->value) {
            $entry = $this->concatEntry($level, $message);
            $this->writeToFile($entry);
        }
    }

    /**
     * Formats a log entry with the specified log level, timestamp, and message.
     *
     * @param -LogLevel $level The log level of the message
     * @param string $message The message to be logged
     *
     * @return string The formatted log entry
     */
    private function concatEntry(LogLevel $level, string $message): string
    {
        $timestamp = date('d.m.Y H:i:s');
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4)[3];
        $class = $trace['class'] ?? basename($trace['file']);

        return $level->name . "\t" . $timestamp . "\t" . $class . ": \t" . $message . "\n";
    }

    /**
     * Writes a log entry to the file and optionally echoes it to the screen.
     *
     * @param string $entry The log entry to be written to the file
     */
    private function writeToFile(string $entry): void
    {
        file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);

        if ($this->echoLogs && !$this->isAjax) {
            echo $entry . '<br>';
        }
    }
}
