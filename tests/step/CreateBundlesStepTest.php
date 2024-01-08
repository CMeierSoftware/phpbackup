<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\CreateBundlesStep;
use CMS\PhpBackup\Step\StepResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateBundlesStep
 */
final class CreateBundlesStepTest extends TestCase
{
    public const CONFIG_FILE = CONFIG_DIR . 'app.xml';

    private const TEST_DIR = TEST_WORK_DIR;
    private AppConfig $config;

    protected function setUp(): void
    {
        copy(TEST_FIXTURES_CONFIG_DIR . 'config_create_bundle_step_test.xml', self::CONFIG_FILE);
        self::assertFileExists(self::CONFIG_FILE);
        $testDir = self::TEST_DIR;
        $content = str_replace('<src>.</src>', "<src>{$testDir}</src>", file_get_contents(self::CONFIG_FILE));
        file_put_contents(self::CONFIG_FILE, $content);

        $this->config = AppConfig::loadAppConfig('app');

        $this->createTestFiles(self::TEST_DIR);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_DIR);
        unlink(CONFIG_DIR . DIRECTORY_SEPARATOR . 'app.xml');
    }

    public function testFileBundle()
    {
        $expectedResult = [
            '\test_file_large.txt',
            [
                '\test_file_3.txt',
                '\test_file_2.txt',
            ],
            [
                '\test_file_1.txt',
                '\test_file_4.txt',
                '\test_file_5.txt',
            ],
            [
                '\sub\test_file_3.txt',
                '\sub\test_file_2.txt',
            ],
            '\sub\test_file_1.txt',
        ];

        $step = new CreateBundlesStep($this->config);

        $fileBundles = $this->config->readTempData('StepData')['bundles'];

        $step->execute();

        self::assertNotEmpty($fileBundles);
        self::assertCount(5, $fileBundles);
        self::assertSame($expectedResult, $fileBundles);
    }

    public function testBackupDir()
    {
        $step = new CreateBundlesStep($this->config);

        $result = $step->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);
        self::assertDirectoryExists($result->returnValue);
    }

    private function createTestFiles(string $directory): void
    {
        FileHelper::makeDir($directory);

        // Create 5 test files with random content
        for ($i = 1; $i <= 3; ++$i) {
            $this->createTestFile("{$directory}test_file_{$i}.txt", 500 * 1024);
        }

        $this->createTestFile("{$directory}test_file_4.txt", 150 * 1024);
        $this->createTestFile("{$directory}test_file_5.txt", 140 * 1024);
        $this->createTestFile("{$directory}test_file_large.txt", 2000 * 1024);

        $subDirectory = "{$directory}sub" . DIRECTORY_SEPARATOR;
        FileHelper::makeDir($subDirectory);

        for ($i = 1; $i <= 3; ++$i) {
            $subFileName = "{$subDirectory}test_file_{$i}.txt";
            $this->createTestFile($subFileName, 500 * 1024);
        }
    }

    /**
     * Create a test file with random content and assert its existence.
     */
    private function createTestFile(string $fileName, int $size): void
    {
        file_put_contents($fileName, random_bytes($size));
        self::assertFileExists($fileName);
    }
}
