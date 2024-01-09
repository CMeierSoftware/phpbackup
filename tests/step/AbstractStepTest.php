<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\AbstractStep
 */
final class AbstractStepTest extends TestCaseWithAppConfig
{
    private const WATCHDOG_FILE = self::CONFIG_TEMP_DIR . 'send_remote_watchdog.xml';
    private const CONFIG_STEP_RESULT_FILE = self::CONFIG_TEMP_DIR . 'StepData.xml';

    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::execute()
     */
    public function testExecuteNoStepData()
    {
        self::assertFileDoesNotExist(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::exactly(1))->method('_execute')->willReturn($stepResult);
        $step->expects(self::exactly(1))->method('getRequiredStepDataKeys')->willReturn([]);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
        self::assertFileDoesNotExist(self::CONFIG_STEP_RESULT_FILE);
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::execute()
     */
    public function testAddStepData()
    {
        self::assertFileDoesNotExist(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::exactly(1))->method('_execute')->willReturn($stepResult);
        $step->expects(self::exactly(1))->method('getRequiredStepDataKeys')->willReturn([]);

        $reflectionClass = new \ReflectionClass($step);
        $property = $reflectionClass->getProperty('stepData');
        $property->setAccessible(true);
        $property->setValue($step, ['key' => 'value', 'key2' => 't']);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
        self::assertStepResultFile(TEST_FIXTURES_STEPS_DIR . 'StepData.xml');
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::execute()
     */
    public function testExecuteExistingStepData()
    {
        FileHelper::makeDir(self::CONFIG_TEMP_DIR);
        copy(TEST_FIXTURES_STEPS_DIR . 'StepData.xml', self::CONFIG_STEP_RESULT_FILE);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::exactly(1))->method('_execute')->willReturn($stepResult);
        $step->expects(self::exactly(1))->method('getRequiredStepDataKeys')->willReturn([]);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
        self::assertStepResultFile(TEST_FIXTURES_STEPS_DIR . 'StepData.xml');
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::validateStepData()
     */
    public function testValidateStepDataKeySuccess()
    {
        FileHelper::makeDir(self::CONFIG_TEMP_DIR);
        copy(TEST_FIXTURES_STEPS_DIR . 'StepData.xml', self::CONFIG_STEP_RESULT_FILE);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::exactly(1))->method('_execute')->willReturn($stepResult);
        $step->expects(self::exactly(1))->method('getRequiredStepDataKeys')->willReturn(['key']);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
        self::assertStepResultFile(TEST_FIXTURES_STEPS_DIR . 'StepData.xml');
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::validateStepData()
     */
    public function testValidateStepDataKeyMissing()
    {
        FileHelper::makeDir(self::CONFIG_TEMP_DIR);
        copy(TEST_FIXTURES_STEPS_DIR . 'StepData.xml', self::CONFIG_STEP_RESULT_FILE);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::never())->method('_execute')->willReturn($stepResult);
        $step->expects(self::exactly(1))->method('getRequiredStepDataKeys')->willReturn(['missing']);

        self::expectException(\InvalidArgumentException::class);
        $step->execute();
    }

    public function testSerialize(): void
    {
        $step = $this->getMockedHandler();

        $expected = 'O:' . strlen($step::class) . ':"' . $step::class . '":1:{i:0;i:0;}';

        self::assertSame($expected, serialize($step));
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::getAttemptsCount()
     *
     * @uses \CMS\PhpBackup\Step\AbstractStep::getAttemptsCount()
     */
    public function testWatchdogFileCreated()
    {
        self::assertFileDoesNotExist(self::WATCHDOG_FILE);
        $step = $this->getMockedHandler();

        $reflectionClass = new \ReflectionClass(get_class($step));
        $method = $reflectionClass->getMethod('incrementAttemptsCount');
        $method->setAccessible(true);

        $method->invoke($step);
        self::assertFileExists(self::WATCHDOG_FILE);
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::incrementAttemptsCount()
     *
     * @uses \CMS\PhpBackup\Step\AbstractStep::getAttemptsCount()
     */
    public function testAttemptsIncrementSuccess()
    {
        $count = 3;
        $step = $this->getMockedHandler();

        $reflectionClass = new \ReflectionClass(get_class($step));
        $methodIncrementAttemptsCount = $reflectionClass->getMethod('incrementAttemptsCount');
        $methodIncrementAttemptsCount->setAccessible(true);

        for ($i = 0; $i < $count; ++$i) {
            $methodIncrementAttemptsCount->invoke($step);
        }

        self::assertFileExists(self::WATCHDOG_FILE);
        self::assertStringContainsString("<attempts>{$count}</attempts>", file_get_contents(self::WATCHDOG_FILE));
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::incrementAttemptsCount()
     *
     * @uses \CMS\PhpBackup\Step\AbstractStep::getAttemptsCount()
     * @uses \CMS\PhpBackup\Step\AbstractStep::incrementAttemptsCount()
     */
    public function testAttemptsResetSuccess()
    {
        $step = $this->getMockedHandler();

        $reflectionClass = new \ReflectionClass(get_class($step));
        $methodIncrementAttemptsCount = $reflectionClass->getMethod('incrementAttemptsCount');
        $methodIncrementAttemptsCount->setAccessible(true);
        $methodResetAttemptsCount = $reflectionClass->getMethod('resetAttemptsCount');
        $methodResetAttemptsCount->setAccessible(true);

        $methodIncrementAttemptsCount->invoke($step);

        self::assertStringContainsString('<attempts>1</attempts>', file_get_contents(self::WATCHDOG_FILE));
        $methodResetAttemptsCount->invoke($step);
        self::assertStringContainsString('<attempts>0</attempts>', file_get_contents(self::WATCHDOG_FILE));
    }

    private function getMockedHandler(): MockObject
    {
        $mockBuilder = $this->getMockBuilder(AbstractStep::class);
        $mockBuilder->setConstructorArgs([$this->config]);
        $mockBuilder->onlyMethods(['_execute', 'getRequiredStepDataKeys']);

        return $mockBuilder->getMock();
    }

    private static function assertStepResultFile(string $expected)
    {
        self::assertDirectoryExists(self::CONFIG_TEMP_DIR);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        self::assertXmlFileEqualsXmlFile($expected, self::CONFIG_STEP_RESULT_FILE);
    }
}
