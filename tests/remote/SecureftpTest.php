<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Remote;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Secureftp;
use phpseclib3\Net\SSH2;
use PHPUnit\Framework\TestCase;

define('NET_SSH2_LOGGING', SSH2::LOG_SIMPLE);

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Remote\Secureftp
 */
final class SecureftpTest extends TestCase
{
    private const WORK_DIR_LOCAL = TEST_WORK_DIR . 'Local' . DIRECTORY_SEPARATOR;
    private const WORK_DIR_REMOTE = 'Test' . DIRECTORY_SEPARATOR;
    private const TEST_FILE1_SRC = TEST_FIXTURES_FILE_1;
    private const TEST_FILE2_SRC = TEST_FIXTURES_FILE_2;
    private $remote;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertDirectoryExists(self::WORK_DIR_LOCAL);

        $this->remote = new Secureftp($_ENV['SFTP_TEST_SERVER'], $_ENV['SFTP_TEST_USER'], $_ENV['SFTP_TEST_PASSWORD'], self::WORK_DIR_REMOTE);
        self::assertTrue($this->remote->connect());
    }

    protected function tearDown(): void
    {
        // self::assertTrue($this->remote->dirDelete('.'));
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Secureftp::deleteDirectory()
     */
    public function testCreateRootDirIfNotExists(): void
    {
        self::assertTrue($this->remote->dirDelete('.'));
        self::assertRemoteFileDoesNotExist('.');
        new Secureftp($_ENV['SFTP_TEST_SERVER'], $_ENV['SFTP_TEST_USER'], $_ENV['SFTP_TEST_PASSWORD'], self::WORK_DIR_REMOTE);
        self::assertRemoteFileExists('.');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Secureftp::fileExists()
     */
    public function testFileExists()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertTrue($this->remote->fileExists($file));
        self::assertFalse($this->remote->fileExists($file . 'invalid'));
        self::assertTrue($this->remote->fileExists('sub' . DIRECTORY_SEPARATOR . $file));
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
     * @covers \CMS\PhpBackup\Remote\Secureftp::fileUpload()
     */
    public function testFileUploadSuccess()
    {
        $destFile = 'file.txt';
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE1_SRC, $destFile));
        self::assertRemoteFileExists($destFile);

        $destFile = 'sub' . DIRECTORY_SEPARATOR . 'file.txt';
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE1_SRC, $destFile));
        self::assertRemoteFileExists($destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Secureftp::fileDownload()
     */
    public function testFileDownloadSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);
        self::assertRemoteFileExists($file);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);

        $file = 'sub' . DIRECTORY_SEPARATOR . 'file.txt';
        self::assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);
        self::assertRemoteFileExists($file);
        self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Secureftp::fileDelete()
     */
    public function testFileDeleteSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertRemoteFileExists($file);
        self::assertTrue($this->remote->fileDelete($file));
        self::assertRemoteFileDoesNotExist($file);

        $file = 'sub' . DIRECTORY_SEPARATOR . 'file.txt';
        self::assertRemoteFileExists($file);
        self::assertTrue($this->remote->fileDelete($file));
        self::assertRemoteFileDoesNotExist($file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Secureftp::dirList()
     */
    public function testDirectoryListSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);
        $result = $this->remote->dirList('.');

        self::assertEqualsCanonicalizing(['sub', $file], $result);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Secureftp::dirCreate()
     */
    public function testDirectoryCreateSuccess()
    {
        $dirs = [
            'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c', 
            'foo' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c' . DIRECTORY_SEPARATOR, 
            'foo' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c' . DIRECTORY_SEPARATOR,
            'bar' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c' . DIRECTORY_SEPARATOR . 'test.txt',
        ];

        foreach ($dirs as $dir) {
            self::assertTrue($this->remote->dirCreate($dir));
            if (str_ends_with($dir, '.txt')) {
                self::assertRemoteFileDoesNotExist($dir);
            } else {
                self::assertRemoteFileExists($dir);
            }
        }
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Secureftp::dirDelete()
     */
    public function testDirectoryDeleteSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);
        $dir = 'sub';

        self::assertRemoteFileExists($dir);
        self::assertTrue($this->remote->dirDelete($dir));
        self::assertRemoteFileDoesNotExist($dir);
        self::assertRemoteFileDoesNotExist($dir . DIRECTORY_SEPARATOR . $file);
    }

    public function assertRemoteFileExists(string $file): void
    {
        $this->remote->clearCache();
        self::assertTrue($this->remote->fileExists($file), "{$file} does not exist on remote storage.");
    }

    public function assertRemoteFileDoesNotExist(string $file): void
    {
        $this->remote->clearCache();
        self::assertFalse($this->remote->fileExists($file), "{$file} exists on remote storage.");
    }

    private function setupRemoteStorage(string $file): void
    {
        $this->remote->fileUpload(self::TEST_FILE1_SRC, $file);
        self::assertRemoteFileExists($file);

        $this->remote->dirCreate('sub');
        self::assertRemoteFileExists('sub');
        $this->remote->fileUpload(self::TEST_FILE1_SRC, 'sub\\' . $file);
        self::assertRemoteFileExists('sub\\' . $file);
    }
}
