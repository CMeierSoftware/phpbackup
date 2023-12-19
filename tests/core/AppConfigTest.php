<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;
use Laminas\Config\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\AppConfig
 */
final class AppConfigTest extends TestCase
{
    private const TEST_TEMP_TEST_RESULT = ABS_PATH . 'tests\\fixtures\\config\\temp_test.xml';
    private const TEST_CONFIG_FILE = ABS_PATH . 'tests\\fixtures\\config\\test.xml';
    private const TEST_EMPTY_CONFIG_FILE = ABS_PATH . 'tests\\fixtures\\config\\empty_config.xml';
    private const TEST_NO_DB_CONFIG_FILE = ABS_PATH . 'tests\\fixtures\\config\\test_no_db.xml';
    private const TEST_TEMP_DIR = CONFIG_DIR . 'temp_valid_app' . DIRECTORY_SEPARATOR;

    private AppConfig $config;

    protected function setUp(): void
    {
        copy(self::TEST_EMPTY_CONFIG_FILE, CONFIG_DIR . '\\empty_app.xml');
        copy(self::TEST_CONFIG_FILE, CONFIG_DIR . '\\valid_app.xml');
        copy(self::TEST_NO_DB_CONFIG_FILE, CONFIG_DIR . '\\valid_app_no_db.xml');
        self::assertFileExists(self::TEST_CONFIG_FILE);
        self::assertFileExists(self::TEST_EMPTY_CONFIG_FILE);
        self::assertFileExists(self::TEST_NO_DB_CONFIG_FILE);

        $this->config = AppConfig::loadAppConfig('valid_app');
    }

    protected function tearDown(): void
    {
        // FileHelper::deleteDirectory(self::TEST_TEMP_DIR);
        unlink(CONFIG_DIR . '\\empty_app.xml');
        unlink(CONFIG_DIR . '\\valid_app.xml');
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::loadAppConfig()
     */
    public function testLoadAppConfigSuccess(): void
    {
        $appConfig = AppConfig::loadAppConfig('valid_app');
        self::assertInstanceOf(AppConfig::class, $appConfig);
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::loadAppConfig()
     */
    public function testLoadAppConfigFailure(): void
    {
        self::expectException(FileNotFoundException::class);
        AppConfig::loadAppConfig('non_existent_app');
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::loadAppConfig()
     */
    public function testLoadAppConfigWrongFileFormat(): void
    {
        rename(CONFIG_DIR . '\\valid_app.xml', CONFIG_DIR . '\\valid_app.json');
        self::expectException(FileNotFoundException::class);
        AppConfig::loadAppConfig('valid_app');
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::getBackupDirectory()
     */
    public function testDirectoryConfig(): void
    {
        $expectedConfig = [
            'src' => '.',
        ];

        $actualConfig = $this->config->getBackupDirectory();

        self::assertIsArray($actualConfig);

        foreach ($expectedConfig as $key => $value) {
            self::assertArrayHasKey($key, $actualConfig);
            self::assertSame($value, $actualConfig[$key]);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::getBackupSettings()
     */
    public function testBackupSettingsConfig(): void
    {
        $expectedConfig = [
            'maxArchiveSize' => '5',
            'encryptionKey' => 'some_random_Key',
        ];

        $actualConfig = $this->config->getBackupSettings();

        self::assertIsArray($actualConfig);

        foreach ($expectedConfig as $key => $value) {
            self::assertArrayHasKey($key, $actualConfig);
            self::assertSame($value, $actualConfig[$key]);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::getRemoteSettings()
     */
    public function testRemoteConfig(): void
    {
        $expectedConfig = [
            'local' => ['rootDir' => ''],
            'backblaze' => ['accountId' => 'some_id', 'applicationKey' => 'some_key', 'bucketName' => 'some_name'],
        ];

        $actualConfig = $this->config->getRemoteSettings();

        self::assertIsArray($actualConfig);

        foreach ($expectedConfig as $key => $value) {
            self::assertArrayHasKey($key, $actualConfig);
            self::assertSame($value, $actualConfig[$key]);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::getBackupDatabase()
     */
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

        self::assertIsArray($actualConfig);

        foreach ($expectedConfig as $key => $value) {
            self::assertArrayHasKey($key, $actualConfig);
            self::assertSame($value, $actualConfig[$key]);
        }
    }

    /**
     * @uses \CMS\PhpBackup\Core\AppConfig::loadAppConfig()
     *
     * @covers \CMS\PhpBackup\Core\AppConfig::getBackupDatabase()
     */
    public function testNoDatabaseDefined()
    {
        $config = AppConfig::loadAppConfig('valid_app_no_db');
        $actualDatabaseConfig = $config->getBackupDatabase();
        self::assertNull($actualDatabaseConfig);
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::getTempDir()
     */
    public function testTempDir()
    {
        FileHelper::deleteDirectory(self::TEST_TEMP_DIR);
        self::assertFileDoesNotExist(self::TEST_TEMP_DIR);
        self::assertSame(self::TEST_TEMP_DIR, $this->config->getTempDir());
        self::assertFileExists(self::TEST_TEMP_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::saveTempData()
     */
    public function testSaveTempDataSuccessfullySavesDataToFile()
    {
        $type = 'test';
        $data = [
            'key' => 'value',
            'bundles' => [
                ['item1', 'item2'],
                ['item3', 'item4'],
            ],
        ];

        $this->config->saveTempData($type, $data);

        $filePath = self::TEST_TEMP_DIR . $type . '.xml';
        self::assertFileExists($filePath);
        self::assertXmlFileEqualsXmlFile(self::TEST_TEMP_TEST_RESULT, $filePath);
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::saveTempData()
     */
    public function testSaveTempDataDirSepInTypeName()
    {
        $type = 'test\\test';
        $data = ['key' => 'value'];

        $this->config->saveTempData($type, $data);

        $filePath = self::TEST_TEMP_DIR . 'test_test.xml';
        self::assertFileExists($filePath);
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::saveTempData()
     */
    public function testSaveTempDataThrowsTypeErrorOnInvalidData()
    {
        $type = 'invalid';
        $data = fopen('php://stdin', 'r');

        $this->expectException(\TypeError::class);
        $this->config->saveTempData($type, $data);

        $data = 'string';
        $this->expectException(\TypeError::class);
        $this->config->saveTempData($type, $data);
    }

    /**
     * @uses \CMS\PhpBackup\Core\AppConfig::saveTempData()
     *
     * @covers \CMS\PhpBackup\Core\AppConfig::readTempData()
     */
    public function testReadTempData()
    {
        $type = 'test';
        $data = [
            'key' => 'value',
            'bundles' => [
                ['item1', 'item2'],
                ['item3', 'item4'],
            ],
        ];

        // Save data to file for testing
        $this->config->saveTempData($type, $data);

        $result = $this->config->readTempData($type);

        self::assertSame($data, $result);
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::readTempData()
     */
    public function testReadTempDataThrowsFileNotFoundException()
    {
        $type = 'nonexistent';

        $this->expectException(FileNotFoundException::class);
        $this->config->readTempData($type);
    }

    /**
     * @uses \CMS\PhpBackup\Core\AppConfig::getTempDir()
     *
     * @covers \CMS\PhpBackup\Core\AppConfig::readTempData()
     */
    public function testReadTempDataThrowsRuntimeException()
    {
        $type = 'invalid';

        // Save invalid data to file for testing
        file_put_contents($this->config->getTempDir() . $type . '.xml', 'invalid_xml_data');

        $this->expectException(RuntimeException::class);
        $this->config->readTempData($type);
    }
}
