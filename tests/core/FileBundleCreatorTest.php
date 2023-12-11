<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\FileBundleCreator;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use PHPUnit\Framework\TestCase;

class FileBundleCreatorTest extends TestCase
{
    private const TEST_DIR = ABS_PATH . 'tests\\work\\test_directory';

    protected function setUp(): void
    {
        // Create test files in the test directory
        $this->createTestFiles(self::TEST_DIR);
    }

    protected function tearDown(): void
    {
        // Clean up: Remove the test files and directory
        //$this->removeTestFiles(self::TEST_DIR);
    }

    public function testCreateFileBundles(): void
    {
        $expectedResult = [
            ['B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\test_file_large.txt',],
            [
                'B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\test_file_3.txt',
                'B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\test_file_2.txt',
            ],
            [
                'B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\test_file_1.txt',
                'B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\test_file_4.txt',
                'B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\test_file_5.txt',
            ],
            [
                'B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\sub\test_file_3.txt',
                'B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\sub\test_file_2.txt',
            ],
            [
                'B:\Christoph\Projects\PHP\phpbackup\tests\work\test_directory\sub\test_file_1.txt'
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
            mkdir($directory);
        }

        // Create 5 test files with random content
        for ($i = 1; $i <= 3; $i++) {
            file_put_contents("$directory/test_file_$i.txt", random_bytes(500 * 1024));
        }
        file_put_contents("$directory/test_file_4.txt", random_bytes(150 * 1024));
        file_put_contents("$directory/test_file_5.txt", random_bytes(140 * 1024));
        file_put_contents("$directory/test_file_large.txt", random_bytes(2000 * 1024));


        if (!is_dir("$directory/sub")) {
            mkdir("$directory/sub");
        }
        // Create 5 test files with random content
        for ($i = 1; $i <= 3; $i++) {
            file_put_contents("$directory/sub/test_file_$i.txt", random_bytes(500 * 1024));
        }
    }

    private function removeTestFiles(string $directory): void
    {
        $files = glob("$directory/*");

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($directory);
    }
}
