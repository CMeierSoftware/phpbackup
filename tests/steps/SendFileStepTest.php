<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\SendFileStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\SendFileStep
 */
final class SendFileStepTest extends TestCaseWithAppConfig
{
    private const WORK_DIR_LOCAL = self::TEST_DIR . 'Local' . DIRECTORY_SEPARATOR;
    private const WORK_DIR_REMOTE_BASE = self::TEST_DIR . 'Remote' . DIRECTORY_SEPARATOR;
    private Local $remoteHandler;
    private string $workDirRemote;
    private string $fileMappingPath;
    private array $archives;
    private array $data = [];

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
        $this->data = ['archives' => $this->archives, 'backupDirectory' => self::WORK_DIR_LOCAL];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @uses \CMS\PhpBackup\Step\SendFileStep::_execute()
     * @uses \CMS\PhpBackup\Step\SendFileStep::getUploadedFiles()
     * @uses \CMS\PhpBackup\Step\SendFileStep::uploadFileMapping()
     * @uses \CMS\PhpBackup\Step\SendFileStep::sendArchives()
     *
     * @covers \CMS\PhpBackup\Step\SendFileStep::execute()
     */
    public function testCreateBaseDir()
    {
        FileHelper::deleteDirectory($this->workDirRemote);
        self::assertDirectoryDoesNotExist($this->workDirRemote);

        $step = new SendFileStep($this->remoteHandler);
        $step->setData($this->data);
        $step->execute();

        self::assertRemoteStorage($this->archives);
    }

    /**
     * @covers \CMS\PhpBackup\Step\SendFileStep::execute()
     */
    public function testReturnValue()
    {
        $step = new SendFileStep($this->remoteHandler);
        $step->setData($this->data);
        $result = $step->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);

        self::assertRemoteStorage($this->archives);
    }

    /**
     * @covers \CMS\PhpBackup\Step\SendFileStep::execute()
     */
    public function testExecuteReinitialization()
    {
        $archives = [];
        foreach ($this->archives as $file => $content) {
            $archives[$file] = $content;
            $this->data['archives'] = $archives;

            $step = new SendFileStep($this->remoteHandler);
            $step->setData($this->data);
            $step->execute();

            self::assertRemoteStorage($archives);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Step\SendFileStep::execute()
     */
    public function testReentryFileMappingDoesMatch()
    {
        $files = [TEST_FIXTURES_FILE_1, TEST_FIXTURES_FILE_2];
        $ts = $this->setupRemoteStorage($files);

        $step = new SendFileStep($this->remoteHandler);
        $step->setData($this->data);
        $step->execute();

        self::assertRemoteStorage($this->archives);

        foreach ($ts as $path => $fileTime) {
            self::assertSame($fileTime, filemtime($path), "File {$path} was modified.");
        }
    }

    /**
     * @covers \CMS\PhpBackup\Step\SendFileStep::execute()
     */
    public function testReentryFileMappingDoesNotMatch()
    {
        $filesUploaded = [TEST_FIXTURES_FILE_1, TEST_FIXTURES_FILE_2];
        $filesInFileMapping = [TEST_FIXTURES_FILE_1];
        $ts = $this->setupRemoteStorage($filesUploaded, $filesInFileMapping);

        $step = new SendFileStep($this->remoteHandler);
        $step->setData($this->data);
        $step->execute();

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
     * @covers \CMS\PhpBackup\Step\SendFileStep::updateFileMapping()
     */
    public function testUpdateFileMapping()
    {
        $this->setupRemoteStorage([]);

        $step = new SendFileStep($this->remoteHandler);
        $step->setData($this->data);
        $step->execute();

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
            touch($remoteFile, time() - 5);
            self::assertFileExists($remoteFile);
            $ts[$remoteFile] = filemtime($remoteFile);
        }

        $fileMappingContent = array_fill_keys(array_map('basename', $fileMapping ?: $files), 'content');
        file_put_contents(
            $this->fileMappingPath,
            json_encode($fileMappingContent, JSON_PRETTY_PRINT)
        );

        return $ts;
    }
}
