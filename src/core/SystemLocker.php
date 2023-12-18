<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;
use CMS\PhpBackup\Exceptions\FileNotFoundException;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Exceptions\SystemAlreadyLockedException;

define('LOCK_TS', date('Y.m.d-H:i:s', time()));

abstract class SystemLocker
{
    public const DEFAULT_LOCK_FILE = '.lock_system';

    /**
     * Function tries to lock the system.
     * Throws an Exception if can not lock the system.
     */
    public static function lock(string $system_path): void
    {
        FileLogger::getInstance()->Info("lock the system.");

        if (self::isLocked($system_path)) {
            throw new SystemAlreadyLockedException('System-Locker: System is locked since ' . self::readLockFile($system_path) . ' UTC.');
        }

        $result = file_put_contents(self::getLockFilePath($system_path), LOCK_TS);

        if (false === $result && !self::isLocked($system_path)) {
            throw new \Exception('System-Locker: Can not lock system.');
        }
    }

    /**
     * Function checks if the system is locked.
     * @return bool is system locked
     */
    public static function isLocked(string $system_path): bool
    {
        return file_exists(self::getLockFilePath($system_path));
    }

    /*
     * Function tries to unlock the system.
     * It only unlocks it, if this instance locked it.
     */
    public static function unlock(string $system_path): void
    {
        FileLogger::getInstance()->Info("unlock the system.");

        if (LOCK_TS === self::readLockFile($system_path)) {
            unlink(self::getLockFilePath($system_path));
        }
    }

    /**
     * Function reads content of the lock file
     * @return string The entire file in a string, FALSE on failure
     */
    public static function readLockFile(string $system_path): string
    {
        if (!self::isLocked($system_path)) {
            throw new FileNotFoundException('System not locked.');
        }
        
        return file_get_contents(self::getLockFilePath($system_path));
    }

    private static function getLockFilePath(string $system_path): string
    {
        return $system_path . DIRECTORY_SEPARATOR . self::DEFAULT_LOCK_FILE;
    }
}
