<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\LogLevel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LogLevel
 */
class LogLevelTest extends TestCase
{
    /**
     * @codeCoverageIgnore
     */
    public function testLogLevelsConstants()
    {
        $this->assertEquals(0, LogLevel::OFF);
        $this->assertEquals(1, LogLevel::ERROR);
        $this->assertEquals(2, LogLevel::WARNING);
        $this->assertEquals(3, LogLevel::INFO);
    }

    /**
     * @covers \toString
     */
    public function testToStringForOff()
    {
        $result = LogLevel::toString(LogLevel::OFF);
        $this->assertEquals('OFF', $result);
    }

    /**
     * @covers \toString
     */
    public function testToStringForError()
    {
        $result = LogLevel::toString(LogLevel::ERROR);
        $this->assertEquals('ERROR', $result);
    }

    /**
     * @covers \toString
     */
    public function testToStringForWarning()
    {
        $result = LogLevel::toString(LogLevel::WARNING);
        $this->assertEquals('WARNING', $result);
    }

    /**
     * @covers \toString
     */
    public function testToStringForInfo()
    {
        $result = LogLevel::toString(LogLevel::INFO);
        $this->assertEquals('INFO', $result);
    }

    /**
     * @covers \toString
     */
    public function testToStringThrowsExceptionForInvalidLogLevel()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid log level: 42');

        LogLevel::toString(42);
    }
}
