<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Steps;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\DirectoryBackupStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateDirectoryBackupStep
 */
final class DirectoryBackupStepTest extends TestCaseWithAppConfig
{
    private array $oneBundle = [];
    private array $bundles = [];
    private array $bundlesResult = [];

    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');

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

        $step = new DirectoryBackupStep($this->config);
        $result = $step->execute();

        self::assertStepResult(true, $result);
    }

    public function testFirstStep()
    {
        $archivesResult = [
            'archive_part_0.zip' => $this->oneBundle,
        ];

        $step = new DirectoryBackupStep($this->config);
        $result = $step->execute();

        self::assertStepResult(true, $result);
        $this->assertStepData($archivesResult);
    }

    public function testAllSteps()
    {
        $count = count($this->bundles);
        $archivesResult = [];

        for ($i = 0; $i < $count; ++$i) {
            $step = new DirectoryBackupStep($this->config);
            $archivesResult["archive_part_{$i}.zip"] = $this->oneBundle;
            $result = $step->execute();

            self::assertStepResult(count($archivesResult) < $count, $result);

            $this->assertStepData($archivesResult);
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

    /**
     * Asserts that the step data matches the expected archives & bundle result in the DirectoryBackupStepTest.
     *
     * @param array $archivesResult the expected archives result, where keys are archive filenames and values are bundles
     */
    private function assertStepData(array $archivesResult)
    {
        $stepData = $this->config->readTempData('StepData');
        self::assertSame($archivesResult, $stepData['archives']);
        self::assertSame($this->bundlesResult, $stepData['bundles']);
    }

    /**
     * Asserts the result of a step in the directory backup process.
     *
     * @param bool $expectedRepeat the expected value for the 'repeat' property in the StepResult
     */
    private static function assertStepResult(bool $expectedRepeat, mixed $actually)
    {
        self::assertInstanceOf(StepResult::class, $actually);
        self::assertSame($expectedRepeat, $actually->repeat);

        self::assertFileExists($actually->returnValue);
        // the encryption is at least 84 bytes
        self::assertGreaterThan(85, filesize($actually->returnValue));
    }
}
