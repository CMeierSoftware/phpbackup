<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\StepManager;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\StepManager
 */
final class StepManagerTest extends TestCase
{
    private const SYSTEM_PATH = ABS_PATH . 'tests\\work';
    private const STEP_FILE = self::SYSTEM_PATH . DIRECTORY_SEPARATOR . 'last.step';
    private array $steps = [];

    protected function setUp(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $step = new StepClass('Hello World', (string)$i);
            $this->steps[] = $step;
        }

        FileHelper::makeDir(self::SYSTEM_PATH);
        self::assertFileExists(self::SYSTEM_PATH);
        self::assertFileDoesNotExist(self::STEP_FILE);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::SYSTEM_PATH);
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepManager::__construct()
     */
    public function testNoSteps()
    {
        $steps = [];
        $this->expectException(\LengthException::class);
        new StepManager($steps, self::SYSTEM_PATH);
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepManager::__construct()
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
     * @covers \CMS\PhpBackup\Core\StepManager::executeNextStep()
     */
    public function testExecuteNextStep()
    {
        self::assertTrue(1 < count($this->steps));
        for ($i = 0; $i < count($this->steps); ++$i) {
            $stepManager = new StepManager($this->steps, self::SYSTEM_PATH);
            $result = $stepManager->executeNextStep();
            self::assertSame('Result: Hello World ' . (string) $i, $result);
        }
        $result = $stepManager->executeNextStep();
        self::assertSame('Result: Hello World 9', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepManager::executeNextStep()
     */
    public function testStepsChanged()
    {
        $stepManager = new StepManager($this->steps, self::SYSTEM_PATH);
        $result = $stepManager->executeNextStep();
        self::assertSame('Result: Hello World 0', $result);
        $stepManager = new StepManager($this->steps, self::SYSTEM_PATH);
        $result = $stepManager->executeNextStep();
        self::assertSame('Result: Hello World 1', $result);
        array_pop($this->steps);
        $stepManager = new StepManager($this->steps, self::SYSTEM_PATH);
        $result = $stepManager->executeNextStep();
        self::assertSame('Result: Hello World 0', $result);
    }
}

// Define a static class with a method for testing
final class StepClass extends AbstractStep
{
    private bool $repeated = false;
    private string $arg1;
    private string $arg2;
    public function __construct(string $arg1, string $arg2, int $delay = 0)
    {
        parent::__construct($delay);
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }

    protected function _execute(): StepResult
    {
        $this->repeated = ('9' === $this->arg2 && !$this->repeated);

        return new StepResult("Result: {$this->arg1} {$this->arg2}", $this->repeated);
    }
}
