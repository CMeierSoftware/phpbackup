<?php

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\Step;
use CMS\PhpBackup\Core\StepManager;
use CMS\PhpBackup\Core\StepResult;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class StepManagerTest extends TestCase
{
    private const SYSTEM_PATH = ABS_PATH . 'tests\\work';
    private const STEP_FILE = self::SYSTEM_PATH . DIRECTORY_SEPARATOR . 'last.step';
    private array $steps = [];

    protected function setUp(): void
    {
        $sm = new StepManagerTestClass();
        for ($i = 0; $i < 10; ++$i) {
            $step = new Step([$sm, 'exampleMethod'], ['Hello World', $i]);
            $this->steps[] = $step;
        }

        FileHelper::makeDir(self::SYSTEM_PATH);
        $this->assertFileExists(self::SYSTEM_PATH);
        $this->assertFileDoesNotExist(self::STEP_FILE);
    }

    public function tearDown(): void
    {
        FileHelper::deleteDirectory(self::SYSTEM_PATH);
    }

    /**
     * @covers \StepManager()
     */
    public function testNoSteps()
    {
        $steps = [];
        $this->expectException(\LengthException::class);
        new StepManager($steps, self::SYSTEM_PATH);
    }

    /**
     * @covers \StepManager()
     */
    public function testInvalidStepArray()
    {
        // Create an array with a non-Step instance
        $invalidStep = new \stdClass(); // Not an instance of Step

        // Instantiate the StepManager with the invalid array
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All entries in the array must be Step instances.');
        new StepManager([$invalidStep], self::SYSTEM_PATH);
    }

    /**
     * @covers \StepManager::executeNextStep
     */
    public function testExecuteNextStep()
    {
        for ($i = 0; $i < count($this->steps); ++$i) {
            $stepManager = new StepManager($this->steps, self::SYSTEM_PATH);
            $result = $stepManager->executeNextStep();
            $this->assertEquals('Result: Hello World ' . strval($i), $result);
        }
        $result = $stepManager->executeNextStep();
        $this->assertEquals('Result: Hello World 9', $result);
    }

    /**
     * @covers \StepManager::executeNextStep
     */
    public function testStepsChanged()
    {
        $stepManager = new StepManager($this->steps, self::SYSTEM_PATH);
        $result = $stepManager->executeNextStep();
        $this->assertEquals('Result: Hello World 0', $result);
        $stepManager = new StepManager($this->steps, self::SYSTEM_PATH);
        $result = $stepManager->executeNextStep();
        $this->assertEquals('Result: Hello World 1', $result);
        array_pop($this->steps);
        $stepManager = new StepManager($this->steps, self::SYSTEM_PATH);
        $result = $stepManager->executeNextStep();
        $this->assertEquals('Result: Hello World 0', $result);
    }
}

// Define a static class with a method for testing
class StepManagerTestClass
{
    private bool $repeated = false;

    public function exampleMethod($arg1, $arg2)
    {
        $this->repeated = (9 === $arg2 && !$this->repeated);

        return new StepResult("Result: {$arg1} {$arg2}", $this->repeated);
    }
}
