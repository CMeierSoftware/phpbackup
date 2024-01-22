<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
abstract class TestCaseWithAppConfig extends TestCase
{
    protected const CONFIG_FILE = CONFIG_DIR . 'app.xml';
    protected const CONFIG_TEMP_DIR = CONFIG_DIR . 'temp_app' . DIRECTORY_SEPARATOR;
    protected const TEST_DIR = TEST_WORK_DIR;

    protected AppConfig $config;

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_DIR);
        FileHelper::deleteDirectory(self::CONFIG_TEMP_DIR);

        unlink(self::CONFIG_FILE);
        parent::tearDown();
    }

    protected function setUpAppConfig(string $configFile, array $replaceTags = []): void
    {
        copy(TEST_FIXTURES_CONFIG_DIR . "{$configFile}.xml", self::CONFIG_FILE);
        self::assertFileExists(self::CONFIG_FILE);

        foreach ($replaceTags as $tag) {
            self::replaceConfigValue($tag['tag'], $tag['value']);
        }

        $this->config = AppConfig::loadAppConfig('app');
    }

    protected function setStepData(array $data)
    {
        $this->config->saveTempData('StepData', $data);
    }

    private function replaceConfigValue(string $tag, string $newValue)
    {
        $content = file_get_contents(self::CONFIG_FILE);
        $content = preg_replace("/<{$tag}>(.*?)<\\/{$tag}>/", "<{$tag}>{$newValue}</{$tag}>", $content);
        file_put_contents(self::CONFIG_FILE, $content);
    }
}
