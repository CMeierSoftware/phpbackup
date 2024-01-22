<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Step\Remote\SendFileStep;
use CMS\PhpBackup\Step\Remote\StepFactory;

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

    public function testBuildWithExistingClass()
    {
        $stepClass = SendFileStep::class;
        $remoteHandler = 'Local';

        $result = StepFactory::build($stepClass, $remoteHandler, $this->config);

        self::assertInstanceOf($stepClass, $result);
    }

    public function testBuildWithNonExistingClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistingClass does not exist');

        $stepClass = 'NonExistingClass';
        $remoteHandler = 'local';

        StepFactory::build($stepClass, $remoteHandler, $this->config);
    }
}
