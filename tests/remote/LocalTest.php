<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
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
    private Local $local;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertFileExists(self::WORK_DIR_LOCAL);
        FileHelper::makeDir(self::WORK_DIR_REMOTE);
        self::assertFileExists(self::WORK_DIR_REMOTE);

        $this->local = new Local(self::WORK_DIR_REMOTE);
        $this->local->connect();
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
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE);
        new Local(self::WORK_DIR_REMOTE);
        self::assertFileExists(self::WORK_DIR_REMOTE);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileExists()
     */
    public function testFileExists()
    {
        $file = 'file.txt';
        self::assertFalse($this->local->fileExists($file));
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertTrue($this->local->fileExists($file));

        $file = 'sub\\file.txt';
        self::assertFalse($this->local->fileExists($file));
        FileHelper::makeDir(self::WORK_DIR_REMOTE . 'sub');
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertTrue($this->local->fileExists($file));
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileUpload()
     */
    public function testFileUploadSrcFileNotFound()
    {
        $srcFile = self::TEST_FILE1_SRC . 'invalid';
        $destFile = 'file.txt';
        self::assertFileDoesNotExist($srcFile);
        $this->expectException(FileNotFoundException::class);
        $this->local->fileUpload($srcFile, $destFile);
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileUpload()
     */
    public function testFileUploadDestFileAlreadyExists()
    {
        $destFile = 'file.txt';
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $destFile);
        self::assertFileExists(self::WORK_DIR_REMOTE . $destFile);

        $this->expectException(FileAlreadyExistsException::class);
        $this->local->fileUpload(self::TEST_FILE2_SRC, $destFile);
        // check if remote file didn't change
        self::assertFileEquals(self::TEST_FILE2_SRC, self::WORK_DIR_REMOTE . $destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileUpload()
     */
    public function testFileUploadSuccess()
    {
        $destFile = 'file.txt';
        self::assertTrue($this->local->fileUpload(self::TEST_FILE1_SRC, $destFile));
        self::assertFileExists(self::WORK_DIR_REMOTE . $destFile);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $destFile);

        $destFile = 'sub\\file.txt';
        self::assertTrue($this->local->fileUpload(self::TEST_FILE1_SRC, $destFile));
        self::assertFileExists(self::WORK_DIR_REMOTE . $destFile);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileDownload()
     */
    public function testFileDownloadSrcFileNotFound()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file . 'invalid');

        $this->expectException(FileNotFoundException::class);
        $this->local->fileDownload(self::WORK_DIR_LOCAL . $file, $file . 'invalid');

        self::assertFileDoesNotExist(self::WORK_DIR_LOCAL . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileDownload()
     */
    public function testFileDownloadDestFileAlreadyExists()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        copy(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);

        $this->expectException(FileAlreadyExistsException::class);
        $this->local->fileDownload(self::WORK_DIR_LOCAL . $file, $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileDownload()
     */
    public function testFileDownload()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertTrue($this->local->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertFileEquals(self::WORK_DIR_REMOTE . $file, self::WORK_DIR_LOCAL . $file);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);

        $file = 'sub\\file.txt';
        self::assertTrue($this->local->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertFileEquals(self::WORK_DIR_REMOTE . $file, self::WORK_DIR_LOCAL . $file);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileDelete()
     */
    public function testFileDeleteFileNotFound()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file . 'invalid');
        $this->expectException(FileNotFoundException::class);
        $this->local->fileDelete($file . 'invalid');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileDelete()
     */
    public function testFileDelete()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertTrue($this->local->fileDelete($file));
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file);

        $file = 'sub\\file.txt';
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertTrue($this->local->fileDelete($file));
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::createDirectory()
     */
    public function testCreateDirectory()
    {
        $dirs = ['a\\b\\c', 'foo\\b\\c\\', 'foo\\b\\c\\'];
        foreach ($dirs as $dir) {
            $this->local->createDirectory($dir);
            self::assertFileExists(self::WORK_DIR_REMOTE . $dir);
        }

        $this->local->createDirectory('bar\b\\c\\test.txt');
        self::assertFileExists(self::WORK_DIR_REMOTE . 'bar\b\\c\\');
        self::assertFileDoesNotExist(self::WORK_DIR_REMOTE . 'bar\b\\c\\test.txt');
    }

    private function setupRemoteStorage(string $file): void
    {
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . $file);

        FileHelper::makeDir(self::WORK_DIR_REMOTE . 'sub');
        self::assertFileExists(self::WORK_DIR_REMOTE . 'sub');
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . 'sub\\' . $file);
        self::assertFileExists(self::WORK_DIR_REMOTE . 'sub\\' . $file);
    }
}
