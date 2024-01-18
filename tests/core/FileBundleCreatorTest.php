<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Core;

use CMS\PhpBackup\Core\FileBundleCreator;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\FileBundleCreator
 */
final class FileBundleCreatorTest extends TestCase
{
    private const TEST_DIR = TEST_WORK_DIR;

    protected function setUp(): void
    {
        // Create test files in the test directory
        $this->createTestFiles(self::TEST_DIR);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileBundleCreator::createFileBundles()
     */
    public function testCreateFileBundles(): void
    {
        $expectedResult = [
            ['\test_file_large.txt'],
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
            [
                '\sub\test_file_1.txt',
            ],
        ];

        $sizeLimitInMB = 1;

        $fileBundles = [];
        FileBundleCreator::createFileBundles(self::TEST_DIR, $sizeLimitInMB, $fileBundles);

        self::assertSame($expectedResult, $fileBundles);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileBundleCreator::createFileBundles()
     */
    public function testCreateFileBundlesOnEmptyDir(): void
    {
        FileHelper::deleteDirectory(self::TEST_DIR);
        self::assertDirectoryDoesNotExist(self::TEST_DIR);
        FileHelper::makeDir(self::TEST_DIR);
        self::assertDirectoryExists(self::TEST_DIR);
        
        $expectedResult = [];

        $sizeLimitInMB = 1;

        $fileBundles = [];
        FileBundleCreator::createFileBundles(self::TEST_DIR, $sizeLimitInMB, $fileBundles);

        self::assertSame($expectedResult, $fileBundles);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileBundleCreator::createFileBundles()
     */
    public function testCreateFileBundlesExclude(): void
    {
        $expectedResult = [
            ['\test_file_large.txt'],
            [
                '\test_file_3.txt',
                '\test_file_2.txt',
            ],
            ['\sub\test_file_large.txt'],
            [
                '\test_file_1.txt',
                '\test_file_4.txt',
                '\test_file_5.txt',
                '\sub\test_file_3.txt',
                '\sub\test_file_2.txt',
            ],
            [
                '\sub\test_file_1.txt',
                '\sub\test_file_4.txt',
                '\sub\test_file_5.txt',
            ],
        ];

        $this->createTestFiles(self::TEST_DIR . 'sub' . DIRECTORY_SEPARATOR);

        $sizeLimitInMB = 1;

        $fileBundles = [];
        FileBundleCreator::createFileBundles(self::TEST_DIR, $sizeLimitInMB, $fileBundles, ['sub\\sub']);

        self::assertSame($expectedResult, $fileBundles);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileBundleCreator::createFileBundles()
     * 
     */
    public function testMeasureTiming(): void
    {
        $this->markTestSkipped('Run only manually.');
        FileHelper::makeDir(self::TEST_DIR . "sub");
        FileHelper::makeDir(self::TEST_DIR . "sub\\sub");

        for ($i=0; $i < 100; $i++) { 
            $this->createTestFile(self::TEST_DIR . "{$i}.txt", 10);
            $this->createTestFile(self::TEST_DIR . "sub\\{$i}.txt", 10);
            $this->createTestFile(self::TEST_DIR . "sub\\sub\\{$i}.txt", 10);
        }

        $sizeLimitInMB = 1;
        $fileBundles = [];

        $start = microtime(true);
        FileBundleCreator::createFileBundles(self::TEST_DIR, $sizeLimitInMB, $fileBundles);
        $end = microtime(true);
        
        $executionTime = $end - $start;
        self::fail("Execution took {$executionTime} ms.");
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
