<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

abstract class LogLevel
{
    public const OFF = 0;
    public const ERROR = 1;
    public const WARNING = 2;
    public const INFO = 3;

    /**
     * Converts a log level integer value to its string representation.
     *
     * @param int $level the log level integer value to convert
     *
     * @return string the string representation of the log level
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
