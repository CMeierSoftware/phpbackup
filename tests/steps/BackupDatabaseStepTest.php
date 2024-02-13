<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\BackupDatabaseStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\DatabaseBackupStep
 */
final class BackupDatabaseStepTest extends TestCaseWithAppConfig
{
    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEMP_DIR);
        parent::tearDown();
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     *
     * @uses \CMS\PhpBackup\Step\BackupDatabaseStep::setData()
     */
    public function testExecuteWithSuccessfulBackup(): void
    {
        $data = ['bundles' => ['something'], 'backupDirectory' => self::TEST_DIR];
        $step = new BackupDatabaseStep(null);
        $step->setData($data);
        $result = $step->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);

        $createdFile = $result->returnValue;

        $dtPattern = '/^backup_database_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql(?:\.[a-zA-Z]{0,3})?$/';
        self::assertMatchesRegularExpression($dtPattern, basename($createdFile));
        self::assertStringStartsWith(self::TEST_DIR, $createdFile);
        self::assertFileExists($createdFile);

        // the encryption is at least 84 bytes
        self::assertGreaterThan(85, filesize($createdFile));

        $archivesResult = [basename($createdFile) => 'Database backup.'];
        self::assertSame($archivesResult, $data['archives']);
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     *
     * @uses \CMS\PhpBackup\Step\BackupDatabaseStep::setData()
     */
    public function testExecuteNoDbInConfig(): void
    {
        $this->setUpAppConfig('config_no_db');
        $step = new BackupDatabaseStep(null);

        $data = ['bundles' => ['something'], 'backupDirectory' => self::TEST_DIR];
        $step->setData($data);
        $actual = $step->execute();

        self::assertFalse($actual->repeat);
        self::assertSame('No database defined. Skip step.', $actual->returnValue);
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::loadData()
     *
     * @dataProvider provideLoadDataMissingDataCases
     */
    public function testLoadDataMissingData(array $keysToSet, string $missingKey)
    {
        $data = array_fill_keys($keysToSet, 'some value');
        $step = new BackupDatabaseStep(null);
        $step->setData($data);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage("Missing required keys: {$missingKey}");
        $step->execute();
    }

    public static function provideLoadDataMissingDataCases(): iterable
    {
        $requiredKeys = ['backupDirectory', 'bundles'];
        $returnValue = [];

        foreach ($requiredKeys as $keyIndex => $key) {
            $rowKeys = array_diff($requiredKeys, [$key]);
            $returnValue[] = [$rowKeys, $key];
        }

        return $returnValue;
    }
}
