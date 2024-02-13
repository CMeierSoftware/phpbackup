<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;
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
        parent::setUp();
        $this->setUpAppConfig('config_full_valid');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::execute()
     */
    public function testExecute()
    {
        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::once())->method('_execute')->willReturn($stepResult);

        $data = [];
        $step->setData($data);
        $result = $step->execute();

        self::assertSame($stepResult, $result);
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::execute()
     */
    public function testDataNotSet()
    {
        $step = $this->getMockedHandler();

        self::expectException(\InvalidArgumentException::class);
        $step->execute();
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::validateData()
     */
    public function testValidateDataKeySuccess()
    {
        $step = $this->getMockedHandler();

        $prop = new \ReflectionProperty($step::class, 'data');
        $prop->setValue($step, ['key' => 'value']);

        $step->expects(self::once())->method('getRequiredDataKeys')->willReturn(['key']);

        $method = new \ReflectionMethod($step::class, 'validateData');
        $method->setAccessible(true);

        self::assertTrue($method->invokeArgs($step, []));
    }

    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::validateData()
     */
    public function testValidateDataKeyMissing()
    {
        $step = $this->getMockedHandler();

        $prop = new \ReflectionProperty($step::class, 'data');
        $prop->setValue($step, ['key' => 'value']);

        $step->expects(self::once())->method('getRequiredDataKeys')->willReturn(['missing']);

        $method = new \ReflectionMethod($step::class, 'validateData');
        $method->setAccessible(true);

        self::expectException(\InvalidArgumentException::class);
        $method->invokeArgs($step, []);
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

    /**
     * @large
     *
     * @covers \CMS\PhpBackup\Step\AbstractStep::isTimeoutClose()
     */
    public function testIsTimeoutClose()
    {
        $oldMaxTime = ini_get('max_execution_time');
        ini_set('max_execution_time', 10);
        $step = $this->getMockedHandler();
        ini_set('max_execution_time', $oldMaxTime);

        self::assertFalse($step->isTimeoutClose());
        sleep(1);
        // elapsed ~1 secs. Has 1.5 secs more time?
        self::assertFalse($step->isTimeoutClose());
        sleep(2);
        //  elapsed ~3 secs. Has 3 secs more time?
        self::assertFalse($step->isTimeoutClose());
        sleep(2);
        //  elapsed ~5 secs. Has 3 secs more time?
        self::assertFalse($step->isTimeoutClose());
        sleep(2);
        //  elapsed ~8 secs. Has 3 secs more time?
        self::assertTrue($step->isTimeoutClose());
    }

    private function getMockedHandler(?AbstractRemoteHandler $remote = null): AbstractStep|MockObject
    {
        $mockBuilder = $this->getMockBuilder(AbstractStep::class);
        $mockBuilder->onlyMethods(['_execute', 'getRequiredDataKeys']);
        $mockBuilder->setConstructorArgs([$remote]);

        return $mockBuilder->getMockForAbstractClass();
    }

    private static function assertStepResultFile(string $expected)
    {
        self::assertDirectoryExists(self::CONFIG_TEMP_DIR);
        self::assertFileExists(self::CONFIG_STEP_RESULT_FILE);

        self::assertJsonFileEqualsJsonFile($expected, self::CONFIG_STEP_RESULT_FILE);
    }
}
