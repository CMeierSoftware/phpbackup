<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Remote\Local
 */
final class LocalTest extends TestCase
{
    private const WORK_DIR_LOCAL = ABS_PATH . 'tests\\work\\Local\\';
    private const WORK_DIR_REMOTE = ABS_PATH . 'tests\\work\\Remote\\';
    private const TEST_FILE1_SRC = ABS_PATH . 'tests\\fixtures\\zip\\file1.txt';
    private const TEST_FILE2_SRC = ABS_PATH . 'tests\\fixtures\\zip\\file2.xls';
    private Local $remote;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertDirectoryExists(self::WORK_DIR_LOCAL);
        FileHelper::makeDir(self::WORK_DIR_REMOTE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE);

        $this->remote = new Local(self::WORK_DIR_REMOTE);
        $this->remote->connect();
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
        FileHelper::deleteDirectory(self::WORK_DIR_REMOTE);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::deleteDirectory()
     */
    public function testCreateRootDirIfNotExists(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_REMOTE);
        self::assertDirectoryDoesNotExist(self::WORK_DIR_REMOTE);
        new Local(self::WORK_DIR_REMOTE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileExists()
     */
    public function testFileExists()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertTrue($this->remote->fileExists($file));
        self::assertFalse($this->remote->fileExists($file . 'invalid'));
        self::assertTrue($this->remote->fileExists('sub/' . $file));
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileExists()
     */
    public function testDirExists()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        $dir = '';
        self::assertTrue($this->remote->fileExists($dir));
        $dir = '.';
        self::assertTrue($this->remote->fileExists($dir));
        $dir = 'sub';
        self::assertTrue($this->remote->fileExists($dir));
        $dir = 'invalid';
        self::assertFalse($this->remote->fileExists($dir));
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileUpload()
     */
    public function testFileUploadSuccess()
    {
        $destFile = 'file.txt';
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE1_SRC, $destFile));
        self::assertFileExists(self::WORK_DIR_REMOTE . $destFile);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $destFile);

        $destFile = 'sub\\file.txt';
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE1_SRC, $destFile));
        self::assertFileExists(self::WORK_DIR_REMOTE . $destFile);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileDownload()
     */
    public function testFileDownloadSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertFileEquals(self::WORK_DIR_REMOTE . $file, self::WORK_DIR_LOCAL . $file);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);

        $file = 'sub\\file.txt';
        self::assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertFileEquals(self::WORK_DIR_REMOTE . $file, self::WORK_DIR_LOCAL . $file);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileDelete()
     */
    public function testFileDeleteSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertTrue($this->remote->fileDelete($file));
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file);

        $file = 'sub\\file.txt';
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertTrue($this->remote->fileDelete($file));
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::dirList()
     */
    public function testDirectoryListSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);
        $result = $this->remote->dirList('.');

        self::assertSame([$file, 'sub'], $result);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::dirCreate()
     */
    public function testDirectoryCreateSuccess()
    {
        $dirs = ['a\\b\\c', 'foo\\b\\c\\', 'foo\\b\\c\\'];
        foreach ($dirs as $dir) {
            $this->remote->dirCreate($dir);
            self::assertFileExists(self::WORK_DIR_REMOTE . $dir);
        }

        $this->remote->dirCreate('bar\b\\c\\test.txt');
        self::assertFileExists(self::WORK_DIR_REMOTE . 'bar\b\\c\\');
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . 'bar\b\\c\\test.txt');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::dirDelete()
     */
    public function testDirectoryDeleteSuccess()
    {
        $dirs = ['sub\\', 'sub'];
        foreach ($dirs as $dir) {
            $file = 'file.txt';
            $this->setupRemoteStorage($file);

            self::assertFileExists(self::WORK_DIR_REMOTE . $dir);
            self::assertTrue($this->remote->dirDelete($dir));
            self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $dir);
            self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $dir . DIRECTORY_SEPARATOR . $file);
        }
    }

    private function setupRemoteStorage(string $file): void
    {
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);

        FileHelper::makeDir(self::WORK_DIR_REMOTE . 'sub');
        self::assertDirectoryExists(self::WORK_DIR_REMOTE . 'sub');
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . 'sub\\' . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . 'sub\\' . $file);
    }
}
