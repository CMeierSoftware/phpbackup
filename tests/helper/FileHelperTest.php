<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use PHPUnit\Framework\TestCase;
use Exception;

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

    public function testMoveFileSuccess()
    {
        $dest = self::TEST_DIR . 'destination.txt';

        FileHelper::moveFile(self::TEST_FILE, $dest);

        $this->assertFileExists($dest);
        $this->assertFileDoesNotExist(self::TEST_FILE);
        $this->assertTrue(is_file($dest));
    }

    public function testMoveFileFailure()
    {
        $src = self::TEST_DIR . '/nonexistent_source.txt';
        $dest = self::TEST_DIR . '/destination.txt';

        $this->expectException(Exception::class);
        FileHelper::moveFile($src, $dest);
    }

    public function testMakeDirSuccess()
    {
        $newDir = self::TEST_DIR . '/new_directory';

        FileHelper::makeDir($newDir, 0444);

        $this->assertFileExists($newDir);
        $this->assertTrue(is_dir($newDir));
        $this->assertFileMode($newDir, 0444);
    }

    public function testDeleteDirectorySuccess()
    {
        FileHelper::deleteDirectory(self::TEST_DIR);

        $this->assertFileDoesNotExist(self::TEST_FILE);
        $this->assertFileDoesNotExist(self::TEST_DIR);
    }

    public function testDoesDirExists()
    {
        $this->assertTrue(FileHelper::doesDirExists(self::TEST_DIR));

        $nonExistingDir = self::TEST_DIR . '/nonexistent_directory';
        $this->assertFalse(FileHelper::doesDirExists($nonExistingDir));
    }

    public function testChangePermissionSuccess()
    {
        $filePath = self::TEST_DIR . '/file.txt';
        touch($filePath);

        FileHelper::changePermission($filePath, 0777);

        $this->assertFileMode($filePath, 0777);
    }

    public function testChangePermissionFailure()
    {
        $nonExistentFile = self::TEST_DIR . '/nonexistent_file.txt';

        $this->expectException(Exception::class);
        FileHelper::changePermission($nonExistentFile, 0777);
    }

    private function assertFileMode($filePath, $expectedMode)
    {
        $actualMode = fileperms($filePath) & 0777;

        $this->assertEquals($expectedMode, $actualMode, "File permission mismatch for $filePath");
    }
}
