<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Core\LogLevel;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\DatabaseBackupStep;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\DatabaseBackupStep
 */
final class DatabaseBackupStepTest extends TestCase
{
    private const TEST_DIR = ABS_PATH . 'tests\\work\\test_directory\\';
    private const LOG_FILE = self::TEST_DIR . self::class . '.log';

    private const DB_CONFIG = ['host' => 'localhost', 'username' => 'root', 'password' => '', 'dbname' => 'test'];
    private $databaseBackupStep;

    protected function setUp(): void
    {
        FileLogger::getInstance()->setLogFile(self::LOG_FILE);
        FileLogger::getInstance()->setLogLevel(LogLevel::INFO);
        $this->databaseBackupStep = new DatabaseBackupStep(
            self::DB_CONFIG,
            self::TEST_DIR,
            'encryption_key',
            0
        );

        // Create a temporary test directory for the tests
        FileHelper::makeDir(self::TEST_DIR);
        self::assertFileExists(self::TEST_DIR);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Step\DatabaseBackupStep::_execute()
     */
    public function testExecuteWithSuccessfulBackup(): void
    {
        $expected = new StepResult('', false);
        $actual = $this->databaseBackupStep->execute();

        self::assertInstanceOf(StepResult::class, $actual);

        $dtPattern = '/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/';
        self::assertMatchesRegularExpression($dtPattern, basename($actual->returnValue));
        self::assertStringStartsWith(self::TEST_DIR, $actual->returnValue);
        self::assertSame($expected->repeat, $actual->repeat);
        self::assertFileExists($actual->returnValue);
    }
    // Add more test cases as needed
}
