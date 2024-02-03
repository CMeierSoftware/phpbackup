<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Remote\Local;
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

        $result = StepFactory::build($stepClass::class);

        self::assertInstanceOf($stepClass::class, $result);
    }

    /**
     * @dataProvider RemoteClassNameProvider
     */
    public function testBuildWithRemoteClass(string $remoteHandler)
    {
        $stepClass = $this->getMockForAbstractClass(AbstractStep::class);

        $result = StepFactory::build($stepClass::class, $remoteHandler);

        self::assertInstanceOf($stepClass::class, $result);
    }

    public static function RemoteClassNameProvider()
    {
        return [
            ['local'],
            ['Local'],
            [Local::class]
        ];
    }

    public function testBuildWithNonExistingClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistingClass does not exist');

        $stepClass = 'NonExistingClass';
        $remoteHandler = 'local';

        StepFactory::build($stepClass, $remoteHandler);
    }

    public function testGetRemoteClasses()
    {
        $handler = ['local', 'Local', 'LOCAL', Local::class, 'invalid'];
        $classes = StepFactory::getRemoteClasses($handler);

        self::assertSame(array_fill(0, 4, Local::class), $classes);
    }
}
