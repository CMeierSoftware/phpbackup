<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\BackupDirectoryStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateDirectoryBackupStep
 */
final class BackupDirectoryStepTest extends TestCaseWithAppConfig
{
    private array $oneBundle = [];
    private array $bundlesResult = [];
    private array $data = [];

    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');

        $this->oneBundle = [basename(TEST_FIXTURES_FILE_1), basename(TEST_FIXTURES_FILE_2)];

        $this->data = ['bundles' => array_fill(0, 5, $this->oneBundle), 'backupDirectory' => self::TEST_DIR];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::deleteDirectory(TEMP_DIR);
    }

    public function testTestResult()
    {
        $step = new BackupDirectoryStep(null);
        $step->setData($this->data);
        $result = $step->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);
    }

    public function testCreateBackupFolder()
    {
        self::assertDirectoryDoesNotExist(self::TEST_DIR);

        $step = new BackupDirectoryStep(null);
        $step->setData($this->data);
        $step->execute();

        self::assertDirectoryExists(self::TEST_DIR);
    }

    public function testBackupAll()
    {
        $archivesResult = [
            'archive_part_0.zip',
            'archive_part_1.zip',
            'archive_part_2.zip',
            'archive_part_3.zip',
            'archive_part_4.zip',
        ];

        $step = new BackupDirectoryStep(null);
        $step->setData($this->data);
        $step->execute();

        $this->assertData($archivesResult);
    }

    public function testReentryStep()
    {
        $existingArchives = ['archive_part_0.zip', 'archive_part_1.zip'];

        $newArchives = ['archive_part_2.zip', 'archive_part_3.zip', 'archive_part_4.zip'];

        $this->data['archives'] = array_fill_keys($existingArchives, $this->oneBundle);

        $ts = [];
        FileHelper::makeDir(self::TEST_DIR);
        foreach ($existingArchives as $file) {
            $filePath = self::TEST_DIR . $file;
            copy(TEST_FIXTURES_FILE_1, $filePath);
            self::assertFileExists($filePath);
            touch($filePath, time() - 5);
            $ts[$file] = filemtime($filePath);
        }

        $step = new BackupDirectoryStep(null);
        $step->setData($this->data);
        $step->execute();

        $this->assertData(array_merge($existingArchives, $newArchives));

        foreach ($ts as $file => $fileTime) {
            self::assertSame($fileTime, filemtime(self::TEST_DIR . $file), "File {$file} was modified.");
        }
    }

    /**
     * @covers \CMS\PhpBackup\Step\DirectoryBackupStep::execute()
     *
     * @uses \CMS\PhpBackup\Step\BackupDirectoryStep::setData()
     *
     * @dataProvider provideLoadDataMissingDataCases
     */
    public function testLoadDataMissingData(array $keysToSet, string $missingKey)
    {
        $data = array_fill_keys($keysToSet, 'some value');
        $step = new BackupDirectoryStep(null);
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

    /**
     * Asserts that the step data matches the expected archives & bundle result in the DirectoryBackupStepTest.
     *
     * @param array $archivesResult the expected archives result, where keys are archive filenames and values are bundles
     */
    private function assertData(array $archivesResult)
    {
        $archives = array_combine($archivesResult, array_fill(0, count($archivesResult), $this->oneBundle));
        self::assertSame($archives, $this->data['archives']);

        self::assertSame(array_fill(0, count($archivesResult), $this->oneBundle), $this->data['bundles']);

        foreach ($archivesResult as $file) {
            $filePath = self::TEST_DIR . $file;
            self::assertFileExists($filePath);
            // the encryption is at least 84 bytes
            self::assertGreaterThan(85, filesize($filePath));
        }
    }
}
