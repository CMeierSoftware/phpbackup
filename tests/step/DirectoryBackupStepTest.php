<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\DirectoryBackupStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateDirectoryBackupStep
 */
final class CreateDirectoryBackupStepTest extends TestCaseWithAppConfig
{
    private array $oneBundle = [];
    private array $bundles = [];
    private array $bundlesResult = [];

    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid', TEST_FIXTURES_FILE_DIR);

        $this->oneBundle = [basename(TEST_FIXTURES_FILE_1), basename(TEST_FIXTURES_FILE_2)];
        $this->bundles = array_fill(0, 5, $this->oneBundle);
        $this->bundlesResult = $this->bundles;

        $this->setStepData(['bundles' => $this->bundles, 'backupFolder' => self::TEST_DIR]);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEMP_DIR);
        parent::tearDown();
    }

    public function testCreateBackupFolder()
    {
        self::assertDirectoryDoesNotExist(self::TEST_DIR);

        $this->setStepData(['bundles' => $this->bundles, 'backupFolder' => self::TEST_DIR]);

        $step = new DirectoryBackupStep($this->config, 0);

        $result = $step->execute();

        self::assertStepResult(true, $result);
    }

    public function testFirstStep()
    {
        $archivesResult = [
            'archive_part_0.zip' => $this->oneBundle,
        ];


        $step = new DirectoryBackupStep($this->config, 0);

        $result = $step->execute();

        self::assertStepResult(true, $result);

        $stepData = $this->config->readTempData('StepData');
        self::assertSame($archivesResult, $stepData['archives']);
        self::assertSame($this->bundlesResult, $stepData['bundles']);
    }

    private static function assertStepResult(bool $expectedRepeat, mixed $actually)
    {
        self::assertInstanceOf(StepResult::class, $actually);
        self::assertSame($expectedRepeat, $actually->repeat);

        self::assertFileExists($actually->returnValue);
        // the encryption is at least 84 bytes
        self::assertGreaterThan(85, filesize($actually->returnValue));
    }

    public function testAllSteps()
    {
        $count = count($this->bundles);
        $archivesResult = [];

        for ($i = 0; $i < $count; ++$i) {
            $step = new DirectoryBackupStep($this->config, 0);
            $archivesResult["archive_part_{$i}.zip"] = $this->oneBundle;
            $result = $step->execute();

            self::assertStepResult(count($archivesResult) < $count, $result);

            $stepData = $this->config->readTempData('StepData');
            self::assertSame($archivesResult, $stepData['archives']);
            self::assertSame($this->bundlesResult, $stepData['bundles']);
        }
    }

    public function testExecuteMissingBackupFolder()
    {
        $this->setStepData(['bundles' => 'some value']);
        $step = new DirectoryBackupStep($this->config);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: backupFolder');
        $step->execute();
    }

    public function testExecuteMissingBundle()
    {
        $this->setStepData(['backupFolder' => 'some value']);
        $step = new DirectoryBackupStep($this->config);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: bundles');
        $step->execute();
    }
}
