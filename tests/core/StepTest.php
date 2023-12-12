<?php

namespace CMS\PhpBackup\Tests\Core;

use CMS\PhpBackup\Core\Step;
use CMS\PhpBackup\Core\StepResult;
use PHPUnit\Framework\TestCase;

class StepTest extends TestCase
{
    /**
     * @covers Step->setCallback()
     */
    public function testSetCallbackWithInvalidCallback()
    {
        $this->expectException(\TypeError::class);
        new Step('invalidCallback');
    }

    /**
     * @covers Step->setCallback()
     */
    public function testSetCallbackWithNonexistentMethod()
    {
        $this->expectException(\TypeError::class);
        new Step([StaticClass::class, 'nonexistentMethod']);
    }

    /**
     * @covers Step->execute()
     */
    public function testExecuteWithValidStaticCallback()
    {

        $callback = [StaticClass::class, 'exampleMethod'];
        $arguments = ['Hello', 'World'];
        $step = new Step($callback, $arguments);

        // Execute the callback and assert the result
        $result = $step->execute();
        $this->assertInstanceOf(StepResult::class, $result);
        $this->assertEquals("Result: Hello World", $result->returnValue);
        $this->assertFalse($result->repeat);
    }
    /**
     * @covers Step->execute()
     */
    public function testExecuteWithValidCallback()
    {
        $obj = new StaticClass();

        $callback = [$obj, 'exampleMethod'];
        $arguments = ['Hello', 'World'];

        $step = new Step($callback, $arguments);

        // Execute the callback and assert the result
        $result = $step->execute();
        $this->assertInstanceOf(StepResult::class, $result);
        $this->assertEquals("Result: Hello World", $result->returnValue);
        $this->assertFalse($result->repeat);
    }
}

// Define a static class with a method for testing
class StaticClass
{
    public static function exampleMethod($arg1, $arg2)
    {
        return new StepResult("Result: $arg1 $arg2", false);
    }
}
