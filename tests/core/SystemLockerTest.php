<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\SystemLocker;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\SystemAlreadyLockedException;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\SystemLocker
 */
final class SystemLockerTest extends TestCase
{
    private const TEST_DIR = TEST_WORK_DIR . 'System1';
    private const TEST_DIR2 = TEST_WORK_DIR . 'system2';

    protected function setUp(): void
    {
        FileHelper::makeDir(self::TEST_DIR);
        self::assertDirectoryExists(self::TEST_DIR);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Core\SystemLocker::isLocked()
     * @covers \CMS\PhpBackup\Core\SystemLocker::lock()
     * @covers \CMS\PhpBackup\Core\SystemLocker::readLockFile()
     * @covers \CMS\PhpBackup\Core\SystemLocker::unlock()
     */
    public function testLockSystem()
    {
        SystemLocker::lock(self::TEST_DIR);

        self::assertTrue(SystemLocker::isLocked(self::TEST_DIR));

        $lockTimestamp = SystemLocker::readLockFile(self::TEST_DIR);
        self::assertNotEmpty($lockTimestamp);

        SystemLocker::unlock(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Core\SystemLocker::lock()
     * @covers \CMS\PhpBackup\Core\SystemLocker::unlock()
     */
    public function testLockedExceptionOnDoubleLock()
    {
        SystemLocker::lock(self::TEST_DIR);

        $this->expectException(SystemAlreadyLockedException::class);
        SystemLocker::lock(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Core\SystemLocker::lock()
     * @covers \CMS\PhpBackup\Core\SystemLocker::unlock()
     */
    public function testUnlockDoesNotAffectOtherLocks()
    {
        FileHelper::makeDir(self::TEST_DIR2);
        self::assertDirectoryExists(self::TEST_DIR2);

        try {
            SystemLocker::lock(self::TEST_DIR);

            SystemLocker::lock(self::TEST_DIR2);
            self::assertTrue(SystemLocker::isLocked(self::TEST_DIR));

            SystemLocker::unlock(self::TEST_DIR2);

            self::assertTrue(SystemLocker::isLocked(self::TEST_DIR));

            SystemLocker::unlock(self::TEST_DIR);
        } finally {
            FileHelper::deleteDirectory(self::TEST_DIR2);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Core\SystemLocker::readLockFile()
     */
    public function testReadLockFileWhenLocked()
    {
        SystemLocker::lock(self::TEST_DIR);

        $lockTimestamp = SystemLocker::readLockFile(self::TEST_DIR);

        self::assertNotEmpty($lockTimestamp);

        SystemLocker::unlock(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Core\SystemLocker::readLockFile()
     */
    public function testReadLockFileWhenUnlocked()
    {
        self::expectException(FileNotFoundException::class);
        SystemLocker::readLockFile(self::TEST_DIR);
    }
}
