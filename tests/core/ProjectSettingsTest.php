<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\ProjectSettings;
use PHPUnit\Framework\TestCase;

class ProjectSettingsTest extends TestCase
{
    /**
     * @covers \getInstance
     */
    public function testSingletonInstance()
    {
        $instance1 = ProjectSettings::getInstance();
        $instance2 = ProjectSettings::getInstance();

        $this->assertInstanceOf(ProjectSettings::class, $instance1);
        $this->assertInstanceOf(ProjectSettings::class, $instance2);
        $this->assertEquals($instance1, $instance2);
    }
}
