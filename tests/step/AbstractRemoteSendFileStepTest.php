<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Steps;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\Remote\AbstractRemoteSendFileStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\SendRemoteStep
 */
final class AbstractRemoteSendFileStepTest extends TestCaseWithAppConfig
{
    private const WORK_DIR_LOCAL = self::TEST_DIR . 'Local' . DIRECTORY_SEPARATOR;
    private const WORK_DIR_REMOTE_BASE = self::TEST_DIR . 'Remote' . DIRECTORY_SEPARATOR;
    private Local $remoteHandler;
    private string $workDirRemote;
    private string $fileMappingPath;
    private array $archives;

    protected function setUp(): void
    {
        $this->workDirRemote = self::WORK_DIR_REMOTE_BASE . basename(self::WORK_DIR_LOCAL) . DIRECTORY_SEPARATOR;
        self::assertDirectoryDoesNotExist($this->workDirRemote);

        $this->fileMappingPath = $this->workDirRemote . 'file_mapping.json';

        $files = [TEST_FIXTURES_FILE_1, TEST_FIXTURES_FILE_2, TEST_FIXTURES_FILE_3];
        $this->setupLocalStorage($files);

        $this->remoteHandler = new Local(self::WORK_DIR_REMOTE_BASE);

        $this->archives = [
            basename(TEST_FIXTURES_FILE_1) => 'content1',
            basename(TEST_FIXTURES_FILE_2) => 'content2',
            basename(TEST_FIXTURES_FILE_3) => 'content3',
        ];

        $this->setUpAppConfig('config_full_valid');
        $this->setStepData(['archives' => $this->archives, 'backupDirectory' => self::WORK_DIR_LOCAL]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
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
        FileHelper::deleteDirectory($this->workDirRemote);
        self::assertDirectoryDoesNotExist($this->workDirRemote);

        $sendRemoteStep = $this->getMockedClass();
        $sendRemoteStep->execute();

        self::assertRemoteStorage($this->archives);
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     */
    public function testExecute()
    {
        $sendRemoteStep = $this->getMockedClass();

        $result = $sendRemoteStep->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);

        self::assertRemoteStorage($this->archives);
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     */
    public function testExecuteReinitialization()
    {
        $archives = [];
        foreach ($this->archives as $file => $content) {
            $archives[$file] = $content;
            $this->setStepData(['archives' => $archives, 'backupDirectory' => self::WORK_DIR_LOCAL]);

            $sendRemoteStep = $this->getMockedClass();
            $result = $sendRemoteStep->execute();

            self::assertInstanceOf(StepResult::class, $result);
            self::assertFalse($result->repeat);

            self::assertRemoteStorage($archives);
        }
    }

    /**
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     *
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     */
    public function testReentryFileMappingDoesMatch()
    {
        $files = [TEST_FIXTURES_FILE_1, TEST_FIXTURES_FILE_2];
        $ts = $this->setupRemoteStorage($files);

        $sendRemoteStep = $this->getMockedClass();
        $sendRemoteStep->execute();

        self::assertRemoteStorage($this->archives);
        foreach ($ts as $path => $fileTime) {
            self::assertSame($fileTime, filemtime($path), "File {$path} was modified.");
        }
    }

    /**
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     *
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     */
    public function testReentryFileMappingDoesNotMatch()
    {
        $filesUploaded = [TEST_FIXTURES_FILE_1, TEST_FIXTURES_FILE_2];
        $filesInFileMapping = [TEST_FIXTURES_FILE_1];
        $ts = $this->setupRemoteStorage($filesUploaded, $filesInFileMapping);

        $sendRemoteStep = $this->getMockedClass();
        $sendRemoteStep->execute();

        self::assertRemoteStorage($this->archives);

        foreach ($filesUploaded as $localPath) {
            $remotePath = $this->workDirRemote . basename($localPath);
            if (in_array($localPath, $filesInFileMapping, true)) {
                self::assertSame($ts[$remotePath], filemtime($remotePath), "File {$remotePath} was modified.");
            } else {
                self::assertGreaterThan($ts[$remotePath], filemtime($remotePath), "File {$remotePath} was not updated.");
            }
        }
    }

    /**
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::getUploadedFiles()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::createBaseDir()
     * @uses \CMS\PhpBackup\Step\DatabaseBackupStep::sendArchives()
     *
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::updateFileMapping()
     */
    public function testUpdateFileMapping()
    {
        $this->setupRemoteStorage([]);

        $sendRemoteStep = $this->getMockedClass();
        $sendRemoteStep->execute();

        self::assertRemoteStorage($this->archives);
    }

    private function assertRemoteStorage(array $archives)
    {
        foreach ($archives as $fileName => $content) {
            $localFile = self::WORK_DIR_LOCAL . $fileName;
            $remoteFile = $this->workDirRemote . $fileName;
            self::assertFileExists($remoteFile);
            self::assertFileEquals($localFile, $remoteFile);
        }

        self::assertFileExists($this->fileMappingPath);
        self::assertJsonStringEqualsJsonFile($this->fileMappingPath, json_encode($archives, JSON_PRETTY_PRINT));
    }

    private function setupLocalStorage(array $files): void
    {
        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertDirectoryExists(self::WORK_DIR_LOCAL);

        foreach ($files as $f) {
            $fn = self::WORK_DIR_LOCAL . basename($f);
            copy($f, $fn);
            self::assertFileExists($fn);
        }
    }

    private function setupRemoteStorage(array $files, array $fileMapping = []): array
    {
        FileHelper::makeDir(self::WORK_DIR_REMOTE_BASE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE_BASE);

        FileHelper::makeDir($this->workDirRemote);
        self::assertDirectoryExists($this->workDirRemote);

        $ts = [];

        foreach ($files as $f) {
            $remoteFile = $this->workDirRemote . basename($f);
            copy($f, $remoteFile);
            self::assertFileExists($remoteFile);
            $ts[$remoteFile] = filemtime($remoteFile);
        }

        $filesToEncode = empty($fileMapping) ? $files : $fileMapping;
        file_put_contents(
            $this->fileMappingPath,
            json_encode(array_map('basename', $filesToEncode), JSON_PRETTY_PRINT)
        );

        // we need to sleep, to be able to detect changes in filemtime
        sleep(2);

        return $ts;
    }

    private function getMockedClass()
    {
        $mockBuilder = $this->getMockBuilder(AbstractRemoteSendFileStep::class);
        $mockBuilder->setConstructorArgs([$this->remoteHandler, $this->config]);

        return $mockBuilder->getMockForAbstractClass();
    }
}
