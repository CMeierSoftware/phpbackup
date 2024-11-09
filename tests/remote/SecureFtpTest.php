<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Remote;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\SecureFtp;
use phpseclib3\Crypt\Random;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use PHPUnit\Framework\TestCase;

define('NET_SSH2_LOGGING', SSH2::LOG_SIMPLE);
/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Remote\SecureFtp
 */
final class SecureFtpTest extends TestCase
{
    private const WORK_DIR_LOCAL = TEST_WORK_DIR . 'Local' . DIRECTORY_SEPARATOR;
    private const WORK_DIR_REMOTE = 'Remote' . DIRECTORY_SEPARATOR;
    private const TEST_FILE1_SRC = TEST_FIXTURES_FILE_1;
    private const TEST_FILE2_SRC = TEST_FIXTURES_FILE_2;
    private $remote;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertDirectoryExists(self::WORK_DIR_LOCAL);

        $this->remote = new SecureFtp($_ENV['SFTP_TEST_SERVER'], $_ENV['SFTP_TEST_USER'], $_ENV['SFTP_TEST_PASSWORD']);
        self::assertTrue($this->remote->connect());
    }

    protected function tearDown(): void
    {
        self::assertTrue($this->remote->dirDelete(self::WORK_DIR_REMOTE));
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileExists()
     */
    public function testFileExists()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertTrue($this->remote->fileExists(self::WORK_DIR_REMOTE . $file));
        self::assertFalse($this->remote->fileExists(self::WORK_DIR_REMOTE . $file . 'invalid'));
        self::assertTrue($this->remote->fileExists(self::WORK_DIR_REMOTE. 'sub' . DIRECTORY_SEPARATOR . $file));
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Backblaze::fileExists()
     */
    public function testDirExists()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        $dir = self::WORK_DIR_REMOTE . '';
        self::assertTrue($this->remote->fileExists($dir));
        $dir = self::WORK_DIR_REMOTE . '.';
        self::assertTrue($this->remote->fileExists($dir));
        $dir = self::WORK_DIR_REMOTE . 'sub';
        self::assertTrue($this->remote->fileExists($dir));
        $dir = self::WORK_DIR_REMOTE . 'invalid';
        self::assertFalse($this->remote->fileExists($dir));
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileUpload()
     */
    public function testFileUploadSuccess()
    {
        $destFile = self::WORK_DIR_REMOTE . 'file.txt';
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE1_SRC, $destFile));
        self::assertRemoteFileExists($destFile);
        // self::assertFileEquals(self::TEST_FILE1_SRC, self::$WORK_DIR_REMOTE . $destFile);

        $destFile = self::WORK_DIR_REMOTE . 'sub\\file.txt';
        self::assertTrue($this->remote->fileUpload(self::TEST_FILE1_SRC, $destFile));
        self::assertRemoteFileExists($destFile);
        // self::assertFileEquals(self::TEST_FILE1_SRC, self::$WORK_DIR_REMOTE . $destFile);
    }

    // /**
    //  * @covers \CMS\PhpBackup\Remote\Local::fileDownload()
    //  */
    // public function testFileDownloadSuccess()
    // {
    //     $file = 'file.txt';
    //     $this->setupRemoteStorage($file);

    //     self::assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
    //     self::assertFileExists(self::WORK_DIR_LOCAL . $file);
    //     self::assertFileExists(self::WORK_DIR_REMOTE . $file);
    //     self::assertFileEquals(self::WORK_DIR_REMOTE . $file, self::WORK_DIR_LOCAL . $file);
    //     self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);

    //     $file = 'sub\\file.txt';
    //     self::assertTrue($this->remote->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
    //     self::assertFileExists(self::WORK_DIR_LOCAL . $file);
    //     self::assertFileExists(self::WORK_DIR_REMOTE . $file);
    //     self::assertFileEquals(self::WORK_DIR_REMOTE . $file, self::WORK_DIR_LOCAL . $file);
    //     self::assertFileEquals(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);
    // }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::fileDelete()
     */
    public function testFileDeleteSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);

        self::assertRemoteFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertTrue($this->remote->fileDelete(self::WORK_DIR_REMOTE . $file));
        self::assertRemoteFileDoesNotExist(self::WORK_DIR_REMOTE . $file);

        $file = 'sub\\file.txt';
        self::assertRemoteFileExists(self::WORK_DIR_REMOTE . $file);
        self::assertTrue($this->remote->fileDelete(self::WORK_DIR_REMOTE . $file));
        self::assertRemoteFileDoesNotExist(self::WORK_DIR_REMOTE . $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::dirList()
     */
    public function testDirectoryListSuccess()
    {
        $file = 'file.txt';
        $this->setupRemoteStorage($file);
        $result = $this->remote->dirList(self::WORK_DIR_REMOTE);

        self::assertEqualsCanonicalizing (['sub', $file], $result);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::dirCreate()
     */
    public function testDirectoryCreateSuccess()
    {
        $dirs = ['a\\b\\c', 'foo\\b\\c\\', 'foo\\b\\c\\'];
        foreach ($dirs as $dir) {
            $this->remote->dirCreate(self::WORK_DIR_REMOTE . $dir);
            self::assertRemoteFileExists(self::WORK_DIR_REMOTE . $dir);
        }

        self::assertTrue($this->remote->dirCreate(self::WORK_DIR_REMOTE . 'bar\\b\\c\\test.txt'));
        self::assertRemoteFileExists(self::WORK_DIR_REMOTE . 'bar\\b\\c\\');
        self::assertRemoteFileDoesNotExist(self::WORK_DIR_REMOTE . 'bar\\b\\c\\test.txt');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\Local::dirDelete()
     */
    public function testDirectoryDeleteSuccess()
    {
        $file = 'file.txt';
        // $this->setupRemoteStorage($file);
        $dir = 'sub';

        // self::assertRemoteFileExists(self::WORK_DIR_REMOTE . $dir);
        self::assertTrue($this->remote->dirDelete(self::WORK_DIR_REMOTE . $dir));
        // self::assertRemoteFileDoesNotExist(self::WORK_DIR_REMOTE . $dir);
        // self::assertRemoteFileDoesNotExist(self::WORK_DIR_REMOTE . $dir . DIRECTORY_SEPARATOR . $file);
    }

    private function setupRemoteStorage(string $file): void
    {
        $this->remote->fileUpload(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . $file);
        self::assertRemoteFileExists(self::WORK_DIR_REMOTE . $file);

        $this->remote->dirCreate(self::WORK_DIR_REMOTE . 'sub');
        self::assertRemoteFileExists(self::WORK_DIR_REMOTE . 'sub');
        $this->remote->fileUpload(self::TEST_FILE1_SRC, self::WORK_DIR_REMOTE . 'sub\\' . $file);
        self::assertRemoteFileExists(self::WORK_DIR_REMOTE . 'sub\\' . $file);
    }

    
    public function assertRemoteFileExists(string $file)
    {
        $this->remote->clearCache();
        self::assertTrue($this->remote->fileExists($file), "{$file} does not exist on remote storage.");
    }

    public function assertRemoteFileDoesNotExist(string $file)
    {
        $this->remote->clearCache();
        self::assertFalse($this->remote->fileExists($file), "{$file} exists on remote storage.");
    }
}
