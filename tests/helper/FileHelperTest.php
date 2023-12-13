<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Helper\FileHelper
 */
class FileHelperTest extends TestCase
{
    private const FIXTURES_DIR = ABS_PATH . '\\tests\\fixtures\\';
    private const TEST_DIR = ABS_PATH . 'tests\\work\\test_directory\\';
    private const TEST_FILE = self::TEST_DIR . 't.txt';

    protected function setUp(): void
    {
        // Create a temporary test directory for the tests
        FileHelper::makeDir(self::TEST_DIR);
        copy(self::FIXTURES_DIR . 'zip\\file1.txt', self::TEST_FILE);
        $this->assertFileExists(self::TEST_DIR);
        $this->assertFileExists(self::TEST_FILE);
    }

    protected function tearDown(): void
    {
        // Clean up the test directory after tests
        if (file_exists(self::TEST_DIR)) {
            FileHelper::deleteDirectory(self::TEST_DIR);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::moveFile()
     */
    public function testMoveFileSuccess()
    {
        $dest = self::TEST_DIR . 'destination.txt';

        FileHelper::moveFile(self::TEST_FILE, $dest);

        $this->assertFileExists($dest);
        $this->assertFileDoesNotExist(self::TEST_FILE);
        $this->assertTrue(is_file($dest));
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::moveFile()
     */
    public function testMoveFileFailure()
    {
        $src = self::TEST_DIR . '/nonexistent_source.txt';
        $dest = self::TEST_DIR . '/destination.txt';

        $this->expectException(\Exception::class);
        FileHelper::moveFile($src, $dest);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::makeDir()
     */
    public function testMakeDirSuccess()
    {
        $newDir = self::TEST_DIR . '/new_directory';

        FileHelper::makeDir($newDir);

        $this->assertFileExists($newDir);
        $this->assertTrue(is_dir($newDir));
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::makeDir()
     */
    public function testMakeDirCurrentDirectory()
    {
        // Attempt to create the current directory, which should just be ignored
        $currentDir = '.';
        FileHelper::makeDir($currentDir);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::deleteDirectory()
     */
    public function testDeleteDirectorySuccess()
    {
        $this->assertFileExists(self::TEST_FILE);
        $this->assertFileExists(self::TEST_DIR);

        FileHelper::deleteDirectory(self::TEST_DIR);

        $this->assertFileDoesNotExist(self::TEST_FILE);
        $this->assertFileDoesNotExist(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::deleteDirectory()
     */
    public function testDeleteDirectoryNotExists()
    {
        $this->assertFileDoesNotExist(self::TEST_DIR . 'Invalid');
        FileHelper::deleteDirectory(self::TEST_DIR . 'Invalid');
        $this->assertFileDoesNotExist(self::TEST_DIR . 'Invalid');
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::doesDirExists()
     */
    public function testDoesDirExists()
    {
        $this->assertTrue(FileHelper::doesDirExists(self::TEST_DIR));

        $nonExistingDir = self::TEST_DIR . '/nonexistent_directory';
        $this->assertFalse(FileHelper::doesDirExists($nonExistingDir));
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::changePermission()
     */
    public function testChangePermissionSuccess()
    {
        $filePath = self::TEST_DIR . '/file.txt';
        touch($filePath);
        $this->assertFileExists($filePath);

        FileHelper::changePermission($filePath, 0o755);

        $this->assertFileMode($filePath, 0o777);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::changePermission()
     */
    public function testChangePermissionFailure()
    {
        $nonExistentFile = self::TEST_DIR . '/nonexistent_file.txt';

        $this->expectException(\Exception::class);
        FileHelper::changePermission($nonExistentFile, 0o777);
    }

    private function assertFileMode(string $filePath, int $expectedMode)
    {
        $actualMode = fileperms($filePath) & 0o777;

        $this->assertEquals($expectedMode, $actualMode, "File permission mismatch for {$filePath}");
    }
}
