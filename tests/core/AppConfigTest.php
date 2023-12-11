<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use PHPUnit\Framework\TestCase;

class AppConfigTest extends TestCase
{
    private const TEST_CONFIG_FILE = ABS_PATH . 'tests\\fixtures\\config\\test.xml';
    private const TEST_EMPTY_CONFIG_FILE = ABS_PATH . 'tests\\fixtures\\config\\empty_config.xml';
    private const TEST_TEMP_DIR = CONFIG_DIR . 'temp_valid_app' . DIRECTORY_SEPARATOR;

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
        rmdir(self::TEST_TEMP_DIR);
        $this->assertEquals(self::TEST_TEMP_DIR, $this->config->getTempDir());
        $this->assertFileExists(self::TEST_TEMP_DIR);
    }

    public function testSaveTempDataSuccessfullySavesDataToFile()
    {
        $type = 'test';
        $data = ['key' => 'value'];

        $result = $this->config->saveTempData($type, $data);

        $this->assertTrue($result);
        $filePath = self::TEST_TEMP_DIR . $type . '.json';
        $this->assertFileExists($filePath);
        $this->assertJsonStringEqualsJsonFile($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function testSaveTempDataThrowsJsonExceptionOnInvalidData()
    {
        $type = 'invalid';
        $data = fopen('php://stdin', 'r');

        $this->expectException(\JsonException::class);
        $this->config->saveTempData($type, $data);
    }


    // readTempData method test cases

    public function testReadTempDataSuccessfullyReadsDataFromFile()
    {
        $type = 'test';
        $data = ['key' => 'value'];

        // Save data to file for testing
        $this->config->saveTempData($type, $data);

        $result = $this->config->readTempData($type);

        $this->assertEquals($data, $result);
    }

    public function testReadTempDataThrowsFileNotFoundExceptionOnFileNotFound()
    {
        $type = 'nonexistent';

        $this->expectException(FileNotFoundException::class);
        $this->config->readTempData($type);
    }

    public function testReadTempDataThrowsJsonExceptionOnInvalidData()
    {
        $type = 'invalid';

        // Save invalid data to file for testing
        file_put_contents($this->config->getTempDir() . $type . '.json', 'invalid_json_data');

        $this->expectException(\JsonException::class);
        $this->config->readTempData($type);
    }

}
