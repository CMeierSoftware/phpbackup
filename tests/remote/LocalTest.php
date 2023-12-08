<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use PHPUnit\Framework\TestCase;

class LocalTest extends TestCase
{
    private Local $local;
    private const WORK_DIR = ABS_PATH . 'tests\\work\\LocalTest\\';
    private const TEST_FILE_SRC = ABS_PATH . 'tests\\fixtures\\zip\\file1.txt';

    private static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        return rmdir($dir);
    }

    private function prepareRemoteStorage(string $file): string
    {
        $downloadDir = self::WORK_DIR . 'downloads\\';
        mkdir($downloadDir);
        copy(self::TEST_FILE_SRC, self::WORK_DIR . $file);

        mkdir(self::WORK_DIR . 'sub');
        mkdir($downloadDir . 'sub');
        copy(self::TEST_FILE_SRC, self::WORK_DIR . 'sub\\' . $file);

        return $downloadDir;
    }

    protected function setUp(): void
    {
        self::deleteDirectory(self::WORK_DIR);
        $this->local = new Local(self::WORK_DIR);
    }

    public function testCreateDirIfNotExists(): void
    {
        self::deleteDirectory(self::WORK_DIR);
        $this->assertFileDoesNotExist(self::WORK_DIR);
        new Local(self::WORK_DIR);
        $this->assertFileExists(self::WORK_DIR);
    }

    public function testFileExists()
    {
        $file = 'file.txt';
        $this->assertFalse($this->local->fileExists($file));
        copy(self::TEST_FILE_SRC, self::WORK_DIR . $file);
        $this->assertTrue($this->local->fileExists($file));

        $file = 'sub\\file.txt';
        $this->assertFalse($this->local->fileExists($file));
        mkdir(self::WORK_DIR . 'sub');
        copy(self::TEST_FILE_SRC, self::WORK_DIR . $file);
        $this->assertTrue($this->local->fileExists($file));
    }

    public function testFileUploadFileNotFound()
    {
        $destFile = 'file.txt';
        $this->expectException(FileNotFoundException::class);
        $this->local->fileUpload(self::TEST_FILE_SRC . 'invalid', $destFile);
    }

    public function testFileUploadFileAlreadyExists()
    {
        $destFile = 'file.txt';
        copy(self::TEST_FILE_SRC, self::WORK_DIR . $destFile);
        $this->expectException(FileAlreadyExistsException::class);
        $this->local->fileUpload(self::TEST_FILE_SRC, $destFile);
    }

    public function testFileUploadSuccess()
    {
        $destFile = 'file.txt';
        $this->assertTrue($this->local->fileUpload(self::TEST_FILE_SRC, $destFile));
        $this->assertFileExists(self::WORK_DIR . $destFile);
        $this->assertFileEquals(self::TEST_FILE_SRC, self::WORK_DIR . $destFile);

        $destFile = 'sub\\file.txt';
        $this->assertTrue($this->local->fileUpload(self::TEST_FILE_SRC, $destFile));
        $this->assertFileExists(self::WORK_DIR . $destFile);
        $this->assertFileEquals(self::TEST_FILE_SRC, self::WORK_DIR . $destFile);
    }

    public function testFileDownloadFileNotFound()
    {
        $file = 'file.txt';
        $downloadDir = $this->prepareRemoteStorage($file);
        $this->expectException(FileNotFoundException::class);
        $this->local->fileDownload($downloadDir . $file, $file . 'invalid');
    }

    public function testFileDownloadFileAlreadyExists()
    {
        $file = 'file.txt';
        $downloadDir = $this->prepareRemoteStorage($file);
        copy(self::TEST_FILE_SRC, $downloadDir . $file);
        $this->expectException(FileAlreadyExistsException::class);
        $this->local->fileDownload($downloadDir . $file, $file);
    }

    public function testFileDownload()
    {
        $file = 'file.txt';
        $downloadDir = $this->prepareRemoteStorage($file);
        $this->assertTrue($this->local->fileDownload($downloadDir . $file, $file));
        $this->assertFileExists($downloadDir . $file);
        $this->assertFileEquals(self::TEST_FILE_SRC, $downloadDir . $file);

        $file = 'sub\\file.txt';
        $this->assertTrue($this->local->fileDownload($downloadDir . $file, $file));
        $this->assertFileExists($downloadDir . $file);
        $this->assertFileEquals(self::TEST_FILE_SRC, $downloadDir . $file);

    }


    public function testFileDeleteFileNotFound()
    {
        $file = 'file.txt';
        $this->prepareRemoteStorage($file);
        $this->expectException(FileNotFoundException::class);
        $this->local->fileDelete($file . 'invalid');
    }

    public function testFileDelete()
    {
        $file = 'file.txt';
        $this->prepareRemoteStorage($file);

        $this->assertFileExists(self::WORK_DIR . $file);
        $this->assertTrue($this->local->fileDelete($file));
        $this->assertFileDoesNotExist(self::WORK_DIR . $file);

        $file = 'sub\\file.txt';
        $this->assertFileExists(self::WORK_DIR . $file);
        $this->assertTrue($this->local->fileDelete($file));
        $this->assertFileDoesNotExist(self::WORK_DIR . $file);
    }

    public function testCreateDirectory()
    {
        $dirs = ['a\\b\\c', 'foo\\b\\c\\', 'foo\\b\\c\\', 'bar\b\\c\\test.txt'];
        foreach ($dirs as $dir) {
            $this->local->createDirectory($dir);
            $this->assertFileExists(self::WORK_DIR . $dir);
        }
    }

}
