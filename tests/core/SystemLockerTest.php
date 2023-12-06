<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\SystemLockedException;
use CMS\PhpBackup\Core\SystemLocker;
use PHPUnit\Framework\TestCase;

class SystemLockerTest extends TestCase
{
    private string $system_path = __DIR__ . '/../work';

    public function setUp(): void
    {
        // Clean up before each test
        if (file_exists($this->system_path . '/.lock_system')) {
            unlink($this->system_path . '/.lock_system');
        }
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
        // Lock the system
        SystemLocker::lock($this->system_path);

        // Assert that the system is locked
        $this->assertTrue(SystemLocker::isLocked($this->system_path));

        // Assert that reading the lock file returns a timestamp
        $lockTimestamp = SystemLocker::readLockFile($this->system_path);
        $this->assertNotEmpty($lockTimestamp);

        // Clean up: Unlock the system
        SystemLocker::unlock($this->system_path);
    }

    /**
     * @covers \lock
     *
     * @uses \unlock
     */
    public function testLockedExceptionOnDoubleLock()
    {
        // Lock the system
        SystemLocker::lock($this->system_path);

        // Attempt to lock the system again, expect SystemLockedException
        $this->expectException(SystemLockedException::class);
        SystemLocker::lock($this->system_path);

        // Clean up: Unlock the system
        SystemLocker::unlock($this->system_path);
    }

    /**
     * @covers \lock
     * @covers \unlock
     */
    public function testUnlockDoesNotAffectOtherLocks()
    {
        $otherSystemPath = __DIR__ . '/../work2';
        mkdir($otherSystemPath);
        try {
            // Lock the system
            SystemLocker::lock($this->system_path);

            // Create another instance with a different path
            SystemLocker::lock($otherSystemPath);

            // Unlock the system with the other path
            SystemLocker::unlock($otherSystemPath);

            // Assert that the original system is still locked
            $this->assertTrue(SystemLocker::isLocked($this->system_path));

            // Clean up: Unlock the original system
            SystemLocker::unlock($this->system_path);
        } finally {
            rmdir($otherSystemPath);
        }
    }

    /**
     * @covers \readLockFile
     */
    public function testReadLockFileWhenLocked()
    {
        // Lock the system
        SystemLocker::lock($this->system_path);

        // Read the lock file
        $lockTimestamp = SystemLocker::readLockFile($this->system_path);

        // Assert that the lock timestamp is not empty
        $this->assertNotEmpty($lockTimestamp);

        // Clean up: Unlock the system
        SystemLocker::unlock($this->system_path);
    }

    /**
     * @covers \readLockFile
     */
    public function testReadLockFileWhenUnlocked()
    {
        // Read the lock file when the system is not locked
        $lockTimestamp = SystemLocker::readLockFile($this->system_path);

        // Assert that the lock timestamp is empty
        $this->assertEmpty($lockTimestamp);
    }
}
