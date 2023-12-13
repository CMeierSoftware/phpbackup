<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Core\LogLevel;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CMS\PhpBackup\Core\FileLogger
 *
 * @internal
 */
class FileLoggerTest extends TestCase
{
    private const WORK_PATH = ABS_PATH . 'tests\\work';
    private const LOG_FILE_PATH = self::WORK_PATH . '\\f.log';

    private FileLogger $logger;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_PATH);
        $this->assertFileExists(self::WORK_PATH);
        // clean the instance of the singleton to make mocking possible
        $ref = new \ReflectionProperty('CMS\PhpBackup\Core\FileLogger', 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $this->logger = FileLogger::getInstance();
        $this->logger->setLogFile(self::LOG_FILE_PATH);
        $this->logger->SetLogLevel(LogLevel::INFO);
    }

    public function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_PATH);
    }

    /**
     * @covers \getInstance()
     */
    public function testGetInstance()
    {
        $instance1 = FileLogger::getInstance();
        $instance2 = FileLogger::getInstance();

        $this->assertInstanceOf(FileLogger::class, $instance1);
        $this->assertInstanceOf(FileLogger::class, $instance2);
        $this->assertEquals($instance1, $instance2);
    }

    /**
     * @covers \setLogLevel()
     */
    public function testSetLogLevel()
    {
        $message = 'This is a message';
        $this->logger->setLogLevel(LogLevel::INFO);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        $this->assertStringStartsWith(LogLevel::toString(LogLevel::INFO), $log_file_content);
        $this->assertStringEndsWith("set log level to INFO\n", $log_file_content);

        $this->logger->Error($message);
        $this->logger->Warning($message);
        $this->logger->Info($message);

        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        $this->assertStringContainsString(LogLevel::toString(LogLevel::ERROR), $log_file_content);
        $this->assertStringContainsString(LogLevel::toString(LogLevel::WARNING), $log_file_content);
        $this->assertStringContainsString(LogLevel::toString(LogLevel::WARNING), $log_file_content);

        $this->logger->SetLogLevel(LogLevel::WARNING);
        // unlink after changing the level, because SetLogLevel will also create a Info
        unlink(self::LOG_FILE_PATH);

        $this->logger->Error($message);
        $this->logger->Warning($message);
        $this->logger->Info($message);
        // Assert that the log entry is written to the file
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        $this->assertStringContainsString(LogLevel::toString(LogLevel::ERROR), $log_file_content);
        $this->assertStringContainsString(LogLevel::toString(LogLevel::WARNING), $log_file_content);
        $this->assertStringNotContainsString(LogLevel::toString(LogLevel::INFO), $log_file_content);
    }

    /**
     * @covers \ActivateEchoLogs()
     */
    public function testActiveEchoLogEntry()
    {
        $errorMessage = 'This is an error message';
        $this->logger->ActivateEchoLogs();
        // Capture output to check if it's echoed when echo_logs is activated
        ob_start();
        $this->logger->error($errorMessage);
        $output = ob_get_clean();
        // Assert that the log entry is written to the file
        $this->assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        $this->assertStringContainsString($errorMessage, $log_file_content);
        $this->assertEquals($output, $log_file_content . '<br>');
    }

    /**
     * @covers \DeactivateEchoLogs()
     */
    public function testDisabledEchoLogEntry()
    {
        $errorMessage = 'This is an error message';

        $this->logger->DeactivateEchoLogs();

        // Capture output to check if it's echoed when echo_logs is activated
        ob_start();
        $this->logger->error($errorMessage);
        $output = ob_get_clean();
        // Assert that the log entry is written to the file
        $this->assertEmpty($output);
        $this->assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        $this->assertStringContainsString($errorMessage, $log_file_content);
    }

    /**
     * @covers \error()
     */
    public function testErrorLogEntry()
    {
        $errorMessage = 'This is an error message';
        $this->logger->error($errorMessage);

        // Assert that the log entry is written to the file
        $this->assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        $this->assertStringStartsWith(LogLevel::toString(LogLevel::ERROR), $log_file_content);
        $this->assertStringEndsWith($errorMessage . "\n", $log_file_content);
    }

    /**
     * @covers \error()
     */
    public function testWarningLogEntry()
    {
        $errorMessage = 'This is a warning message';
        $this->logger->warning($errorMessage);

        // Assert that the log entry is written to the file
        $this->assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        $this->assertStringStartsWith(LogLevel::toString(LogLevel::WARNING), $log_file_content);
        $this->assertStringEndsWith($errorMessage . "\n", $log_file_content);
    }

    /**
     * @covers \error()
     */
    public function testInfoLogEntry()
    {
        $errorMessage = 'This is an error message';
        $this->logger->Info($errorMessage);

        // Assert that the log entry is written to the file
        // $this->assertFileExists(self::LOG_FILE_PATH);
        // $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        // $this->assertStringStartsWith(LogLevel::toString(LogLevel::INFO), $log_file_content);
        // $this->assertStringEndsWith($errorMessage . "\n", $log_file_content);
    }
}
