<?php

namespace CMS\PhpBackup\Tests\Core;

use CMS\PhpBackup\Core\Step;
use PHPUnit\Framework\TestCase;

class StepTest extends TestCase
{
    /**
     * @covers Step->setCallback()
     */
    public function testSetCallbackWithInvalidCallback()
    {
        $step = new Step();
        $this->expectException(\InvalidArgumentException::class);
        $step->setCallback('invalidCallback');
    }

    /**
     * @covers Step->setCallback()
     */
    public function testSetCallbackWithNonexistentMethod()
    {
        $step = new Step();
        $this->expectException(\InvalidArgumentException::class);
        $step->setCallback([StaticClass::class, 'nonexistentMethod']);
    }

    /**
     * @covers Step->execute()
     */
    public function testExecuteWithValidCallback()
    {
        $step = new Step();

        $callback = [StaticClass::class, 'exampleMethod'];
        $arguments = ['Hello', 'World'];

        $step->setCallback($callback, $arguments);

        // Execute the callback and assert the result
        $result = $step->execute();
        $this->assertEquals("Result: Hello World", $result);
    }

    /**
     * @covers Step->execute()
     */
    public function testExecuteWithoutSettingCallback()
    {
        $step = new Step();

        $this->expectException(\RuntimeException::class);
        $step->execute();
    }
}

// Define a static class with a method for testing
class StaticClass
{
    public static function exampleMethod($arg1, $arg2)
    {
        return "Result: $arg1 $arg2";
    }
}
