<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Core;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\StepFactory;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\AbstractStep;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\StepFactory
 */
final class StepFactoryTest extends TestCase
{
    protected const CONFIG_FILE = CONFIG_DIR . 'app.xml';

    protected function setUp(): void
    {
        copy(TEST_FIXTURES_CONFIG_DIR . 'config_full_valid.xml', self::CONFIG_FILE);
        self::assertFileExists(self::CONFIG_FILE);

        AppConfig::loadAppConfig('app');
    }

    protected function tearDown(): void
    {
        FileHelper::deleteFile(self::CONFIG_FILE);
        parent::tearDown();
    }

    public function testBuildWithoutRemote()
    {
        $stepClass = $this->getMockForAbstractClass(AbstractStep::class, [null]);

        $result = StepFactory::build($stepClass::class);

        self::assertInstanceOf($stepClass::class, $result);
    }

    public function testBuildWithRemote()
    {
        $stepClass = $this->getMockForAbstractClass(AbstractStep::class, [null]);

        $result = StepFactory::build($stepClass::class, 'local');

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
        $this->expectExceptionMessage("Method 'createNonexistingclass' does not exist in class CMS\\PhpBackup\\Core\\StepFactory");

        $stepClass = 'NonExistingClass';

        StepFactory::buildRemoteHandler($stepClass);
    }

    /**
     * @covers \CMS\PhpBackup\Step\StepFactory::getRemoteClassNames()
     */
    public function testGetRemoteClassNames()
    {
        $handler = ['local', 'Local', 'LOCAL', Local::class, 'invalid'];
        $classes = StepFactory::getRemoteClassNames($handler);

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

    public static function provideExtractNamespaceCases(): iterable
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
