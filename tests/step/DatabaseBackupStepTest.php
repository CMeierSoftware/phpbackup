<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

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

        $this->setStepData(['bundles' => ['something'], 'backupDirectory' => self::TEST_DIR]);

        $this->databaseBackupStep = new DatabaseBackupStep();
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
        $actual = $this->databaseBackupStep->execute();

        self::assertInstanceOf(StepResult::class, $actual);

        $dtPattern = '/^backup_database_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql(?:\.[a-zA-Z]{0,3})?$/';
        self::assertMatchesRegularExpression($dtPattern, basename($actual->returnValue));
        self::assertStringStartsWith(self::TEST_DIR, $actual->returnValue);
        self::assertFalse($actual->repeat);
        self::assertFileExists($actual->returnValue);

        // the encryption is at least 84 bytes
        self::assertGreaterThan(85, filesize($actual->returnValue));

        $stepData = $this->getStepData();
        $archivesResult = [basename($actual->returnValue) => 'Database backup.'];
        self::assertSame($archivesResult, $stepData['archives']);
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     */
    public function testExecuteNoDbInConfig(): void
    {
        $this->setUpAppConfig('config_no_db');
        $this->databaseBackupStep = new DatabaseBackupStep();

        $actual = $this->databaseBackupStep->execute();

        self::assertFalse($actual->repeat);
        self::assertSame('No database defined. Skip step.', $actual->returnValue);
    }

    public function testExecuteMissingBackupDirectory()
    {
        $this->setStepData(['bundles' => 'some value']);
        $step = new DatabaseBackupStep();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: backupDirectory');
        $step->execute();
    }

    public function testExecuteMissingBundle()
    {
        $this->setStepData(['backupDirectory' => 'some value']);
        $step = new DatabaseBackupStep();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: bundles');
        $step->execute();
    }
}
