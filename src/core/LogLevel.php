<?php

declare(strict_types=1);

/**
 * This script is a simple logger that writes messages into a file.
 *
 * Usage:
 *
 * try {
 *     FileLogger::getInstance()->info('Info Message');
 *     // Do some thing
 * } catch(Exception $e) {
 *     FileLogger::getInstance()->error($e->getMessage());
 * }
 *
 * @version 1.0.0
 *
 * @author Christoph Meier
 *
 * @date 19.12.2021
 */

namespace CMS\PhpBackup\Core;

abstract class LogLevel
{
    public const OFF = 0;
    public const ERROR = 1;
    public const WARNING = 2;
    public const INFO = 3;

    /**
     * Converts a log level integer value to its string representation.
     *
     * @param int $level The log level integer value to convert.
     *
     * @return string The string representation of the log level.
     */
    public static function toString(int $level): string
    {
        switch ($level) {
            case LogLevel::OFF:
                return 'OFF';
            case LogLevel::ERROR:
                return 'ERROR';
            case LogLevel::WARNING:
                return 'WARNING';
            case LogLevel::INFO:
                return 'INFO';
            default:
                throw new \Exception("Invalid log level: $level");
        }
    }
}
