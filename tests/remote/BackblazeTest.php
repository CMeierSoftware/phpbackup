<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Remote\Backblaze;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use PHPUnit\Framework\TestCase;

class BackblazeTest extends TestCase
{
    private Backblaze $remote;
    private readonly string $workDir;
    private readonly string $remoteFileDest;
    private const WORK_DIR_LOCAL = ABS_PATH . 'tests\\work\\Local\\';
    private const TEST_FILE_SRC = ABS_PATH . 'tests\\fixtures\\zip\\file1.txt';

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->workDir = 'work/' . uniqid() . '/';
        $this->remoteFileDest = $this->workDir . 'file.txt';
    }
    protected function setUp(): void
    {
        $this->remote = new Backblaze('', '', '');
        $this->remote->connect();
        $this->remote->createDirectory($this->workDir);
        $this->assertRemoteFileExists($this->workDir);

        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        $this->assertFileExists(self::WORK_DIR_LOCAL);
    }

    public function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
        //$this->remote->fileDelete($this->testFileDest);
    }

    public function testFileExists()
    {
        $file = 'fixtures/file1.txt';
        $this->assertTrue($this->remote->fileExists($file));
        $this->assertFalse($this->remote->fileExists($file . 'invalid.txt'));
    }

    public function testDirExists()
    {
        $dir = 'fixtures';
        $this->assertTrue($this->remote->fileExists($dir));
        $dir = 'fixtures/sub';
        $this->assertTrue($this->remote->fileExists($dir));
        $dir = 'invalid';
        $this->assertFalse($this->remote->fileExists($dir));
    }

    public function testFileUploadSrcFileNotFound()
    {
        $destFile = 'file.txt';
        $this->expectException(FileNotFoundException::class);
        $this->remote->fileUpload(self::TEST_FILE_SRC . 'invalid', $destFile);
    }

    public function testFileUploadDestFileAlreadyExists()
    {
        $this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest);
        $this->assertRemoteFileExists($this->remoteFileDest);
        $this->expectException(FileAlreadyExistsException::class);
        $this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest);
        $this->assertRemoteFileExists($this->remoteFileDest);
    }

    public function testFileUploadSuccess()
    {
        $this->assertRemoteFileDoesNotExist($this->remoteFileDest);
        $this->assertTrue($this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest));
        $this->assertRemoteFileExists($this->remoteFileDest);
    }

    public function testCreateDirectory()
    {
        $dirs = ['a/', '/b', 'foo/b/c/'];
        foreach ($dirs as $dir) {
            $this->assertRemoteFileDoesNotExist($this->workDir . $dir);
            $this->assertTrue($this->remote->createDirectory($this->workDir . $dir));
            $this->assertRemoteFileExists($this->workDir . $dir);
        }

        $this->remote->createDirectory($this->workDir . 'bar/b/c/test.txt');
        $this->assertRemoteFileExists($this->workDir . 'bar/b/c/');
        $this->assertRemoteFileDoesNotExist($this->workDir . 'bar/b/c/test.txt');
    }

    public function testFileDownloadSrcFileNotFound()
    {
        $file = 'file.txt';
        $this->expectException(FileNotFoundException::class);
        $this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, $file . 'invalid');
    }

    public function testFileDownloadDestFileAlreadyExists()
    {
        $file = 'file1.txt';

        copy(self::TEST_FILE_SRC, self::WORK_DIR_LOCAL . $file);
        $this->assertFileExists(self::WORK_DIR_LOCAL . $file);

        $this->expectException(FileAlreadyExistsException::class);
        $this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, 'fixtures/' . $file);
    }

    public function testFileDownload()
    {
        $file = 'file1.txt';

        $this->assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, 'fixtures/' . $file));
        $this->assertFileExists(self::WORK_DIR_LOCAL . $file);
        $this->assertFileEquals(self::TEST_FILE_SRC, self::WORK_DIR_LOCAL . $file);
    }

    public function testFileDeleteFileNotFound()
    {
        $file = 'invalid.txt';

        $this->expectException(FileNotFoundException::class);
        $this->remote->fileDelete($file);
    }

    public function testFileDelete()
    {
        $this->assertTrue($this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest));
        $this->assertRemoteFileExists($this->remoteFileDest);

        $this->assertTrue($this->remote->fileDelete($this->remoteFileDest));
        $this->assertRemoteFileDoesNotExist($this->remoteFileDest);
    }

    public function assertRemoteFileExists(string $file)
    {
        $this->assertTrue($this->remote->fileExists($file), "{$file} does not exist on remote storage.");
    }
    public function assertRemoteFileDoesNotExist(string $file)
    {
        $this->assertFalse($this->remote->fileExists($file), "{$file} exists on remote storage.");
    }
}
