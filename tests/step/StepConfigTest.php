<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\StepConfig;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\StepConfig
 */
final class StepConfigTest extends TestCaseWithAppConfig
{
    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepConfig::__construct()
     *
     * @dataProvider provideInvalidStepValueClassCases
     */
    public function testInvalidStepValueClass(string $step, int $delay)
    {
        $this->expectException(\UnexpectedValueException::class);
        new StepConfig($step, $delay);
    }

    public static function provideInvalidStepValueClassCases(): iterable
    {
        $invalidClassNames = ['nonExistent', \stdClass::class, 'null'];
        $invalidDelays = [-1];
        $validDelays = [0, 1, 20, PHP_INT_MAX - 1];

        $result = [];

        foreach ($invalidClassNames as $className) {
            foreach (array_merge($invalidDelays, $validDelays) as $delay) {
                $result[] = [$className, $delay];
            }
        }

        foreach ($invalidDelays as $delay) {
            $result[] = [StepStub::class, $delay];
        }

        return $result;
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepConfig::getStepObject()
     */
    public function testGetStepObject()
    {
        $stepConfig = new StepConfig(StepStub::class);

        $obj = $stepConfig->getStepObject($this->config);

        self::assertIsObject($obj);
        self::assertInstanceOf(StepStub::class, $obj);

        $expected = new StepResult('', false);
        $actual = $obj->execute();
        self::assertSame($expected->returnValue, $actual->returnValue);
        self::assertSame($expected->repeat, $actual->repeat);
    }
}

// Define a static class with a method for testing
final class StepStub extends AbstractStep
{
    protected function _execute(): StepResult
    {
        return new StepResult('', false);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return [];
    }
}
