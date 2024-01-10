<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Helper;

use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Helper\FileHelper
 */
final class FileHelperTest extends TestCase
{
    private const TEST_DIR = TEST_WORK_DIR;
    private const TEST_FILE = self::TEST_DIR . 't.txt';

    protected function setUp(): void
    {
        FileHelper::makeDir(self::TEST_DIR);
        self::assertDirectoryExists(self::TEST_DIR);

        copy(TEST_FIXTURES_FILE_1, self::TEST_FILE);
        self::assertFileExists(self::TEST_FILE);
    }

    protected function tearDown(): void
    {
        // FileHelper::deleteDirectory(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::moveFile()
     */
    public function testMoveFileSuccess()
    {
        $dest = self::TEST_DIR . 'destination.txt';

        FileHelper::moveFile(self::TEST_FILE, $dest);

        self::assertFileExists($dest);
        self::assertFileDoesNotExist(self::TEST_FILE);
        self::assertTrue(is_file($dest));
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::moveFile()
     */
    public function testMoveFileFailure()
    {
        $src = self::TEST_DIR . 'nonexistent_source.txt';
        $dest = self::TEST_DIR . 'destination.txt';

        $this->expectException(\Exception::class);
        FileHelper::moveFile($src, $dest);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::makeDir()
     */
    public function testMakeDirSuccess()
    {
        $newDir = self::TEST_DIR . 'new_directory';

        FileHelper::makeDir($newDir);

        self::assertFileExists($newDir);
        self::assertTrue(is_dir($newDir));
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
        FileHelper::deleteDirectory(self::TEST_DIR);

        self::assertFileDoesNotExist(self::TEST_FILE);
        self::assertDirectoryDoesNotExist(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::deleteDirectory()
     */
    public function testDeleteDirectoryWithSubDirSuccess()
    {
        $subDir = self::TEST_DIR . 'sub';
        FileHelper::makeDir($subDir);
        self::assertDirectoryExists($subDir);

        $subFile = $subDir . DIRECTORY_SEPARATOR . 'f.txt';
        copy(TEST_FIXTURES_FILE_1, $subFile);
        self::assertFileExists($subFile);

        FileHelper::deleteDirectory(self::TEST_DIR);

        self::assertFileDoesNotExist($subFile);
        self::assertDirectoryDoesNotExist($subDir);
        self::assertFileDoesNotExist(self::TEST_FILE);
        self::assertDirectoryDoesNotExist(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::deleteDirectory()
     */
    public function testDeleteDirectoryNotExists()
    {
        self::assertFileDoesNotExist(self::TEST_DIR . 'Invalid');
        FileHelper::deleteDirectory(self::TEST_DIR . 'Invalid');
        self::assertFileDoesNotExist(self::TEST_DIR . 'Invalid');
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::doesDirExists()
     */
    public function testDoesDirExists()
    {
        self::assertTrue(FileHelper::doesDirExists(self::TEST_DIR));

        $nonExistingDir = self::TEST_DIR . 'nonexistent_directory';
        self::assertFalse(FileHelper::doesDirExists($nonExistingDir));
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::changePermission()
     */
    public function testChangePermissionSuccess()
    {
        $filePath = self::TEST_DIR . 'file.txt';
        touch($filePath);
        self::assertFileExists($filePath);

        FileHelper::changePermission($filePath, 0o755);

        $this->assertFileMode($filePath, 0o777);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileHelper::changePermission()
     */
    public function testChangePermissionFailure()
    {
        $nonExistentFile = self::TEST_DIR . 'nonexistent_file.txt';

        $this->expectException(\Exception::class);
        FileHelper::changePermission($nonExistentFile, 0o777);
    }

    private function assertFileMode(string $filePath, int $expectedMode)
    {
        $actualMode = fileperms($filePath) & 0o777;

        self::assertSame($expectedMode, $actualMode, "File permission mismatch for {$filePath}");
    }
}
