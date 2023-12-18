<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\Step;
use CMS\PhpBackup\Core\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\Step
 */
final class StepTest extends TestCase
{
    /**
     * @covers \CMS\PhpBackup\Core\Step::setCallback()
     */
    public function testSetCallbackWithInvalidCallback()
    {
        $this->expectException(\TypeError::class);
        new Step('invalidCallback');
    }

    /**
     * @covers \CMS\PhpBackup\Core\Step::setCallback()
     */
    public function testSetCallbackWithNonexistentMethod()
    {
        $this->expectException(\TypeError::class);
        new Step([StaticClass::class, 'nonexistentMethod']);
    }

    /**
     * @covers \CMS\PhpBackup\Core\Step::execute()
     */
    public function testExecuteWithValidStaticCallback()
    {
        $callback = [StaticClass::class, 'exampleMethod'];
        $arguments = ['Hello', 'World'];
        $step = new Step($callback, $arguments);

        // Execute the callback and assert the result
        $result = $step->execute();
        self::assertInstanceOf(StepResult::class, $result);
        self::assertSame('Result: Hello World', $result->returnValue);
        self::assertFalse($result->repeat);
    }

    /**
     * @covers \CMS\PhpBackup\Core\Step::execute()
     */
    public function testExecuteWithValidCallback()
    {
        $obj = new StaticClass();

        $callback = [$obj, 'exampleMethod'];
        $arguments = ['Hello', 'World'];

        $step = new Step($callback, $arguments);

        // Execute the callback and assert the result
        $result = $step->execute();
        self::assertInstanceOf(StepResult::class, $result);
        self::assertSame('Result: Hello World', $result->returnValue);
        self::assertFalse($result->repeat);
    }

    public function testToStringWithArrayCallback(): void
    {
        $callback = [StaticClass::class, 'exampleMethod'];
        $step = new Step($callback);

        $expected = "Callable: [" . StaticClass::class . ", exampleMethod]";
        $actual = $step->__toString();

        $this->assertSame($expected, $actual);
    }

    public function testToStringWithObjectCallback(): void
    {
        $callback = new StaticClass();
        $step = new Step([$callback, 'exampleMethod']);

        $expected = "Callable: [" . StaticClass::class . ", exampleMethod]";
        $actual = $step->__toString();

        $this->assertSame($expected, $actual);
    }
}

// Define a static class with a method for testing
class StaticClass
{
    public static function exampleMethod($arg1, $arg2)
    {
        return new StepResult("Result: {$arg1} {$arg2}", false);
    }
}
