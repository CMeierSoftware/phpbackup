<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Step\Ajax\AbstractAjaxStep;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\AbstractAjaxStep
 */
final class AbstractAjaxStepTest extends TestCase
{
    private $mock;

    protected function setUp(): void
    {
        $this->mock = $this->getMockForAbstractClass(AbstractAjaxStep::class);

        $this->mock->expects(self::once())
            ->method('getRequiredDataKeys')
            ->willReturn(['key1', 'key2'])
        ;

        $this->mock->expects(self::once())
            ->method('sanitizeData')
            ->willReturn(['sanitized' => 'data'])
        ;
    }

    public function testParsePostDataSuccess()
    {
        $postData = ['key1' => 'value1', 'key2' => 'value2'];

        $mock = $this->getMockForAbstractClass(AbstractAjaxStep::class);

        $mock->expects(self::once())
            ->method('_execute')
            ->willReturn(new StepResult('done'))
        ;

        self::assertSame(['sanitized' => 'data'], $mock->execute($postData));
    }

    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
