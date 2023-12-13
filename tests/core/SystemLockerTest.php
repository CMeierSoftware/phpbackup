<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\SystemLocker;
use CMS\PhpBackup\Exceptions\SystemAlreadyLockedException;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class SystemLockerTest extends TestCase
{
    private const TEST_DIR = ABS_PATH . 'tests\\work\\';
    private const TEST_DIR2 = ABS_PATH . 'tests\\work2\\';

    public function setUp(): void
    {
        FileHelper::makeDir(self::TEST_DIR);

        $this->assertFileExists(self::TEST_DIR);
    }

    public function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_DIR);
    }

    /**
     * @covers \lock
     * @covers \unlock
     *
     * @uses \SystemLocker::isLocked
     * @uses \SystemLocker::readLockFile
     */
    public function testLockSystem()
    {
        SystemLocker::lock(self::TEST_DIR);

        $this->assertTrue(SystemLocker::isLocked(self::TEST_DIR));

        $lockTimestamp = SystemLocker::readLockFile(self::TEST_DIR);
        $this->assertNotEmpty($lockTimestamp);

        SystemLocker::unlock(self::TEST_DIR);
    }

    /**
     * @covers \lock
     *
     * @uses \unlock
     */
    public function testLockedExceptionOnDoubleLock()
    {
        SystemLocker::lock(self::TEST_DIR);

        $this->expectException(SystemAlreadyLockedException::class);
        SystemLocker::lock(self::TEST_DIR);

        SystemLocker::unlock(self::TEST_DIR);
    }

    /**
     * @covers \lock
     * @covers \unlock
     */
    public function testUnlockDoesNotAffectOtherLocks()
    {
        FileHelper::makeDir(self::TEST_DIR2);
        $this->assertFileExists(self::TEST_DIR2);

        try {
            SystemLocker::lock(self::TEST_DIR);

            SystemLocker::lock(self::TEST_DIR2);
            $this->assertTrue(SystemLocker::isLocked(self::TEST_DIR));

            SystemLocker::unlock(self::TEST_DIR2);

            $this->assertTrue(SystemLocker::isLocked(self::TEST_DIR));

            SystemLocker::unlock(self::TEST_DIR);
        } finally {
            FileHelper::deleteDirectory(self::TEST_DIR2);
        }
    }

    /**
     * @covers \readLockFile
     */
    public function testReadLockFileWhenLocked()
    {
        SystemLocker::lock(self::TEST_DIR);

        $lockTimestamp = SystemLocker::readLockFile(self::TEST_DIR);

        $this->assertNotEmpty($lockTimestamp);

        SystemLocker::unlock(self::TEST_DIR);
    }

    /**
     * @covers \readLockFile
     */
    public function testReadLockFileWhenUnlocked()
    {
        $lockTimestamp = SystemLocker::readLockFile(self::TEST_DIR);

        $this->assertEmpty($lockTimestamp);
    }
}
