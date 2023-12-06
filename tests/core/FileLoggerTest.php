<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Core\LogLevel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CMS\PhpBackup\Core\FileLogger
 */
class FileLoggerTest extends TestCase
{
    private FileLogger $logger;
    private string $log_file_path = __DIR__ . '/../work/f.log';

    protected function setUp(): void
    {
        // clean the instance of the singleton to make mocking possible
        $ref = new \ReflectionProperty('CMS\PhpBackup\Core\FileLogger', 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $this->logger = FileLogger::getInstance();
        $this->logger->setLogFile($this->log_file_path);
        $this->logger->SetLogLevel(LogLevel::INFO);
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
        try {
            $this->logger->setLogLevel(LogLevel::INFO);
            $log_file_content = file_get_contents($this->log_file_path);
            $this->assertStringStartsWith(LogLevel::toString(LogLevel::INFO), $log_file_content);
            $this->assertStringEndsWith("set log level to INFO\n", $log_file_content);

            $this->logger->Error($message);
            $this->logger->Warning($message);
            $this->logger->Info($message);

            $log_file_content = file_get_contents($this->log_file_path);
            $this->assertStringContainsString(LogLevel::toString(LogLevel::ERROR), $log_file_content);
            $this->assertStringContainsString(LogLevel::toString(LogLevel::WARNING), $log_file_content);
            $this->assertStringContainsString(LogLevel::toString(LogLevel::WARNING), $log_file_content);

            $this->logger->SetLogLevel(LogLevel::WARNING);
            // unlink after changing the level, because SetLogLevel will also create a Info
            unlink($this->log_file_path);

            $this->logger->Error($message);
            $this->logger->Warning($message);
            $this->logger->Info($message);
            // Assert that the log entry is written to the file
            $log_file_content = file_get_contents($this->log_file_path);
            $this->assertStringContainsString(LogLevel::toString(LogLevel::ERROR), $log_file_content);
            $this->assertStringContainsString(LogLevel::toString(LogLevel::WARNING), $log_file_content);
            $this->assertStringNotContainsString(LogLevel::toString(LogLevel::INFO), $log_file_content);
        } finally {
            unlink($this->log_file_path);
        }
    }

    /**
     * @covers \ActivateEchoLogs()
     */
    public function testActiveEchoLogEntry()
    {
        $errorMessage = 'This is an error message';
        try {
            $this->logger->ActivateEchoLogs();
            // Capture output to check if it's echoed when echo_logs is activated
            ob_start();
            $this->logger->error($errorMessage);
            $output = ob_get_clean();
            // Assert that the log entry is written to the file
            $log_file_content = file_get_contents($this->log_file_path);
            $this->assertStringContainsString($errorMessage, $log_file_content);
            $this->assertEquals($output, $log_file_content . '<br>');
        } finally {
            unlink($this->log_file_path);
        }
    }

    /**
     * @covers \DeactivateEchoLogs()
     */
    public function testDisabledEchoLogEntry()
    {
        $errorMessage = 'This is an error message';

        $this->logger->DeactivateEchoLogs();

        try {
            // Capture output to check if it's echoed when echo_logs is activated
            ob_start();
            $this->logger->error($errorMessage);
            $output = ob_get_clean();
            // Assert that the log entry is written to the file
            $this->assertEmpty($output);
            $log_file_content = file_get_contents($this->log_file_path);
            $this->assertStringContainsString($errorMessage, $log_file_content);
        } finally {
            unlink($this->log_file_path);
        }
    }

    /**
     * @covers \error()
     */
    public function testErrorLogEntry()
    {
        $errorMessage = 'This is an error message';
        try {
            $this->logger->error($errorMessage);

            // Assert that the log entry is written to the file
            $log_file_content = file_get_contents($this->log_file_path);
            $this->assertStringStartsWith(LogLevel::toString(LogLevel::ERROR), $log_file_content);
            $this->assertStringEndsWith($errorMessage . "\n", $log_file_content);
        } finally {
            unlink($this->log_file_path);
        }
    }

    /**
     * @covers \error()
     */
    public function testWarningLogEntry()
    {
        $errorMessage = 'This is a warning message';
        try {
            $this->logger->warning($errorMessage);

            // Assert that the log entry is written to the file
            $log_file_content = file_get_contents($this->log_file_path);
            $this->assertStringStartsWith(LogLevel::toString(LogLevel::WARNING), $log_file_content);
            $this->assertStringEndsWith($errorMessage . "\n", $log_file_content);
        } finally {
            unlink($this->log_file_path);
        }
    }

    /**
     * @covers \error()
     */
    public function testInfoLogEntry()
    {
        $errorMessage = 'This is an error message';
        try {
            $this->logger->Info($errorMessage);

            // Assert that the log entry is written to the file
            $log_file_content = file_get_contents($this->log_file_path);
            $this->assertStringStartsWith(LogLevel::toString(LogLevel::INFO), $log_file_content);
            $this->assertStringEndsWith($errorMessage . "\n", $log_file_content);
        } finally {
            unlink($this->log_file_path);
        }
    }
}
