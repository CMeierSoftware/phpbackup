<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class TestCaseWithAppConfig extends TestCase
{
    protected const CONFIG_FILE = CONFIG_DIR . 'app.xml';
    protected const CONFIG_TEMP_DIR = CONFIG_DIR . 'temp_app';
    protected const TEST_DIR = TEST_WORK_DIR;

    protected AppConfig $config;

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_DIR);
        FileHelper::deleteDirectory(self::CONFIG_TEMP_DIR);

        unlink(self::CONFIG_FILE);
        parent::tearDown();
    }

    protected function setUpAppConfig(string $configFile, string $backupPath = '.'): void
    {
        copy(TEST_FIXTURES_CONFIG_DIR . "{$configFile}.xml", self::CONFIG_FILE);
        self::assertFileExists(self::CONFIG_FILE);

        $content = file_get_contents(self::CONFIG_FILE);
        $content = str_replace('<src>.</src>', "<src>{$backupPath}</src>", $content);
        file_put_contents(self::CONFIG_FILE, $content);

        $this->config = AppConfig::loadAppConfig('app');
    }

    protected function setStepData(array $data)
    {
        $this->config->saveTempData('StepData', $data);
    }
}
