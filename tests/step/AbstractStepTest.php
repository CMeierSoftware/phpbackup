<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\AbstractStep
 */
final class AbstractStepTest extends TestCase
{
    public const CONFIG_FILE = CONFIG_DIR . 'valid_app.xml';
    public const CONFIG_TEMP_DIR = CONFIG_DIR . 'temp_valid_app';
    public const CONFIG_STEP_RESULT_FILE = self::CONFIG_TEMP_DIR . DIRECTORY_SEPARATOR . 'StepData.xml';
    private AppConfig $config;

    protected function setUp(): void
    {
        copy(TEST_FIXTURES_CONFIG_DIR . 'config_full_valid.xml', self::CONFIG_FILE);
        self::assertFileExists(self::CONFIG_FILE);

        $this->config = AppConfig::loadAppConfig('valid_app');
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEST_WORK_DIR);
        FileHelper::deleteDirectory(self::CONFIG_TEMP_DIR);
        unlink(self::CONFIG_FILE);
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
