<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

final class FileLogger
{
    public const DEFAULT_LOG_FILE = './logs.log';

    protected static ?FileLogger $instance = null;
    private string $log_file = self::DEFAULT_LOG_FILE;
    private int $log_level = LogLevel::WARNING;
    private bool $echo_logs = false;

    protected function __construct()
    {
    }

    /**
     * Clones the logger instance (disallowed).
     */
    protected function __clone()
    {
    }

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
            $this->log_file = $log_file;
            FileHelper::makeDir(dirname($log_file));
            $this->info("set log file to {$log_file}");

    }

    /**
     * Set the Log level
     * Type must be from 'LogLevel'.
     */
    public function setLogLevel(int $log_level): void
    {
        $this->info('set log level to ' . LogLevel::toString($log_level));
        $this->log_level = $log_level;
    }

    /**
     * activates 'echo logs'.
     * IF you activate 'echo logs', the logger will echo the logs in the file AND will print it as HTML.
     */
    public function activateEchoLogs(): void
    {
        $this->echo_logs = true;
    }

    /**
     * Deactivates 'echo logs'.
     */
    public function deactivateEchoLogs(): void
    {
        $this->echo_logs = false;
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
    public function warning(string $message)
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
     * Writes a log entry to the file if the log level is equal to or greater than the specified level.
     *
     * @param int $level The log level of the message
     * @param string $message The message to be logged
     */
    private function writeEntry(int $level, string $message): void
    {
        if ($this->log_level >= $level) {
            $entry = $this->concatEntry($level, $message);
            $this->writeToFile($entry);
        }
    }

    /**
     * Formats a log entry with the specified log level, timestamp, and message.
     *
     * @param int $level The log level of the message
     * @param string $message The message to be logged
     *
     * @return string The formatted log entry
     */
    private function concatEntry(int $level, string $message): string
    {
        $timestamp = date('d.m.Y H:i:s');
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4);
        $class = '';
        if (isset($trace[3]['object'])) {
            $class = get_class($trace[3]['object']);
        } else {
            $class = basename($trace[3]['file']);
        }

        return LogLevel::toString($level) . "\t" . $timestamp . "\t" . $class . ': ' . $message . "\n";
    }

    /**
     * Writes a log entry to the file and optionally echoes it to the screen.
     *
     * @param string $entry The log entry to be written to the file
     */
    private function writeToFile(string $entry): void
    {
        file_put_contents($this->log_file, $entry, FILE_APPEND | LOCK_EX);

        if ($this->echo_logs) {
            echo $entry . '<br>';
        }
    }
}
