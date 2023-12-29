<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

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
    /**
     * @covers \CMS\PhpBackup\Step\AbstractStep::execute()
     */
    public function testExecute()
    {
        $stepResult = new StepResult('Result', false);
        $step = $this->getMockedHandler();
        $step->expects(self::exactly(1))->method('_execute')->willReturn($stepResult);

        $result = $step->execute();

        self::assertSame($stepResult, $result);
    }

    public function testSerialize(): void
    {
        $step = $this->getMockedHandler();

        $expected = 'O:' . strlen($step::class) . ':"' . $step::class . '":1:{i:0;i:0;}';

        self::assertSame($expected, serialize($step));
    }

    private function getMockedHandler(): MockObject
    {
        // Create a partial mock of AbstractRemoteHandler
        $mockBuilder = $this->getMockBuilder(AbstractStep::class);
        $mockBuilder->onlyMethods(['_execute']);

        return $mockBuilder->getMock();
    }
}
