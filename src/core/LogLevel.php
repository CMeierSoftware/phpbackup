<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Represents different log levels for logging messages.
 */
final class LogLevel
{
    public const OFF = 0;
    public const ERROR = 1;
    public const WARNING = 2;
    public const INFO = 3;

    /**
     * Converts a log level integer value to its string representation.
     *
     * @param int $level The log level integer value to convert.
     * @return string The string representation of the log level.
     * @throws \Exception If an invalid log level is provided.
     */
    public static function toString(int $level): string
    {
        switch ($level) {
            case self::OFF:
                return 'OFF';

            case self::ERROR:
                return 'ERROR';

            case self::WARNING:
                return 'WARNING';

            case self::INFO:
                return 'INFO';

            default:
                throw new \Exception("Invalid log level: {$level}");
        }
    }
}
