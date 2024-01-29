<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

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

        $this->setStepData(['bundles' => $this->bundles, 'backupDirectory' => self::TEST_DIR]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::deleteDirectory(TEMP_DIR);
    }

    public function testCreateBackupFolder()
    {
        self::assertDirectoryDoesNotExist(self::TEST_DIR);

        $step = new DirectoryBackupStep();
        $result = $step->execute();

        self::assertStepResult(false, $result);
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

        $step = new DirectoryBackupStep();
        $result = $step->execute();

        self::assertStepResult(false, $result);
        $this->assertStepData($archivesResult);
    }

    public function testReentryStep()
    {
        $existingArchives = ['archive_part_0.zip', 'archive_part_1.zip'];

        $newArchives = ['archive_part_2.zip', 'archive_part_3.zip', 'archive_part_4.zip'];

        $this->setStepData(
            [
                'bundles' => $this->bundles,
                'backupDirectory' => self::TEST_DIR,
                'archives' => array_fill_keys($existingArchives, $this->oneBundle),
            ]
        );

        $ts = [];
        FileHelper::makeDir(self::TEST_DIR);
        foreach ($existingArchives as $file) {
            $filePath = self::TEST_DIR . $file;
            copy(TEST_FIXTURES_FILE_1, $filePath);
            self::assertFileExists($filePath);
            touch($filePath, time() - 5);
            $ts[$file] = filemtime($filePath);
        }

        $step = new DirectoryBackupStep();
        $result = $step->execute();

        self::assertStepResult(false, $result);
        $this->assertStepData(array_merge($existingArchives, $newArchives));

        foreach ($ts as $file => $fileTime) {
            self::assertSame($fileTime, filemtime(self::TEST_DIR . $file), "File {$file} was modified.");
        }
    }

    public function testExecuteMissingBackupFolder()
    {
        $this->setStepData(['bundles' => 'some value']);
        $step = new DirectoryBackupStep();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Missing required keys: backupDirectory');
        $step->execute();
    }

    public function testExecuteMissingBundle()
    {
        $this->setStepData(['backupDirectory' => 'some value']);
        $step = new DirectoryBackupStep();

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
        $stepData = $this->getStepData();
        self::assertSame($archivesResult, array_keys($stepData['archives']));
        self::assertSame(array_fill(0, count($archivesResult), $this->oneBundle), array_values($stepData['archives']));
        self::assertSame($this->bundlesResult, $stepData['bundles']);

        foreach ($archivesResult as $file) {
            $filePath = self::TEST_DIR . $file;
            self::assertFileExists($filePath);
            // the encryption is at least 84 bytes
            self::assertGreaterThan(85, filesize($filePath));
        }
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
    }
}
