<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use PHPUnit\Framework\TestCase;

class LocalTest extends TestCase
{
    private Local $local;
    private const WORK_DIR_LOCAL = ABS_PATH . 'tests\\work\\Local\\';
    private const WORK_DIR_REMOTE = ABS_PATH . 'tests\\work\\Remote\\';
    private const TEST_FILE1_SRC = ABS_PATH . 'tests\\fixtures\\zip\\file1.txt';
    private const TEST_FILE2_SRC = ABS_PATH . 'tests\\fixtures\\zip\\file2.xls';

    private function setupRemoteStorage(string $file): void
    {
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $file);
        $this->assertFileExists(self::WORK_DIR_REMOTE . $file);

        FileHelper::makeDir(self::WORK_DIR_REMOTE . 'sub');
        $this->assertFileExists(self::WORK_DIR_REMOTE . 'sub');
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . 'sub\\' . $file);
        $this->assertFileExists(self::WORK_DIR_REMOTE . 'sub\\' . $file);
    }

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        $this->assertFileExists(self::WORK_DIR_LOCAL);
        FileHelper::makeDir(self::WORK_DIR_REMOTE);
        $this->assertFileExists(self::WORK_DIR_REMOTE);

        $this->local = new Local(self::WORK_DIR_REMOTE);
        $this->local->connect();
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
        FileHelper::deleteDirectory(self::WORK_DIR_REMOTE);
    }

    public function testCreateRootDirIfNotExists(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_REMOTE);
        $this->assertFileDoesNotExist(self::WORK_DIR_REMOTE);
        new Local(self::WORK_DIR_REMOTE);
        $this->assertFileExists(self::WORK_DIR_REMOTE);
    }

    public function testFileExists()
    {
        $file = 'file.txt';
        $this->assertFalse($this->local->fileExists($file));
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $file);
        $this->assertFileExists(self::WORK_DIR_REMOTE . $file);
        $this->assertTrue($this->local->fileExists($file));

        $file = 'sub\\file.txt';
        $this->assertFalse($this->local->fileExists($file));
        FileHelper::makeDir(self::WORK_DIR_REMOTE . 'sub');
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $file);
        $this->assertFileExists(self::WORK_DIR_REMOTE . $file);
        $this->assertTrue($this->local->fileExists($file));
    }

    public function testFileUploadSrcFileNotFound()
    {
        $srcFile = self::TEST_FILE1_SRC . 'invalid';
        $destFile = 'file.txt';
        $this->assertFileDoesNotExist($srcFile);
        $this->expectException(FileNotFoundException::class);
        $this->local->fileUpload($srcFile, $destFile);
        $this->assertFileDoesNotExist(self::WORK_DIR_REMOTE . $destFile);

    }

    public function testFileUploadDestFileAlreadyExists()
    {
        $destFile = 'file.txt';
        copy(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $destFile);
        $this->assertFileExists(self::WORK_DIR_REMOTE . $destFile);

        $this->expectException(FileAlreadyExistsException::class);
        $this->local->fileUpload(self::TEST_FILE2_SRC, $destFile);
        // check if remote file didn't change
        $this->assertFileEquals(self::TEST_FILE2_SRC, self::WORK_DIR_REMOTE . $destFile);
    }

    public function testFileUploadSuccess()
    {
        $destFile = 'file.txt';
        $this->assertTrue($this->local->fileUpload(self::TEST_FILE1_SRC, $destFile));
        $this->assertFileExists(self::WORK_DIR_REMOTE . $destFile);
        $this->assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $destFile);

        $destFile = 'sub\\file.txt';
        $this->assertTrue($this->local->fileUpload(self::TEST_FILE1_SRC, $destFile));
        $this->assertFileExists(self::WORK_DIR_REMOTE . $destFile);
        $this->assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $destFile);
    }

    public function testFileDownloadSrcFileNotFound()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        $this->assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file . 'invalid');

        $this->expectException(FileNotFoundException::class);
        $this->local->fileDownload(self::WORK_DIR_LOCAL . $file, $file . 'invalid');

        $this->assertFileDoesNotExist(self::WORK_DIR_LOCAL . $file);
    }

    public function testFileDownloadDestFileAlreadyExists()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        copy(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);
        $this->assertFileExists(self::WORK_DIR_LOCAL . $file);

        $this->expectException(FileAlreadyExistsException::class);
        $this->local->fileDownload(self::WORK_DIR_LOCAL . $file, $file);
    }

    public function testFileDownload()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        $this->assertTrue($this->local->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        $this->assertFileExists(self::WORK_DIR_LOCAL . $file);
        $this->assertFileExists(self::WORK_DIR_REMOTE . $file);
        $this->assertFileEquals(self::WORK_DIR_REMOTE . $file, self::WORK_DIR_LOCAL . $file);
        $this->assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);

        $file = 'sub\\file.txt';
        $this->assertTrue($this->local->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        $this->assertFileExists(self::WORK_DIR_LOCAL . $file);
        $this->assertFileExists(self::WORK_DIR_REMOTE . $file);
        $this->assertFileEquals(self::WORK_DIR_REMOTE . $file, self::WORK_DIR_LOCAL . $file);
        $this->assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);
    }

    public function testFileDeleteFileNotFound()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);
        $this->assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file . 'invalid');
        $this->expectException(FileNotFoundException::class);
        $this->local->fileDelete($file . 'invalid');
    }

    public function testFileDelete()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        $this->assertFileExists(self::WORK_DIR_REMOTE . $file);
        $this->assertTrue($this->local->fileDelete($file));
        $this->assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file);

        $file = 'sub\\file.txt';
        $this->assertFileExists(self::WORK_DIR_REMOTE . $file);
        $this->assertTrue($this->local->fileDelete($file));
        $this->assertFileDoesNotExist(self::WORK_DIR_REMOTE . $file);
    }

    public function testCreateDirectory()
    {
        $dirs = ['a\\b\\c', 'foo\\b\\c\\', 'foo\\b\\c\\'];
        foreach ($dirs as $dir) {
            $this->local->createDirectory($dir);
            $this->assertFileExists(self::WORK_DIR_REMOTE . $dir);
        }

        $this->local->createDirectory('bar\b\\c\\test.txt');
        $this->assertFileExists(self::WORK_DIR_REMOTE . 'bar\b\\c\\');
        $this->assertFileDoesNotExist(self::WORK_DIR_REMOTE . 'bar\b\\c\\test.txt');
    }

}
