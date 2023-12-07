<?php

namespace CMS\PhpBackup\Tests\Core;

use CMS\PhpBackup\Core\StepManager;
use CMS\PhpBackup\Core\Step;
use PHPUnit\Framework\TestCase;

class StepManagerTest extends TestCase
{
    private const STEP_FILE = CONFIG_DIR . DIRECTORY_SEPARATOR . 'last.step';
    private array $steps = [];

    protected function setUp(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $step = new Step();
            $step->setCallback([StepManagerTestClass::class, 'exampleMethod'], ['Hello', 'World ' . strval($i)]);
            $this->steps[] = $step;
        }

        if (file_exists(self::STEP_FILE)) {
            unlink(self::STEP_FILE);
        }
    }
    /**
     * @covers StepManager()
     */
    public function testNoSteps()
    {
        $steps = [];
        $this->expectException(\LengthException::class);
        new StepManager($steps);
    }

    /**
     * @covers StepManager::executeNextStep
     */
    public function testExecuteNextStep()
    {
        for ($i = 0; $i < count($this->steps); $i++) {
            $stepManager = new StepManager($this->steps);
            $result = $stepManager->executeNextStep();
            $this->assertEquals("Result: Hello World " . strval($i), $result);
        }
    }

    /**
     * @covers StepManager::executeNextStep
     */
    public function testStepsChanged()
    {
        $stepManager = new StepManager($this->steps);
        $result = $stepManager->executeNextStep();
        $this->assertEquals("Result: Hello World 0", $result);
        $stepManager = new StepManager($this->steps);
        $result = $stepManager->executeNextStep();
        $this->assertEquals("Result: Hello World 1", $result);
        array_pop($this->steps);
        $stepManager = new StepManager($this->steps);
        $result = $stepManager->executeNextStep();
        $this->assertEquals("Result: Hello World 0", $result);
    }
}

// Define a static class with a method for testing
class StepManagerTestClass
{
    public static function exampleMethod($arg1, $arg2)
    {
        return "Result: $arg1 $arg2";
    }
}
