<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\App;

use CMS\PhpBackup\App\BackupRunner;
use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Core\LogLevel;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\CleanUpStep;
use CMS\PhpBackup\Step\CreateBundlesStep;
use CMS\PhpBackup\Step\DatabaseBackupStep;
use CMS\PhpBackup\Step\DirectoryBackupStep;
use CMS\PhpBackup\Step\Remote\DeleteOldFilesStep;
use CMS\PhpBackup\Step\Remote\SendFileStep;
use CMS\PhpBackup\Step\StepConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\App\BackupRunner
 */
final class BackupRunnerTest extends TestCase
{
    protected const CONFIG_FILE = CONFIG_DIR . 'app.xml';
    protected const CONFIG_TEMP_DIR = CONFIG_DIR . 'temp_app' . DIRECTORY_SEPARATOR;
    protected const TEST_DIR = TEST_WORK_DIR;

    protected AppConfig $config;

    protected function setUp(): void
    {
        copy(TEST_FIXTURES_CONFIG_DIR . 'config_full_valid.xml', self::CONFIG_FILE);
        self::assertFileExists(self::CONFIG_FILE);

        $this->config = AppConfig::loadAppConfig('app');
    }

    protected function tearDown(): void
    {
        FileLogger::getInstance()->deactivateEchoLogs();
        FileLogger::getInstance()->setLogLevel(LogLevel::OFF);

        FileHelper::deleteDirectory(self::TEST_DIR);
        FileHelper::deleteDirectory(self::CONFIG_TEMP_DIR);

        FileHelper::deleteFile(self::CONFIG_FILE);
        parent::tearDown();
    }

    public function testSteps()
    {
        $expectedSteps = [
            new StepConfig(CreateBundlesStep::class, 5 * 24 * 60 * 60),
            new StepConfig(DirectoryBackupStep::class),
            new StepConfig(DatabaseBackupStep::class),
            new StepConfig(SendFileStep::class),
            new StepConfig(SendFileStep::class),
            new StepConfig(DeleteOldFilesStep::class),
            new StepConfig(DeleteOldFilesStep::class),
            new StepConfig(CleanUpStep::class),
        ];

        $runner = new BackupRunner($this->config);
        FileLogger::getInstance()->deactivateEchoLogs();
        FileLogger::getInstance()->setLogLevel(LogLevel::OFF);

        $property = new \ReflectionProperty($runner, 'steps');
        self::assertEqualsIgnoringCase($expectedSteps, $property->getValue($runner));
    }
}
