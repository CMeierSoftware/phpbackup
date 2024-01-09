<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\RemoteDeleteOldFilesStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\DeleteOldFilesRemoteStep
 */
final class RemoteDeleteOldFilesStepTest extends TestCaseWithAppConfig
{
    private const WORK_DIR_REMOTE_BASE = self::TEST_DIR . 'Remote' . DIRECTORY_SEPARATOR;
    private Local $remoteHandler;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_REMOTE_BASE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE_BASE);

        $this->remoteHandler = new Local(self::WORK_DIR_REMOTE_BASE);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEST_WORK_DIR);
        parent::tearDown();
    }

    public function testDeleteOldFilesDaysSuccess()
    {
        $countToKeep = 7;

        list($expiredDirs, $validDirs) = self::setupRemoteStorage($countToKeep);


        $this->setUpAppConfig(
            'config_full_valid', 
            [
                ['tag' => 'keepBackupDays', 'value' => (string) 0],
                ['tag' => 'keepBackupAmount', 'value' => (string) $countToKeep],
            ]
        );
        $sendRemoteStep = new RemoteDeleteOldFilesStep($this->remoteHandler, $this->config);

        $result = $sendRemoteStep->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);

        $this->assertDirectoriesExist($validDirs);
        $this->assertDirectoriesDoNotExist($expiredDirs);
    }

    public function testDeleteOldFilesAmountSuccess()
    {
        $countToKeep = 7;

        list($expiredDirs, $validDirs) = self::setupRemoteStorage($countToKeep);

        $this->setUpAppConfig(
            'config_full_valid', 
            [
                ['tag' => 'keepBackupDays', 'value' => (string) $countToKeep],
                ['tag' => 'keepBackupAmount', 'value' => (string) 0],
            ]
        );

        $sendRemoteStep = new RemoteDeleteOldFilesStep($this->remoteHandler, $this->config);

        $result = $sendRemoteStep->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);

        $this->assertDirectoriesExist($validDirs);
        $this->assertDirectoriesDoNotExist($expiredDirs);
    }

    private function assertDirectoriesExist(array $dirs): void
    {
        foreach ($dirs as $dir) {
            self::assertDirectoryExists($dir);
            self::assertFileExists($dir . DIRECTORY_SEPARATOR . 'f1.txt');
        }
    }

    private function assertDirectoriesDoNotExist(array $dirs): void
    {
        foreach ($dirs as $dir) {
            self::assertDirectoryDoesNotExist($dir);
            self::assertFileDoesNotExist($dir . DIRECTORY_SEPARATOR . 'f1.txt');
        }
    }

    private static function setupRemoteStorage(int $numberOfDays): array
    {
        $expiredFiles = [];
        $validFiles = [];

        for ($i = 0; $i < 2 * $numberOfDays; ++$i) {
            $timestamp = strtotime("-{$i} days") - 120;
            $fileName = 'backup_' . date('Y-m-d_H-i-s', $timestamp);
            if ($i < $numberOfDays) {
                $validFiles[] = self::WORK_DIR_REMOTE_BASE . $fileName;
            } else {
                $expiredFiles[] = self::WORK_DIR_REMOTE_BASE . $fileName;
            }
        }

        foreach (array_merge($expiredFiles, $validFiles) as $dir) {
            FileHelper::makeDir($dir);
            $fn = $dir . DIRECTORY_SEPARATOR . 'f1.txt';
            copy(TEST_FIXTURES_FILE_1, $fn);

            self::assertFileExists($fn);
        }

        return [$expiredFiles, $validFiles];
    }
}
