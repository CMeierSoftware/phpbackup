<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Backblaze;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Remote\Backblaze
 */
final class BackblazeTest extends TestCase
{
    private const WORK_DIR_LOCAL = ABS_PATH . 'tests\\work\\Local\\';
    private const TEST_FILE_SRC = ABS_PATH . 'tests\\fixtures\\zip\\file1.txt';
    private Backblaze $remote;
    private readonly string $workDir;
    private readonly string $remoteFileDest;

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
        $this->remote->dirCreate($this->workDir);
        $this->assertRemoteFileExists($this->workDir);

        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertFileExists(self::WORK_DIR_LOCAL);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
        // $this->remote->fileDelete($this->testFileDest);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileExists()
     */
    public function testFileExists()
    {
        $file = 'fixtures/file1.txt';
        self::assertTrue($this->remote->fileExists($file));
        self::assertFalse($this->remote->fileExists($file . 'invalid.txt'));
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileExists()
     */
    public function testDirExists()
    {
        $dir = 'fixtures';
        self::assertTrue($this->remote->fileExists($dir));
        $dir = 'fixtures/sub';
        self::assertTrue($this->remote->fileExists($dir));
        $dir = 'invalid';
        self::assertFalse($this->remote->fileExists($dir));
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileUpload()
     */
    public function testFileUploadSrcFileNotFound()
    {
        $destFile = 'file.txt';
        $this->expectException(FileNotFoundException::class);
        $this->remote->fileUpload(self::TEST_FILE_SRC . 'invalid', $destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileUpload()
     */
    public function testFileUploadDestFileAlreadyExists()
    {
        $this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest);
        $this->assertRemoteFileExists($this->remoteFileDest);
        $this->expectException(FileAlreadyExistsException::class);
        $this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest);
        $this->assertRemoteFileExists($this->remoteFileDest);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileUpload()
     */
    public function testFileUploadSuccess()
    {
        $this->assertRemoteFileDoesNotExist($this->remoteFileDest);
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest));
        $this->assertRemoteFileExists($this->remoteFileDest);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::createDirectory()
     */
    public function testCreateDirectory()
    {
        $dirs = ['a/', '/b', 'foo/b/c/'];
        foreach ($dirs as $dir) {
            $this->assertRemoteFileDoesNotExist($this->workDir . $dir);
            self::assertTrue($this->remote->dirCreate($this->workDir . $dir));
            $this->assertRemoteFileExists($this->workDir . $dir);
        }

        $this->remote->dirCreate($this->workDir . 'bar/b/c/test.txt');
        $this->assertRemoteFileExists($this->workDir . 'bar/b/c/');
        $this->assertRemoteFileDoesNotExist($this->workDir . 'bar/b/c/test.txt');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileDownload()
     */
    public function testFileDownloadSrcFileNotFound()
    {
        $file = 'file.txt';
        $this->expectException(FileNotFoundException::class);
        $this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, $file . 'invalid');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileDownload()
     */
    public function testFileDownloadDestFileAlreadyExists()
    {
        $file = 'file1.txt';

        copy(self::TEST_FILE_SRC, self::WORK_DIR_LOCAL . $file);
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);

        $this->expectException(FileAlreadyExistsException::class);
        $this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, 'fixtures/' . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileDownload()
     */
    public function testFileDownload()
    {
        $file = 'file1.txt';

        self::assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, 'fixtures/' . $file));
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);
        self::assertFileEquals(self::TEST_FILE_SRC, self::WORK_DIR_LOCAL . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileDelete()
     */
    public function testFileDeleteFileNotFound()
    {
        $file = 'invalid.txt';

        $this->expectException(FileNotFoundException::class);
        $this->remote->fileDelete($file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileDelete()
     */
    public function testFileDelete()
    {
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest));
        $this->assertRemoteFileExists($this->remoteFileDest);

        self::assertTrue($this->remote->fileDelete($this->remoteFileDest));
        $this->assertRemoteFileDoesNotExist($this->remoteFileDest);
    }

    public function assertRemoteFileExists(string $file)
    {
        self::assertTrue($this->remote->fileExists($file), "{$file} does not exist on remote storage.");
    }

    public function assertRemoteFileDoesNotExist(string $file)
    {
        self::assertFalse($this->remote->fileExists($file), "{$file} exists on remote storage.");
    }
}
