<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Remote;

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
    private const WORK_DIR_LOCAL = TEST_WORK_DIR . 'Local' . DIRECTORY_SEPARATOR;
    private const TEST_FILE_SRC = TEST_FIXTURES_FILE_1;
    private Backblaze $remote;
    private readonly string $remoteWorkDir;
    private readonly string $remoteFileDest;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->remoteWorkDir = 'work/' . uniqid() . '/';
        $this->remoteFileDest = $this->remoteWorkDir . 'file.txt';
    }

    protected function setUp(): void
    {
        $this->remote = new Backblaze('', '', '');
        $this->remote->connect();
        $this->remote->dirCreate($this->remoteWorkDir);
        $this->assertRemoteFileExists($this->remoteWorkDir);

        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertDirectoryExists(self::WORK_DIR_LOCAL);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
        $this->remote->fileDelete($this->remoteWorkDir);
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
    public function testFileUploadSuccess()
    {
        $this->assertRemoteFileDoesNotExist($this->remoteFileDest);
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE_SRC, $this->remoteFileDest));
        $this->assertRemoteFileExists($this->remoteFileDest);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::createDirectory()
     */
    public function testDirectoryCreateSuccess()
    {
        $dirs = ['a/', '/b', 'foo/b/c/'];
        foreach ($dirs as $dir) {
            $this->assertRemoteFileDoesNotExist($this->remoteWorkDir . $dir);
            self::assertTrue($this->remote->dirCreate($this->remoteWorkDir . $dir));
            $this->assertRemoteFileExists($this->remoteWorkDir . $dir);
        }

        $this->remote->dirCreate($this->remoteWorkDir . 'bar/b/c/test.txt');
        $this->assertRemoteFileExists($this->remoteWorkDir . 'bar/b/c/');
        $this->assertRemoteFileDoesNotExist($this->remoteWorkDir . 'bar/b/c/test.txt');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileDownload()
     */
    public function testFileDownloadSuccess()
    {
        $file = 'file1.txt';

        self::assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, 'fixtures/' . $file));
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);
        self::assertFileEquals(self::TEST_FILE_SRC, self::WORK_DIR_LOCAL . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileDelete()
     */
    public function testFileDeleteSuccess()
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
