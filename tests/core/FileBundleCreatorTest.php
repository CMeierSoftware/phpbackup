<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\FileBundleCreator;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class FileBundleCreatorTest extends TestCase
{
    private const TEST_DIR = ABS_PATH . 'tests\\work\\test_directory\\';

    protected function setUp(): void
    {
        // Create test files in the test directory
        $this->createTestFiles(self::TEST_DIR);

        $files = [
            self::TEST_DIR . 'test_file_large.txt',
            self::TEST_DIR . 'test_file_3.txt',
            self::TEST_DIR . 'test_file_2.txt',
            self::TEST_DIR . 'test_file_1.txt',
            self::TEST_DIR . 'test_file_4.txt',
            self::TEST_DIR . 'test_file_5.txt',
            self::TEST_DIR . 'sub\test_file_3.txt', self::TEST_DIR . 'sub\test_file_2.txt',
            self::TEST_DIR . 'sub\test_file_1.txt',
        ];
        foreach ($files as $file) {
            $this->assertFileExists($file);
        }
    }

    protected function tearDown(): void
    {
        // Clean up: Remove the test files and directory
        FileHelper::deleteDirectory(self::TEST_DIR);
    }

    public function testCreateFileBundles(): void
    {
        $expectedResult = [
            [self::TEST_DIR . 'test_file_large.txt'],
            [
                self::TEST_DIR . 'test_file_3.txt',
                self::TEST_DIR . 'test_file_2.txt',
            ],
            [
                self::TEST_DIR . 'test_file_1.txt',
                self::TEST_DIR . 'test_file_4.txt',
                self::TEST_DIR . 'test_file_5.txt',
            ],
            [
                self::TEST_DIR . 'sub\test_file_3.txt',
                self::TEST_DIR . 'sub\test_file_2.txt',
            ],
            [
                self::TEST_DIR . 'sub\test_file_1.txt',
            ],
        ];

        $sizeLimitInMB = 1;

        // Call the static function to create file bundles
        $fileBundles = FileBundleCreator::createFileBundles(self::TEST_DIR, $sizeLimitInMB);
        // Assert that at least one bundle is created
        $this->assertNotEmpty($fileBundles);
        $this->assertCount(5, $fileBundles);
        $this->assertEquals($expectedResult, $fileBundles);
    }

    private function createTestFiles(string $directory): void
    {
        if (!is_dir($directory)) {
            FileHelper::makeDir($directory);
        }

        // Create 5 test files with random content
        for ($i = 1; $i <= 3; ++$i) {
            file_put_contents($directory . "test_file_{$i}.txt", random_bytes(500 * 1024));
        }
        file_put_contents($directory . 'test_file_4.txt', random_bytes(150 * 1024));
        file_put_contents($directory . 'test_file_5.txt', random_bytes(140 * 1024));
        file_put_contents($directory . 'test_file_large.txt', random_bytes(2000 * 1024));

        if (!is_dir($directory . 'sub')) {
            FileHelper::makeDir($directory . 'sub');
        }
        // Create 5 test files with random content
        for ($i = 1; $i <= 3; ++$i) {
            file_put_contents($directory . "sub/test_file_{$i}.txt", random_bytes(500 * 1024));
        }
    }
}
