<?php declare(strict_types= 1);

namespace CMS\Phpbackup\Tests;

use PHPUnit\Framework\TestCase;
use CMS\PhpBackup\Core\ProjectSettings;

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