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
final class LogLevelTest extends TestCase
{
    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::ERROR
     * @covers \CMS\PhpBackup\Core\LogLevel::INFO
     * @covers \CMS\PhpBackup\Core\LogLevel::OFF
     * @covers \CMS\PhpBackup\Core\LogLevel::WARNING
     */
    public function testLogLevelsConstants()
    {
        self::assertSame(0, LogLevel::OFF);
        self::assertSame(1, LogLevel::ERROR);
        self::assertSame(2, LogLevel::WARNING);
        self::assertSame(3, LogLevel::INFO);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringForOff()
    {
        $result = LogLevel::toString(LogLevel::OFF);
        self::assertSame('OFF', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringForError()
    {
        $result = LogLevel::toString(LogLevel::ERROR);
        self::assertSame('ERROR', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringForWarning()
    {
        $result = LogLevel::toString(LogLevel::WARNING);
        self::assertSame('WARNING', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\LogLevel::toString()
     */
    public function testToStringForInfo()
    {
        $result = LogLevel::toString(LogLevel::INFO);
        self::assertSame('INFO', $result);
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
