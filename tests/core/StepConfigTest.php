<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Core\StepConfig;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\AbstractStep;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\StepConfig
 */
final class StepConfigTest extends TestCaseWithAppConfig
{
    private $stepMock;

    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');
        $this->stepMock = $this->getMockForAbstractClass(AbstractStep::class, [null]);

        FileHelper::makeDir(TEST_WORK_DIR);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEST_WORK_DIR);
        parent::tearDown();
    }

    public function testValidParameter()
    {
        $remoteMock = $this->getMockForAbstractClass(AbstractRemoteHandler::class);

        $step = new StepConfig($this->stepMock::class, 0, $remoteMock::class);

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

    /**
     * @dataProvider provideInvalidClassNameCases
     */
    public function testInvalidRemoteClass(string $remote)
    {
        $this->expectException(\UnexpectedValueException::class);
        new StepConfig($this->stepMock::class, 0, $remote);
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
     * @covers \CMS\PhpBackup\Core\StepConfig::getStepObject()
     */
    public function testGetStepObject()
    {
        $stepConfig = new StepConfig($this->stepMock::class);

        $obj = $stepConfig->getStepObject();

        self::assertIsObject($obj);
        self::assertInstanceOf(AbstractStep::class, $obj);
    }

    /**
     * @covers \CMS\PhpBackup\Core\StepConfig::getStepObject()
     */
    public function testGetRemoteStepObject()
    {
        $remote = new Local(TEST_WORK_DIR);

        $remoteStepMock = $this->getMockForAbstractClass(AbstractStep::class, [$remote]);
        $stepConfig = new StepConfig($remoteStepMock::class, 0, $remote::class);

        $obj = $stepConfig->getStepObject();

        self::assertIsObject($obj);
        self::assertInstanceOf(AbstractStep::class, $obj);
    }
}
