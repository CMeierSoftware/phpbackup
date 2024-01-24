<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\StepFactory;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\StepFactory
 */
final class StepFactoryTest extends TestCaseWithAppConfig
{
    protected function setUp(): void
    {
        $this->setUpAppConfig('config_full_valid');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testBuild()
    {
        $stepClass = $this->getMockForAbstractClass(AbstractStep::class);

        $result = StepFactory::build($stepClass::class, '');

        self::assertInstanceOf($stepClass::class, $result);
    }

    public function testBuildWithRemoteClass()
    {
        $stepClass = $this->getMockForAbstractClass(AbstractStep::class);
        $remoteHandler = 'Local';

        $result = StepFactory::build($stepClass::class, $remoteHandler);

        self::assertInstanceOf($stepClass::class, $result);
    }

    public function testBuildWithNonExistingClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistingClass does not exist');

        $stepClass = 'NonExistingClass';
        $remoteHandler = 'local';

        StepFactory::build($stepClass, $remoteHandler);
    }
}
