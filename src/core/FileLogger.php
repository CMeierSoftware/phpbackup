<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

class FileLogger
{
    public const DEFAULT_LOG_FILE = './logs.txt';

    protected static ?FileLogger $instance = null;
    private string $log_file;
    private int $log_level;
    private bool $echo_logs;

    /**
     * Constructs the logger instance.
     *
     * @param string $log_file The path to the log file.
     * @param int $log_level The log level to use.
     * @param bool $echo_logs Whether to echo log messages in HTML format.
     */
    protected function __construct(string $log_file, int $log_level, bool $echo_logs)
    {
        $this->log_file = $log_file;
        $this->log_level = $log_level;
        $this->echo_logs = $echo_logs;
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
     * @param string $log_file The path to the log file (optional, default: `logs.txt`).
     * @param int $log_level The log level to use (optional, default: `LogLevel::WARNING`).
     * @param bool $echo_logs Whether to echo log messages in HTML format (optional, default: `false`).
     *
     * @return FileLogger The singleton instance of the logger.
     */
    public static function getInstance(string $log_file = self::DEFAULT_LOG_FILE, int $log_level = LogLevel::WARNING, bool $echo_logs = false): FileLogger
    {
        if (null === self::$instance) {
            self::$instance = new self($log_file, $log_level, $echo_logs);
        }

        return self::$instance;
    }

    /**
     * Function sets a new path to a log file. This new file will be created if it still not exists.
     *
     * @param $log_file: path to the new log file. WARNING: The directory must already exists.
     */
    public function SetLogFile(string $log_file)
    {
        $this->log_file = $log_file;
        FileLogger::GetInstance()->Info("set log file to $log_file");
    }

    /**
     * activates 'echo logs'.
     * IF you activate 'echo logs', the logger will echo the logs in the file AND will print it as HTML.
     */
    public function ActivateEchoLogs()
    {
        $this->echo_logs = true;
    }

    /**
     * Deactivates 'echo logs'.
     */
    public function DeactivateEchoLogs()
    {
        $this->echo_logs = false;
    }

    /**
     * Set the Log level
     * Type must be from 'LogLevel'
     */
    public function SetLogLevel(int $log_level): void
    {
        self::GetInstance()->Info('set log level to ' . LogLevel::toString($log_level));
        $this->log_level = $log_level;
    }

    /**
     * Writes a message of the level 'Error'
     */
    public function Error(string $message): void
    {
        $this->WriteEntry(LogLevel::ERROR, $message);
    }

    /**
     * Writes a message of the level 'Warning'
     */
    public function Warning(string $message)
    {
        $this->WriteEntry(LogLevel::WARNING, $message);
    }

    /**
     * Writes a message of the level 'Info'.
     *
     * @param string $message The message to be logged
     */
    public function Info(string $message): void
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
    private function ConcatEntry(int $level, string $message): string
    {
        $timestamp = date('d.m.Y H:i:s');

        return LogLevel::ToString($level) . "\t" . $timestamp . "\t" . $message . "\n";
    }

    /**
     * Writes a log entry to the file and optionally echoes it to the screen.
     *
     * @param string $entry The log entry to be written to the file
     *
     */
    private function WriteToFile(string $entry)
    {
        file_put_contents($this->log_file, $entry, FILE_APPEND);

        if ($this->echo_logs) {
            echo $entry . '<br>';
        }
    }
}
