<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\SendRemoteStep;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\SendRemoteStep
 */
final class SendRemoteStepTest extends TestCase
{
    private const WORK_DIR_LOCAL = TEST_WORK_DIR . 'Local' . DIRECTORY_SEPARATOR;
    private const WORK_DIR_REMOTE_BASE = TEST_WORK_DIR . 'Remote' . DIRECTORY_SEPARATOR;
    private const TEST_FILE1_SRC = TEST_FIXTURES_FILE_1;
    private const TEST_FILE2_SRC = TEST_FIXTURES_FILE_2;
    private Local $remoteHandler;
    private string $workDirRemote;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertDirectoryExists(self::WORK_DIR_LOCAL);

        FileHelper::makeDir(self::WORK_DIR_REMOTE_BASE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE_BASE);

        $this->workDirRemote = self::WORK_DIR_REMOTE_BASE . basename(self::WORK_DIR_LOCAL);
        self::assertDirectoryDoesNotExist($this->workDirRemote);

        self::setupLocalStorage();

        $this->remoteHandler = new Local(self::WORK_DIR_REMOTE_BASE);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
        FileHelper::deleteDirectory(self::WORK_DIR_REMOTE_BASE);
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     */
    public function testExecute()
    {
        $archives = [
            basename(self::TEST_FILE1_SRC) => 'content1',
            basename(self::TEST_FILE2_SRC) => 'content2',
        ];

        $sendRemoteStep = new SendRemoteStep($this->remoteHandler, self::WORK_DIR_LOCAL, $archives);

        $result = $sendRemoteStep->execute();

        // Assert that the result is as expected
        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);

        self::assertRemoteStorage($archives);
    }

    /**
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     *
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     */
    public function testReentry()
    {
        $archives = [
            basename(self::TEST_FILE1_SRC) => 'content1',
            basename(self::TEST_FILE2_SRC) => 'content2',
        ];

        FileHelper::makeDir($this->workDirRemote);
        $remotePathFile1 = $this->workDirRemote . DIRECTORY_SEPARATOR . basename(self::TEST_FILE1_SRC);
        copy(self::TEST_FILE1_SRC, $remotePathFile1);
        self::assertFileExists($remotePathFile1);

        $sendRemoteStep = new SendRemoteStep($this->remoteHandler, self::WORK_DIR_LOCAL, $archives);
        $sendRemoteStep->execute();

        self::assertRemoteStorage($archives);
    }

    /**
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     *
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::uploadFileMapping()
     */
    public function testReplaceFileMapping()
    {
        $archives = [
            basename(self::TEST_FILE1_SRC) => 'content1',
            basename(self::TEST_FILE2_SRC) => 'content2',
        ];

        FileHelper::makeDir($this->workDirRemote);
        $fileMappingPath = $this->workDirRemote . DIRECTORY_SEPARATOR . 'file_mapping.json';
        touch($fileMappingPath);
        self::assertFileExists($fileMappingPath);

        $sendRemoteStep = new SendRemoteStep($this->remoteHandler, self::WORK_DIR_LOCAL, $archives);
        $sendRemoteStep->execute();

        self::assertRemoteStorage($archives);
    }

    /**
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::uploadFileMapping()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     *
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     */
    public function testCreateBaseDir()
    {
        $archives = [
            basename(self::TEST_FILE1_SRC) => 'content1',
            basename(self::TEST_FILE2_SRC) => 'content2',
        ];

        FileHelper::deleteDirectory($this->workDirRemote);
        self::assertDirectoryDoesNotExist($this->workDirRemote);

        $sendRemoteStep = new SendRemoteStep($this->remoteHandler, self::WORK_DIR_LOCAL, $archives);
        $sendRemoteStep->execute();

        self::assertRemoteStorage($archives);
    }

    private function assertRemoteStorage(array $archives)
    {
        foreach ($archives as $fileName => $content) {
            self::assertFileExists($this->workDirRemote . DIRECTORY_SEPARATOR . $fileName);
        }
        $fileMappingPath = $this->workDirRemote . DIRECTORY_SEPARATOR . 'file_mapping.json';

        self::assertFileExists($fileMappingPath);
        self::assertJsonStringEqualsJsonFile($fileMappingPath, json_encode($archives, JSON_PRETTY_PRINT));
    }

    private static function setupLocalStorage(): void
    {
        $files = [self::TEST_FILE1_SRC, self::TEST_FILE2_SRC];
        foreach ($files as $f) {
            $fn = basename($f);
            copy($f, self::WORK_DIR_LOCAL . $fn);
            self::assertFileExists(self::WORK_DIR_LOCAL . $fn);
        }
    }
}
