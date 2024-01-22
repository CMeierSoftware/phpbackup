<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

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
    private const WATCHDOG_FILE = self::CONFIG_TEMP_DIR . 'send_remote_watchdog.json';
    private const CONFIG_STEP_RESULT_FILE = self::CONFIG_TEMP_DIR . 'StepData.json';
    private const CONFIG_STEP_EXPECTED_FILE = TEST_FIXTURES_STEPS_DIR . 'StepData.json';

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
        $step->expects(self::once())->method('_execute')->willReturn($stepResult);
        $step->expects(self::once())->method('getRequiredStepDataKeys')->willReturn([]);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);
        self::assertStringEqualsFile(self::CONFIG_STEP_RESULT_FILE, '[]');
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::execute()
     */
    public function testAddStepData()
    {
        self::assertFileDoesNotExist(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::once())->method('_execute')->willReturn($stepResult);
        $step->expects(self::once())->method('getRequiredStepDataKeys')->willReturn([]);

        $reflectionProp = new \ReflectionProperty($step, 'stepData');
        $reflectionProp->setAccessible(true);
        $reflectionProp->setValue($step, ['key' => 'value', 'key2' => 't']);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
        self::assertStepResultFile(self::CONFIG_STEP_EXPECTED_FILE);
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::execute()
     */
    public function testExecuteExistingStepData()
    {
        FileHelper::makeDir(self::CONFIG_TEMP_DIR);
        copy(self::CONFIG_STEP_EXPECTED_FILE, self::CONFIG_STEP_RESULT_FILE);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::once())->method('_execute')->willReturn($stepResult);
        $step->expects(self::once())->method('getRequiredStepDataKeys')->willReturn([]);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
        self::assertStepResultFile(self::CONFIG_STEP_EXPECTED_FILE);
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::validateStepData()
     */
    public function testValidateStepDataKeySuccess()
    {
        FileHelper::makeDir(self::CONFIG_TEMP_DIR);
        copy(self::CONFIG_STEP_EXPECTED_FILE, self::CONFIG_STEP_RESULT_FILE);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::once())->method('_execute')->willReturn($stepResult);
        $step->expects(self::once())->method('getRequiredStepDataKeys')->willReturn(['key']);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
        self::assertStepResultFile(self::CONFIG_STEP_EXPECTED_FILE);
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::validateStepData()
     */
    public function testValidateStepDataKeyMissing()
    {
        FileHelper::makeDir(self::CONFIG_TEMP_DIR);
        copy(self::CONFIG_STEP_EXPECTED_FILE, self::CONFIG_STEP_RESULT_FILE);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::never())->method('_execute')->willReturn($stepResult);
        $step->expects(self::once())->method('getRequiredStepDataKeys')->willReturn(['missing']);

        self::expectException(\InvalidArgumentException::class);
        $step->execute();
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::getAttemptsCount()
     *
     * @uses \CMS\PhpBackup\Step\AbstractStep::getAttemptCount()
     */
    public function testWatchdogFileCreated()
    {
        self::assertFileDoesNotExist(self::WATCHDOG_FILE);
        $step = $this->getMockedHandler();

        $reflectionMethod = new \ReflectionMethod(get_class($step), 'incrementAttemptsCount');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invoke($step);
        self::assertFileExists(self::WATCHDOG_FILE);
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::incrementAttemptsCount()
     *
     * @uses \CMS\PhpBackup\Step\AbstractStep::getAttemptCount()
     */
    public function testAttemptsIncrementSuccess()
    {
        $count = 3;
        $step = $this->getMockedHandler();

        $reflectionMethod = new \ReflectionMethod(get_class($step), 'incrementAttemptsCount');
        $reflectionMethod->setAccessible(true);

        for ($i = 0; $i < $count; ++$i) {
            $reflectionMethod->invoke($step);
        }

        self::assertFileExists(self::WATCHDOG_FILE);
        self::assertStringContainsString("\"attempts\": {$count}", file_get_contents(self::WATCHDOG_FILE));
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::incrementAttemptsCount()
     *
     * @uses \CMS\PhpBackup\Step\AbstractStep::getAttemptCount()
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

        self::assertStringContainsString('"attempts": 1', file_get_contents(self::WATCHDOG_FILE));
        $methodResetAttemptsCount->invoke($step);
        self::assertStringContainsString('"attempts": 0', file_get_contents(self::WATCHDOG_FILE));
    }

    private function getMockedHandler(): AbstractStep|MockObject
    {
        $mockBuilder = $this->getMockBuilder(AbstractStep::class);
        $mockBuilder->setConstructorArgs([$this->config]);
        $mockBuilder->onlyMethods(['_execute', 'getRequiredStepDataKeys']);

        return $mockBuilder->getMockForAbstractClass();
    }

    private static function assertStepResultFile(string $expected)
    {
        self::assertDirectoryExists(self::CONFIG_TEMP_DIR);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        self::assertJsonFileEqualsJsonFile($expected, self::CONFIG_STEP_RESULT_FILE);
    }
}
