<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\AppConfig;
use PHPUnit\Framework\TestCase;
use Laminas\Config\Config;
use Laminas\Config\Factory as LaminasConfigFactory;
use ReflectionClass;

class AppConfigTest extends TestCase
{
    private const TEST_CONFIG_FILE = ABS_PATH . 'tests\\fixtures\\config\\test.json';

    protected function setUp(): void
    {
        copy(self::TEST_CONFIG_FILE, CONFIG_DIR . '\\valid_app.json');
    }
    
    public function testLoadAppConfigSuccess(): void
    {
        $appConfig = AppConfig::loadAppConfig('valid_app');

        $this->assertInstanceOf(AppConfig::class, $appConfig);
    }

    public function testLoadAppConfigFailure(): void
    {
        $nonExistentAppConfig = AppConfig::loadAppConfig('non_existent_app');
        $this->assertNull($nonExistentAppConfig);
    }

    public function testSaveConfig(): void
    {
        
    }
}

