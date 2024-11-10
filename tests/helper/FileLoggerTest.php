<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Helper;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Helper\FileLogger;
use CMS\PhpBackup\Helper\LogLevel;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Helper\FileLogger
 */
final class FileLoggerTest extends TestCase
{
    private const LOG_FILE_PATH = TEST_WORK_DIR . DIRECTORY_SEPARATOR . 'f.log';

    private FileLogger $logger;

    protected function setUp(): void
    {
        FileHelper::makeDir(TEST_WORK_DIR);
        self::assertDirectoryExists(TEST_WORK_DIR);

        // clean the instance of the singleton to make mocking possible
        $ref = new \ReflectionProperty(FileLogger::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $this->logger = FileLogger::getInstance();
        $this->logger->setLogFile(self::LOG_FILE_PATH);
        $this->logger->setLogLevel(LogLevel::ERROR);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEST_WORK_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileLogger::getInstance()
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
     * @covers \CMS\PhpBackup\Helper\FileLogger::setLogLevel()
     *
     * @uses \CMS\PhpBackup\Helper\FileLogger::Error()
     * @uses \CMS\PhpBackup\Helper\FileLogger::Warning()
     * @uses \CMS\PhpBackup\Helper\FileLogger::Info()
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     * @uses \CMS\PhpBackup\Helper\FileLogger::getInstance()
     */
    public function testSetLogLevel()
    {
        $message = 'This is an error message for test ' . uniqid(__FUNCTION__);
        $this->logger->setLogLevel(LogLevel::DEBUG);

        $this->logger->error($message);
        $this->logger->warning($message);
        $this->logger->info($message);

        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringContainsString(LogLevel::ERROR->name, $log_file_content);
        self::assertStringContainsString(LogLevel::WARNING->name, $log_file_content);
        self::assertStringContainsString(LogLevel::WARNING->name, $log_file_content);

        $this->logger->setLogLevel(LogLevel::WARNING);
        // unlink after changing the level, because SetLogLevel will also create a Info
        FileHelper::deleteFile(self::LOG_FILE_PATH);

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
     * @covers \CMS\PhpBackup\Helper\FileLogger::setLogLevel()
     *
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     * @uses \CMS\PhpBackup\Helper\FileLogger::getInstance()
     */
    public function testSetLogLevelMessage()
    {
        $this->logger->setLogLevel(LogLevel::DEBUG);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::DEBUG->name, $log_file_content);
        self::assertStringEndsWith("set log level to DEBUG\n", $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileLogger::ActivateEchoLogs()
     *
     * @uses \CMS\PhpBackup\Helper\FileLogger::Error()
     * @uses \CMS\PhpBackup\Helper\FileLogger::getInstance()
     */
    public function testActiveEchoLogEntry()
    {
        $message = 'This is an error message for test ' . uniqid(__FUNCTION__);

        $this->logger->activateEchoLogs();
        ob_start();
        $this->logger->error($message);
        $output = ob_get_clean();
        $this->logger->deactivateEchoLogs();

        self::assertFileExists(self::LOG_FILE_PATH);
        $logFileContent = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringContainsString($message, $logFileContent);
        self::assertSame($output, $logFileContent . '<br>');
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileLogger::DeactivateEchoLogs()
     *
     * @uses \CMS\PhpBackup\Helper\FileLogger::Error()
     * @uses \CMS\PhpBackup\Helper\FileLogger::getInstance()
     */
    public function testDisabledEchoLogEntry()
    {
        $message = 'This is an error message for test ' . uniqid(__FUNCTION__);

        $this->logger->deactivateEchoLogs();

        // Capture output to check if it's echoed when echo_logs is activated
        ob_start();
        $this->logger->error($message);
        $output = ob_get_clean();
        // Assert that the log entry is written to the file
        self::assertEmpty($output);
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringContainsString($message, $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileLogger::Error()
     *
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     * @uses \CMS\PhpBackup\Helper\FileLogger::getInstance()
     */
    public function testErrorLogEntry()
    {
        $message = 'This is an error message for test ' . uniqid(__FUNCTION__);
        $this->logger->error($message);

        // Assert that the log entry is written to the file
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::ERROR->name, $log_file_content);
        self::assertStringEndsWith($message . "\n", $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileLogger::Warning()
     *
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     * @uses \CMS\PhpBackup\Helper\FileLogger::getInstance()
     */
    public function testWarningLogEntry()
    {
        $message = 'This is an error message for test ' . uniqid(__FUNCTION__);
        $this->logger->setLogLevel(LogLevel::WARNING);
        $this->logger->warning($message);

        // Assert that the log entry is written to the file
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::WARNING->name, $log_file_content);
        self::assertStringEndsWith($message . "\n", $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileLogger::Info()
     *
     * @uses \CMS\PhpBackup\Helper\FileLogger::getInstance()
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testInfoLogEntry()
    {
        $message = 'This is an error message for test ' . uniqid(__FUNCTION__);
        $this->logger->setLogLevel(LogLevel::INFO);
        $this->logger->info($message);

        // Assert that the log entry is written to the file
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::INFO->name, $log_file_content);
        self::assertStringEndsWith($message . "\n", $log_file_content);
    }

    /**
     * @covers \CMS\PhpBackup\Helper\FileLogger::debug()
     *
     * @uses \CMS\PhpBackup\Helper\FileLogger::getInstance()
     * @uses \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testDebugLogEntry()
    {
        $message = 'This is an error message for test ' . uniqid(__FUNCTION__);
        $this->logger->setLogLevel(LogLevel::DEBUG);
        $this->logger->debug($message);

        // Assert that the log entry is written to the file
        self::assertFileExists(self::LOG_FILE_PATH);
        $log_file_content = file_get_contents(self::LOG_FILE_PATH);
        self::assertStringStartsWith(LogLevel::DEBUG->name, $log_file_content);
        self::assertStringEndsWith($message . "\n", $log_file_content);
    }
}
