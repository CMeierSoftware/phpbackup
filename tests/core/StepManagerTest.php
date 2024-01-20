<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Core;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\StepManager;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\StepConfig;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\StepManager
 */
final class StepManagerTest extends TestCase
{
    private const CONFIG_FILE = CONFIG_DIR . 'app.xml';
    private const CONFIG_TEMP_DIR = CONFIG_DIR . 'temp_app' . DIRECTORY_SEPARATOR;
    private const SYSTEM_PATH = TEST_WORK_DIR;
    private const STEP_FILE = self::SYSTEM_PATH . DIRECTORY_SEPARATOR . 'last.step';
    private const STUBS = [StepStub1::class, StepStub2::class, StepStub3::class];
    private array $steps = [];
    private AppConfig $config;

    protected function setUp(): void
    {
        copy(TEST_FIXTURES_CONFIG_DIR . 'config_full_valid.xml', self::CONFIG_FILE);
        self::assertFileExists(self::CONFIG_FILE);
        $this->config = AppConfig::loadAppConfig('app');

        $this->steps = array_map(static fn (string $stub): StepConfig => new StepConfig($stub), self::STUBS);

        FileHelper::makeDir(self::SYSTEM_PATH);
        self::assertDirectoryExists(self::SYSTEM_PATH);
        self::assertFileDoesNotExist(self::STEP_FILE);
    }

    protected function tearDown(): void
    {
        unlink(self::CONFIG_FILE);
        FileHelper::deleteDirectory(self::CONFIG_TEMP_DIR);
        FileHelper::deleteDirectory(self::SYSTEM_PATH);
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepManager::__construct()
     */
    public function testNoSteps()
    {
        $steps = [];
        $this->expectException(\LengthException::class);
        new StepManager($steps, $this->config);
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepManager::__construct()
     *
     * @dataProvider provideMissingStepArgumentsCases
     *
     * @param mixed $step
     */
    public function testMissingStepArguments($step)
    {
        $this->expectException(\UnexpectedValueException::class);
        self::expectExceptionMessage('All entries in the array must be of type ' . StepConfig::class);
        new StepManager([$step], $this->config);
    }

    public static function provideMissingStepArgumentsCases(): iterable
    {
        return [['string'], [0], [null]];
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepManager::executeNextStep()
     */
    public function testExecuteNextStep()
    {
        $calledStubs = array_merge(self::STUBS, [StepStub3::class], self::STUBS);

        for ($i = 0; $i < count($calledStubs); ++$i) {
            $stepManager = new StepManager($this->steps, $this->config);
            $result = $stepManager->executeNextStep();

            self::assertSame('Result: Hello ' . $calledStubs[$i], $result);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepManager::executeNextStep()
     */
    public function testStepsChanged()
    {
        $stepManager = new StepManager($this->steps, $this->config);
        $result = $stepManager->executeNextStep();
        self::assertSame('Result: Hello ' . StepStub1::class, $result);
        $stepManager = new StepManager($this->steps, $this->config);
        $result = $stepManager->executeNextStep();
        self::assertSame('Result: Hello ' . StepStub2::class, $result);
        array_pop($this->steps);
        $stepManager = new StepManager($this->steps, $this->config);
        $result = $stepManager->executeNextStep();
        self::assertSame('Result: Hello ' . StepStub1::class, $result);
    }
}

final class StepStub1 extends AbstractStep
{
    protected function _execute(): StepResult
    {
        return new StepResult('Result: Hello ' . self::class, false);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return [];
    }
}
final class StepStub2 extends AbstractStep
{
    protected function _execute(): StepResult
    {
        return new StepResult('Result: Hello ' . self::class, false);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return [];
    }
}
final class StepStub3 extends AbstractStep
{
    private static $repeat = true;

    protected function _execute(): StepResult
    {
        self::$repeat = !self::$repeat;

        return new StepResult('Result: Hello ' . self::class, !self::$repeat);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return [];
    }
}
