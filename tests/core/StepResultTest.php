<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\StepResult
 */
final class StepResultTest extends TestCase
{

    /**
     * @covers \CMS\PhpBackup\Core\StepResult::__construct()
     */    public function testCreateNewInstanceWithReturnValueAndRepeatFlag()
    {
        $returnValue = 'test';
        $repeat = true;
        $stepResult = new StepResult($returnValue, $repeat);
        
        self::assertSame($returnValue, $stepResult->returnValue);
        self::assertSame($repeat, $stepResult->repeat);
    }

   /**
    * @covers \CMS\PhpBackup\Core\StepResult::__toString()
    */
     public function testCallToStringOnInstance()
    {
        $returnValue = 'test';
        $repeat = true;
        $expectedString = "ReturnValue: {$returnValue}, Repeat: true";
        $stepResult = new StepResult($returnValue, $repeat);
        
        self::assertSame($expectedString, $stepResult->__toString());
    }

   /**
    * @covers \CMS\PhpBackup\Core\StepResult::__toString()
    */
        public function testCreateNewInstanceWithReturnValueOnly()
    {
        $returnValue = 'test';
        $expectedRepeat = false;
        $expectedString = "ReturnValue: {$returnValue}, Repeat: false";
        $stepResult = new StepResult($returnValue);
        
        self::assertSame($returnValue, $stepResult->returnValue);
        self::assertSame($expectedRepeat, $stepResult->repeat);
        self::assertSame($expectedString, $stepResult->__toString());
    }
}