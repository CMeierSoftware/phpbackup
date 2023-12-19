<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Core\LogLevel;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\FileLogger
 */
final class FileLoggerTest extends TestCase
{
    private const WORK_PATH = ABS_PATH . 'tests\\work';
    private const LOG_FILE_PATH = self::WORK_PATH . '\\f.log';

    private FileLogger $logger;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_PATH);
        self::assertFileExists(self::WORK_PATH);
        // clean the instance of the singleton to make mocking possible
        $ref = new \ReflectionProperty('CMS\PhpBackup\Core\FileLogger', 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $this->logger = FileLogger::getInstance();
        $this->logger->setLogFile(self::LOG_FILE_PATH);
        $this->logger->setLogLevel(LogLevel::INFO);

    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_PATH);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileLogger::getInstance()
     */
    public function testGetInstance()
    {
        $instance1 = FileLogger::getInstance();
        $instance2 = FileLogger::getInstance();

        self::assertInstanceOf(FileLogger::class, $instance1);
        self::assertInstanceOf(FileLogger::class, $instance2);
        self::assertSame($instance1, $instance2);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileLogger::setLogLevel()
     *
     * @uses \CMS\PhpBackup\Core\FileLogger::Error()
     * @uses \CMS\PhpBackup\Core\FileLogger::Warning()
     * @uses \CMS\PhpBackup\Core\FileLogger::Info()
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     * @uses \CMS\PhpBackup\Core\FileLogger::getInstance()
     */
    public function testSetLogLevel()
    {
        $message = 'This is a message';
        $this->logger->setLogLevel(LogLevel::INFO);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::INFO->name, $log_file_content);
        self::assertStringEndsWith("set log level to INFO\n", $log_file_content);

        $this->logger->error($message);
        $this->logger->warning($message);
        $this->logger->info($message);

        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringContainsString(LogLevel::ERROR->name, $log_file_content);
        self::assertStringContainsString(LogLevel::WARNING->name, $log_file_content);
        self::assertStringContainsString(LogLevel::WARNING->name, $log_file_content);

        $this->logger->setLogLevel(LogLevel::WARNING);
        // unlink after changing the level, because SetLogLevel will also create a Info
        unlink(self::LOG_FILE_PATH);

        $this->logger->error($message);
        $this->logger->warning($message);
        $this->logger->info($message);
        // Assert that the log entry is written to the file
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringContainsString(LogLevel::ERROR->name, $log_file_content);
        self::assertStringContainsString(LogLevel::WARNING->name, $log_file_content);
        self::assertStringNotContainsString(LogLevel::INFO->name, $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileLogger::ActivateEchoLogs()
     *
     * @uses \CMS\PhpBackup\Core\FileLogger::Error()
     * @uses \CMS\PhpBackup\Core\FileLogger::getInstance()
     */
    public function testActiveEchoLogEntry()
    {
        $errorMessage = 'This is an error message';
        $this->logger->activateEchoLogs();
        // Capture output to check if it's echoed when echo_logs is activated
        ob_start();
        $this->logger->error($errorMessage);
        $output = ob_get_clean();
        $this->logger->deactivateEchoLogs();
        // Assert that the log entry is written to the file
        self::assertFileExists(self::LOG_FILE_PATH);
        $logFileContent = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringContainsString($errorMessage, $logFileContent);
        self::assertSame($output, $logFileContent . '<br>');
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileLogger::DeactivateEchoLogs()
     *
     * @uses \CMS\PhpBackup\Core\FileLogger::Error()
     * @uses \CMS\PhpBackup\Core\FileLogger::getInstance()
     */
    public function testDisabledEchoLogEntry()
    {
        $errorMessage = 'This is an error message';

        $this->logger->deactivateEchoLogs();

        // Capture output to check if it's echoed when echo_logs is activated
        ob_start();
        $this->logger->error($errorMessage);
        $output = ob_get_clean();
        // Assert that the log entry is written to the file
        self::assertEmpty($output);
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringContainsString($errorMessage, $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileLogger::Error()
     *
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     * @uses \CMS\PhpBackup\Core\FileLogger::getInstance()
     */
    public function testErrorLogEntry()
    {
        $errorMessage = 'This is an error message';
        $this->logger->error($errorMessage);

        // Assert that the log entry is written to the file
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::ERROR->name, $log_file_content);
        self::assertStringEndsWith($errorMessage . "\n", $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileLogger::Warning()
     *
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     * @uses \CMS\PhpBackup\Core\FileLogger::getInstance()
     */
    public function testWarningLogEntry()
    {
        $errorMessage = 'This is a warning message';
        $this->logger->warning($errorMessage);

        // Assert that the log entry is written to the file
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::WARNING->name, $log_file_content);
        self::assertStringEndsWith($errorMessage . "\n", $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileLogger::Info()
     *
     * @uses \CMS\PhpBackup\Core\FileLogger::getInstance()
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testInfoLogEntry()
    {
        $errorMessage = 'This is an error message';
        $this->logger->info($errorMessage);

        // Assert that the log entry is written to the file
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::INFO->name, $log_file_content);
        self::assertStringEndsWith($errorMessage . "\n", $log_file_content);
    }
}
