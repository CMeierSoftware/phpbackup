<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\Remote\AbstractRemoteStep;
use CMS\PhpBackup\Step\StepConfig;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\StepConfig
 */
final class StepConfigTest extends TestCaseWithAppConfig
{
    private $stepMock;
    private $remoteMock;

    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');
        $this->stepMock = $this->getMockForAbstractClass(AbstractStep::class, [$this->config]);
        $this->remoteMock = $this->getMockForAbstractClass(AbstractRemoteHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testValidParameter()
    {
        $step = new StepConfig($this->stepMock::class, 0, $this->remoteMock::class);

        self::assertInstanceOf(StepConfig::class, $step);
    }

    /**
     * @dataProvider provideInvalidClassNameCases
     */
    public function testInvalidClassName(string $step)
    {
        $this->expectException(\UnexpectedValueException::class);
        new StepConfig($step);
    }

    public static function provideInvalidClassNameCases(): iterable
    {
        return [['nonExistent'], [\stdClass::class], ['null']];
    }

    public function testInvalidDelay()
    {
        $this->expectException(\UnexpectedValueException::class);
        new StepConfig($this->stepMock::class, -1);
    }

    /**
     * @dataProvider provideInvalidClassNameCases
     */
    public function testInvalidRemoteClass(string $remote)
    {
        $this->expectException(\UnexpectedValueException::class);
        new StepConfig($this->stepMock::class, 0, $remote);
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepConfig::getStepObject()
     */
    public function testGetStepObject()
    {
        $stepConfig = new StepConfig($this->stepMock::class);

        $obj = $stepConfig->getStepObject($this->config);

        self::assertIsObject($obj);
        self::assertInstanceOf(AbstractStep::class, $obj);
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepConfig::getStepObject()
     */
    public function testGetRemoteStepObject()
    {
        $remoteStepMock = $this->getMockForAbstractClass(AbstractRemoteStep::class, [$this->remoteMock, $this->config]);
        $stepConfig = new StepConfig($remoteStepMock::class, 0, $remoteStepMock::class);

        $obj = $stepConfig->getStepObject($this->config);

        self::assertIsObject($obj);
        self::assertInstanceOf(AbstractStep::class, $obj);
    }
}
