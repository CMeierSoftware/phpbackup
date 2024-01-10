<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Steps;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\AbstractRemoteDeleteOldFilesStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\DeleteOldFilesRemoteStep
 */
final class AbstractRemoteDeleteOldFilesStepTest extends TestCaseWithAppConfig
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
        parent::tearDown();
    }

    public function testDeleteOldFilesDaysSuccess()
    {
        $this->executeDeleteOldFilesTest(7, 0);
    }

    public function testDeleteOldFilesAmountSuccess()
    {
        $this->executeDeleteOldFilesTest(0, 7);
    }

    private function executeDeleteOldFilesTest($keepDays, $keepAmount)
    {
        list($expiredDirs, $validDirs) = self::setupRemoteStorage(7);

        $this->setUpAppConfig(
            'config_full_valid',
            [
                ['tag' => 'keepBackupDays', 'value' => (string) $keepDays],
                ['tag' => 'keepBackupAmount', 'value' => (string) $keepAmount],
            ]
        );

        $mockBuilder = $this->getMockBuilder(AbstractRemoteDeleteOldFilesStep::class);
        $mockBuilder->setConstructorArgs([$this->remoteHandler, $this->config]);

        $sendRemoteStep = $mockBuilder->getMockForAbstractClass();

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
