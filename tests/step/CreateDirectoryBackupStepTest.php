<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\CreateDirectoryBackupStep;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateDirectoryBackupStep
 */
final class CreateDirectoryBackupStepTest extends TestCase
{
    private const WORK_DIR_REMOTE_BASE = TEST_WORK_DIR . 'Remote' . DIRECTORY_SEPARATOR;
    private array $oneBundle = [];

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_REMOTE_BASE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE_BASE);

        $this->oneBundle = [basename(TEST_FIXTURES_FILE_1), basename(TEST_FIXTURES_FILE_2)];
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEST_WORK_DIR);
    }

    public function testFirstStep()
    {
        $bundles = array_fill(0, 5, $this->oneBundle);
        $archives = [];

        $bundlesResult = array_fill(0, 5, $this->oneBundle);
        $archivesResult = [
            'archive_part_0.zip' => $this->oneBundle,
        ];

        $step = new CreateDirectoryBackupStep(TEST_FIXTURES_FILE_DIR, TEST_WORK_DIR, 'key', $bundles, $archives, 0);

        $result = $step->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertTrue($result->repeat);

        self::assertFileExists($result->returnValue);

        self::assertSame($archivesResult, $archives);
        self::assertSame($bundlesResult, $bundles);
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
}
