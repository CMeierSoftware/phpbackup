<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Steps;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\CleanUpStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateBundlesStep
 */
final class CleanUpStepTest extends TestCaseWithAppConfig
{
    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');

        $this->setStepData(['backupFolder' => self::TEST_DIR]);

        FileHelper::makeDir(self::TEST_DIR);
        copy(TEST_FIXTURES_FILE_1, self::TEST_DIR . basename(TEST_FIXTURES_FILE_1));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testDeleteBackupDirectory()
    {
        self::assertDirectoryExists(self::TEST_DIR);

        $step = new CleanUpStep($this->config);

        $result = $step->execute();
        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);
        self::assertSame('Backup process done.', $result->returnValue);

        self::assertDirectoryDoesNotExist(self::TEST_DIR);
    }

    public function testCleanStepData()
    {
        $step = new CleanUpStep($this->config);

        $result = $step->execute();
        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);
        self::assertSame('Backup process done.', $result->returnValue);

        self::assertSame([], $this->config->readTempData('StepData'));
    }
}
