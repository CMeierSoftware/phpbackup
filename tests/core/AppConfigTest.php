<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\AppConfig;
use PHPUnit\Framework\TestCase;

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

    public function testDirectoryConfig(): void
    {
        $expectedConfig = [
            'src' => '.',
        ];

        $actualConfig = $this->config->getBackupDirectory();

        $this->assertIsArray($actualConfig);

        foreach ($expectedConfig as $key => $value) {
            $this->assertArrayHasKey($key, $actualConfig);
            $this->assertEquals($value, $actualConfig[$key]);
        }
    }

    public function testBackupSettingsConfig(): void
    {
        $expectedConfig = [
            'maxArchiveSize' => '5',
        ];

        $actualConfig = $this->config->getBackupSettings();

        $this->assertIsArray($actualConfig);

        foreach ($expectedConfig as $key => $value) {
            $this->assertArrayHasKey($key, $actualConfig);
            $this->assertEquals($value, $actualConfig[$key]);
        }
    }

    public function testDatabaseConfig(): void
    {
        $expectedConfig = [
            'adapter' => 'pdo_mysql',
            'host' => 'db.example.com',
            'username' => 'dbuser',
            'password' => 'secret',
            'dbname' => 'dbproduction',
        ];

        $actualConfig = $this->config->getBackupDatabase();

        $this->assertIsArray($actualConfig);

        foreach ($expectedConfig as $key => $value) {
            $this->assertArrayHasKey($key, $actualConfig);
            $this->assertEquals($value, $actualConfig[$key]);
        }
    }

    public function testNoDatabaseDefined()
    {
        $config = AppConfig::loadAppConfig('empty_app');
        $actualDatabaseConfig = $config->getBackupDatabase();
        $this->assertNull($actualDatabaseConfig);
    }

    public function testTempDir()
    {
        $tmpDir = CONFIG_DIR . 'temp_valid_app' . DIRECTORY_SEPARATOR;
        rmdir($tmpDir);
        $this->assertEquals($tmpDir, $this->config->getTmp());
        $this->assertFileExists($tmpDir);
    }

}
