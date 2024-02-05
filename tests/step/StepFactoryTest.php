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
     * @covers \CMS\PhpBackup\Step\StepFactory::build()
     */
    public function testBuildWithNonExistingClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Class 'NonExistingClass' does not exist");

        $stepClass = 'NonExistingClass';

        StepFactory::build($stepClass);
    }

    /**
     * @dataProvider provideBuildRemoteClassCases
     *
     * @covers \CMS\PhpBackup\Step\StepFactory::buildRemoteHandler()
     */
    public function testBuildRemoteClass(string $remoteHandler)
    {
        $result = StepFactory::buildRemoteHandler($remoteHandler);

        self::assertInstanceOf(Local::class, $result);
    }

    public static function provideBuildRemoteClassCases(): iterable
    {
        return [
            ['local'],
            ['Local'],
            ['LOCAL'],
            [Local::class],
        ];
    }

    /**
     * @covers \CMS\PhpBackup\Step\StepFactory::build()
     */
    public function testBuildRemoteClassWithNonExistingClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Method 'createNonexistingclass' does not exist in class CMS\\PhpBackup\\Step\\StepFactory");

        $stepClass = 'NonExistingClass';

        StepFactory::buildRemoteHandler($stepClass);
    }

    /**
     * @covers \CMS\PhpBackup\Step\StepFactory::testGetRemoteClasses()
     */
    public function testGetRemoteClasses()
    {
        $handler = ['local', 'Local', 'LOCAL', Local::class, 'invalid'];
        $classes = StepFactory::getRemoteClasses($handler);

        self::assertSame(array_fill(0, 4, Local::class), $classes);
    }

    /**
     * @dataProvider provideExtractNamespaceCases
     *
     * @param mixed $cls
     * @param mixed $expectedResult
     */
    public function testExtractNamespace($cls, $expectedResult)
    {
        self::assertSame($expectedResult, StepFactory::extractNamespace($cls));
    }

    public function provideExtractNamespaceCases(): iterable
    {
        return [
            ['Namespace1\Namespace2\Namespace3\ClassName', 'Namespace1\Namespace2\Namespace3'],
            ['Namespace\ClassName', 'Namespace'],
            ['', ''],
            ['ClassName', ''],
            ['Namespace1\ClassName\Namespace2', 'Namespace1\ClassName'],
        ];
    }
}
