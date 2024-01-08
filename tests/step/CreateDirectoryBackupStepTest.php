<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\CreateDirectoryBackupStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateDirectoryBackupStep
 */
final class CreateDirectoryBackupStepTest extends TestCaseWithAppConfig
{
    private const WORK_DIR_REMOTE_BASE = self::TEST_DIR . 'Remote' . DIRECTORY_SEPARATOR;
    private array $oneBundle = [];

    protected function setUp(): void
    {
        $this->setUpAppConfig(TEST_FIXTURES_FILE_DIR);

        FileHelper::makeDir(self::WORK_DIR_REMOTE_BASE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE_BASE);

        $this->oneBundle = [basename(TEST_FIXTURES_FILE_1), basename(TEST_FIXTURES_FILE_2)];
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEMP_DIR);
        parent::tearDown();
    }

    public function testFirstStep()
    {
        $bundles = array_fill(0, 5, $this->oneBundle);

        $bundlesResult = array_fill(0, 5, $this->oneBundle);
        $archivesResult = [
            'archive_part_0.zip' => $this->oneBundle,
        ];

        $this->setStepData(['bundles' => $bundles, 'backupFolder' => self::TEST_DIR]);

        $step = new CreateDirectoryBackupStep($this->config, 0);

        $result = $step->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertTrue($result->repeat);

        self::assertFileExists($result->returnValue);

        $stepData = $this->config->readTempData('StepData');
        self::assertSame($archivesResult, $stepData['archives']);
        self::assertSame($bundlesResult, $stepData['bundles']);
    }

    public function testAllSteps()
    {
        $count = 5;

        $bundles = array_fill(0, $count, $this->oneBundle);
        $archives = [];

        $bundlesResult = array_fill(0, $count, $this->oneBundle);
        $archivesResult = [];

        $step = new CreateDirectoryBackupStep(TEST_FIXTURES_FILE_DIR, TEST_WORK_DIR, 'key', $bundles, $archives, 0);

        for ($i = 0; $i < $count; ++$i) {
            $archivesResult["archive_part_{$i}.zip"] = $this->oneBundle;
            $result = $step->execute();

            self::assertInstanceOf(StepResult::class, $result);
            self::assertSame(count($archivesResult) < $count, $result->repeat);

            self::assertFileExists($result->returnValue);

            self::assertSame($archivesResult, $archives);
            self::assertSame($bundlesResult, $bundles);
        }
    }

    public function testExecuteMissingBackupFolder()
    {
        $this->setStepData(['bundles' => 'some value']);
        $step = new CreateDirectoryBackupStep($this->config);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: backupFolder');
        $step->execute();
    }

    public function testExecuteMissingBundle()
    {
        $this->setStepData(['backupFolder' => 'some value']);
        $step = new CreateDirectoryBackupStep($this->config);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: bundles');
        $step->execute();
    }
}
