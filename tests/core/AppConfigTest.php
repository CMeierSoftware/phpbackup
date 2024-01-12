<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Core;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\Remote\AbstractRemoteDeleteOldFilesStep;
use CMS\PhpBackup\Step\Remote\BackblazeRemoteDeleteOldFilesStep;
use CMS\PhpBackup\Step\Remote\LocalRemoteDeleteOldFilesStep;
use Laminas\Config\Exception\RuntimeException;
use Laminas\Config\Exception\UnprocessableConfigException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\AppConfig
 */
final class AppConfigTest extends TestCase
{
    private const TEST_TEMP_TEST_RESULT = TEST_FIXTURES_CONFIG_DIR . 'test_temp_data.xml';
    private const TEST_TEMP_DIR = CONFIG_DIR . 'temp_valid_app' . DIRECTORY_SEPARATOR;
    private const APPS = [
        'valid_app' => TEST_FIXTURES_CONFIG_DIR . 'config_full_valid.xml',
        'empty_app' => TEST_FIXTURES_CONFIG_DIR . 'config_empty.xml',
        'empty_elements' => TEST_FIXTURES_CONFIG_DIR . 'config_empty_elements.xml',
        'valid_app_no_db' => TEST_FIXTURES_CONFIG_DIR . 'config_no_db.xml',
        'valid_app_no_remote' => TEST_FIXTURES_CONFIG_DIR . 'config_no_remote.xml',
    ];

    private AppConfig $config;

    protected function setUp(): void
    {
        FileHelper::makeDir(CONFIG_DIR);
        foreach (self::APPS as $configName => $sourceFile) {
            $destinationFile = CONFIG_DIR . $configName . '.xml';
            copy($sourceFile, $destinationFile);
            self::assertFileExists($destinationFile);
        }

        $this->config = AppConfig::loadAppConfig('valid_app');
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_TEMP_DIR);
        FileHelper::deleteDirectory(TEMP_DIR);
        FileHelper::deleteDirectory(CONFIG_DIR);
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
    public function testLoadAppConfigEmpty(): void
    {
        self::expectException(UnprocessableConfigException::class);
        AppConfig::loadAppConfig('empty_app');
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
        rename(CONFIG_DIR . 'valid_app.xml', CONFIG_DIR . 'valid_app.json');
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
            'keepBackupAmount' => '2',
            'keepBackupDays' => '0',
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
     * @covers \CMS\PhpBackup\Core\AppConfig::getDefinedRemoteClasses()
     */
    public function testDefinedRemoteClasses(): void
    {
        $classes = [
            LocalRemoteDeleteOldFilesStep::class,
            BackblazeRemoteDeleteOldFilesStep::class,
        ];
        $result = $this->config->getDefinedRemoteClasses(AbstractRemoteDeleteOldFilesStep::class);
        self::assertSame($classes, $result);
    }

    /**
     * @uses \CMS\PhpBackup\Core\AppConfig::loadAppConfig()
     *
     * @covers \CMS\PhpBackup\Core\AppConfig::getDefinedRemoteClasses()
     */
    public function testDefinedRemoteClassesNoRemote(): void
    {
        $config = AppConfig::loadAppConfig('valid_app_no_remote');
        self::assertEmpty($config->getDefinedRemoteClasses(AbstractRemoteDeleteOldFilesStep::class));
    }

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::getBackupDatabase()
     */
    public function testDatabaseConfig(): void
    {
        $expectedConfig = [
            'adapter' => 'pdo_mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'dbname' => 'test',
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
        self::assertDirectoryDoesNotExist(self::TEST_TEMP_DIR);
        self::assertSame(self::TEST_TEMP_DIR, $this->config->getTempDir());
        self::assertDirectoryExists(self::TEST_TEMP_DIR);
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
                'a' => ['item1', 'item2'],
                'b' => ['item3', 'item4'],
                'c' => ['c1' => ['item5', 'item6'], 'c2' => ['item7', 'item8']],
            ],
            'archives' => [
                ['item1', 'item2'],
                ['item3', 'item4'],
                [['item5', 'item6'], ['item7', 'item8']],
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

        $data = 'string';
        $this->expectException(\TypeError::class);
        $this->config->saveTempData($type, $data);
    }

    /**
     * @uses \CMS\PhpBackup\Core\AppConfig::getTempDir()
     *
     * @covers \CMS\PhpBackup\Core\AppConfig::readTempData()
     */
    public function testReadTempData()
    {
        $type = 'test';
        $data = [
            'key' => 'value',
            'bundles' => [
                'a' => ['item1', 'item2'],
                'b' => ['item3', 'item4'],
                'c' => ['c1' => ['item5', 'item6'], 'c2' => ['item7', 'item8']],
            ],
            'archives' => [
                ['item1', 'item2'],
                ['item3', 'item4'],
                [['item5', 'item6'], ['item7', 'item8']],
            ],
        ];

        $filePath = $this->config->getTempDir() . $type . '.xml';
        copy(self::TEST_TEMP_TEST_RESULT, $filePath);
        self::assertFileExists($filePath);

        $result = $this->config->readTempData($type);

        self::assertSame($data, $result);
    }

    /**
     * @uses \CMS\PhpBackup\Core\AppConfig::getTempDir()
     *
     * @covers \CMS\PhpBackup\Core\AppConfig::readTempData()
     */
    public function testReadTempDataOnEmptyData()
    {
        $type = 'test';

        $data = [];
        $this->config->saveTempData($type, $data);
        $result = $this->config->readTempData($type);

        self::assertSame([], $result);
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

    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::toAbsolutePath()
     * 
     * @dataProvider dataProviderTestToAbsolutePathOwnBase
     */
    public function testToAbsolutePathOwnBase(string $expect, string $relPath, string $base)
    {
        self::assertSame($expect, $this->config->toAbsolutePath($relPath, $base));
    }

    public function dataProviderTestToAbsolutePathOwnBase()
    {
        return [
            [self::TEST_TEMP_DIR, self::TEST_TEMP_DIR, ''],
            [self::TEST_TEMP_DIR, self::TEST_TEMP_DIR . 'seven/..\\', ''],
            [self::TEST_TEMP_DIR, '', self::TEST_TEMP_DIR],
            [self::TEST_TEMP_DIR, '.', self::TEST_TEMP_DIR],
            [self::TEST_TEMP_DIR, './six/../', self::TEST_TEMP_DIR],
            [self::TEST_TEMP_DIR, '.\\six\\..', self::TEST_TEMP_DIR],
        ];
    }
    /**
     * @covers \CMS\PhpBackup\Core\AppConfig::toAbsolutePath()
     * 
     * @dataProvider dataProviderTestToAbsolutePathConfigBase
     */
    public function testToAbsolutePathConfigBase(string $expect, string $relPath)
    {
        self::assertSame($expect, $this->config->toAbsolutePath($relPath));
    }

    public function dataProviderTestToAbsolutePathConfigBase()
    {
        return [
            [CONFIG_DIR, ''],
            [CONFIG_DIR, '.'],
            [CONFIG_DIR, '\\.\\'],
            [CONFIG_DIR, '\\\\.//'],
            [CONFIG_DIR, './six/../'],
            [CONFIG_DIR, '.\\six\\..'],
            [CONFIG_DIR . 't1\\', '.\\six\\../t1'],
        ];
    }
}
