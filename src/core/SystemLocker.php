<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;
use CMS\PhpBackup\Exceptions\FileNotFoundException;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Exceptions\SystemAlreadyLockedException;

define('LOCK_TS', date('Y.m.d-H:i:s', time()));

final abstract class SystemLocker
{
    public const DEFAULT_LOCK_FILE = '.lock_system';

    /**
     * Tries to lock the system by creating a lock file.
     *
     * @param string $system_path The path to the system.
     *
     * @throws SystemAlreadyLockedException If the system is already locked.
     * @throws \Exception If the system cannot be locked.
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
     * Checks if the system is locked.
     *
     * @param string $system_path The path to the system.
     *
     * @return bool True if the system is locked, false otherwise.
     */
    public static function isLocked(string $system_path): bool
    {
        return file_exists(self::getLockFilePath($system_path));
    }

    /**
     * Tries to unlock the system.
     * It only unlocks it if this instance locked it.
     *
     * @param string $system_path The path to the system.
     */
    public static function unlock(string $system_path): void
    {
        FileLogger::getInstance()->Info("unlock the system.");

        if (LOCK_TS === self::readLockFile($system_path)) {
            unlink(self::getLockFilePath($system_path));
        }
    }

    /**
     * Reads the content of the lock file.
     *
     * @param string $system_path The path to the system.
     *
     * @return string The content of the lock file.
     *
     * @throws FileNotFoundException If the system is not locked.
     */
    public static function readLockFile(string $system_path): string
    {
        if (!self::isLocked($system_path)) {
            throw new FileNotFoundException('System not locked.');
        }
        
        return file_get_contents(self::getLockFilePath($system_path));
    }

    /**
     * Returns the path to the lock file.
     *
     * @param string $system_path The path to the system.
     *
     * @return string The path to the lock file.
     */
    private static function getLockFilePath(string $system_path): string
    {
        return $system_path . DIRECTORY_SEPARATOR . self::DEFAULT_LOCK_FILE;
    }
}
