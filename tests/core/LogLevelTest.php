<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\LogLevel;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\LogLevel
 */
class LogLevelTest extends TestCase
{
    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::ERROR
     * @covers \CMS\PhpBackup\Core\LogLevel::INFO
     * @covers \CMS\PhpBackup\Core\LogLevel::OFF
     * @covers \CMS\PhpBackup\Core\LogLevel::WARNING
     */
    public function testLogLevelsConstants()
    {
        $this->assertEquals(0, LogLevel::OFF);
        $this->assertEquals(1, LogLevel::ERROR);
        $this->assertEquals(2, LogLevel::WARNING);
        $this->assertEquals(3, LogLevel::INFO);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringForOff()
    {
        $result = LogLevel::toString(LogLevel::OFF);
        $this->assertEquals('OFF', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringForError()
    {
        $result = LogLevel::toString(LogLevel::ERROR);
        $this->assertEquals('ERROR', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringForWarning()
    {
        $result = LogLevel::toString(LogLevel::WARNING);
        $this->assertEquals('WARNING', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringForInfo()
    {
        $result = LogLevel::toString(LogLevel::INFO);
        $this->assertEquals('INFO', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringThrowsExceptionForInvalidLogLevel()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid log level: 42');

        LogLevel::toString(42);
    }
}
