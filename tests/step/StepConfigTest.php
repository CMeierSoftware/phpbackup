<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

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
 * @covers \CMS\PhpBackup\Core\StepConfig
 */
final class StepConfigTest extends TestCase
{
    /**
     * @covers \CMS\PhpBackup\Core\StepConfig::__construct()
     * @dataProvider provideInvalidStepValue
     */
    public function testInvalidStepValueClass(string $step, $delay)
    {
        $this->expectException(\UnexpectedValueException::class);
        new StepConfig($step, $delay);
    }

    public static function provideInvalidStepValue()
    {
        $invalidClassNames = ['nonExistent', \stdClass::class, 'null'];
        $invalidDelays = [-1];
        $validDelays = [0, 1, 20, PHP_INT_MAX - 1];

        $result = [];

        foreach ($invalidClassNames as $className) {
            foreach (array_merge($invalidDelays, $validDelays) as $delay) {
                $result[] = [$className, $delay];
            }
        }

        foreach ($invalidDelays as $delay) {
            $result[] = [StepClass::class, $delay];
        }

        return $result;
    }
}

