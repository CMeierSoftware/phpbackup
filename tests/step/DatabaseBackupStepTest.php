<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Steps;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\DatabaseBackupStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\DatabaseBackupStep
 */
final class DatabaseBackupStepTest extends TestCaseWithAppConfig
{
    private $databaseBackupStep;

    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');

        $this->setStepData(['bundles' => ['something'], 'backupFolder' => self::TEST_DIR]);

        $this->databaseBackupStep = new DatabaseBackupStep($this->config);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEMP_DIR);
        parent::tearDown();
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     */
    public function testExecuteWithSuccessfulBackup(): void
    {
        $expected = new StepResult('unknown', false);
        $actual = $this->databaseBackupStep->execute();

        self::assertInstanceOf(StepResult::class, $actual);

        // $dtPattern = '/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/';
        $dtPattern = '/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/';
        self::assertMatchesRegularExpression($dtPattern, basename($actual->returnValue));
        self::assertStringStartsWith(self::TEST_DIR, $actual->returnValue);
        self::assertSame($expected->repeat, $actual->repeat);
        self::assertFileExists($actual->returnValue);

        // the encryption is at least 84 bytes
        self::assertGreaterThan(85, filesize($actual->returnValue));

        $stepData = $this->config->readTempData('StepData');
        $archivesResult = [basename($actual->returnValue) => 'Database backup.'];
        self::assertSame($archivesResult, $stepData['archives']);
    }

    public function testExecuteMissingBackupFolder()
    {
        $this->setStepData(['bundles' => 'some value']);
        $step = new DatabaseBackupStep($this->config);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: backupFolder');
        $step->execute();
    }

    public function testExecuteMissingBundle()
    {
        $this->setStepData(['backupFolder' => 'some value']);
        $step = new DatabaseBackupStep($this->config);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: bundles');
        $step->execute();
    }
}
