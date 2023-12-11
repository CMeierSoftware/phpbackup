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
    private const TEST_CONFIG_FILE = ABS_PATH . 'tests\\fixtures\\config\\test.xml';
    private const TEST_EMPTY_CONFIG_FILE = ABS_PATH . 'tests\\fixtures\\config\\empty_config.xml';
    private AppConfig $config;

    protected function setUp(): void
    {
        copy(self::TEST_EMPTY_CONFIG_FILE, CONFIG_DIR . '\\empty_app.xml');
        copy(self::TEST_CONFIG_FILE, CONFIG_DIR . '\\valid_app.xml');
        $this->config = AppConfig::loadAppConfig('valid_app');
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

    public function testDatabaseConfig(): void
    {
        $expectedDatabaseConfig = [
            'adapter' => 'pdo_mysql',
            'host' => 'db.example.com',
            'username' => 'dbuser',
            'password' => 'secret',
            'dbname' => 'dbproduction',
        ];

        $actualDatabaseConfig = $this->config->getDatabase();

        $this->assertIsArray($actualDatabaseConfig);

        foreach ($expectedDatabaseConfig as $key => $value) {
            $this->assertArrayHasKey($key, $actualDatabaseConfig);
            $this->assertEquals($value, $actualDatabaseConfig[$key]);
        }
    }

    public function testNoDatabaseDefined()
    {
        $config = AppConfig::loadAppConfig('empty_app');
        $actualDatabaseConfig = $config->getDatabase();
        $this->assertNull($actualDatabaseConfig);
    }

}
