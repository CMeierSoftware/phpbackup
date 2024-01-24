<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\CreateBundlesStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateBundlesStep
 */
final class CreateBundlesStepTest extends TestCaseWithAppConfig
{
    protected function setUp(): void
    {
        $this->setUpAppConfig(
            'config_create_bundle_step_test',
            [['tag' => 'src', 'value' => '../tests/work']]
        );

        $this->createTestFileStructure(self::TEST_DIR);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testBackupDirectoryCreation()
    {
        $step = new CreateBundlesStep();

        $result = $step->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);
        self::assertDirectoryExists($result->returnValue);
    }

    public function testFileBundlesAreCreatedCorrectly()
    {
        $expectedResult = [
            ['\test_file_5.txt'],
            [
                '\test_file_2.txt',
                '\test_file_1.txt',
            ],
            ['\sub\test_file_5.txt'],
            [
                '\test_file_0.txt',
                '\test_file_3.txt',
                '\test_file_4.txt',
                '\sub\test_file_3.txt',
            ],
            [
                '\sub\test_file_2.txt',
                '\sub\test_file_1.txt',
            ],
            [
                '\sub\test_file_0.txt',
                '\sub\test_file_4.txt',
            ],
        ];

        $step = new CreateBundlesStep();

        $step->execute();

        $fileBundles = $this->getStepData()['bundles'];

        self::assertNotEmpty($fileBundles);
        self::assertCount(count($expectedResult), $fileBundles);
        self::assertSame($expectedResult, $fileBundles);
    }

    private function createTestFileStructure(string $directory): void
    {
        $fileSizes = [
            500 * 1024 => 3,
            150 * 1024 => 1,
            140 * 1024 => 1,
            2000 * 1024 => 1,
        ];

        $this->createTestFiles($directory, $fileSizes);

        $directory = "{$directory}sub" . DIRECTORY_SEPARATOR;
        $this->createTestFiles($directory, $fileSizes);
    }

    private function createTestFiles(string $directory, array $fileSizes): void
    {
        FileHelper::makeDir($directory);
        self::assertDirectoryExists($directory);

        $idx = 0;
        foreach ($fileSizes as $size => $count) {
            for ($i = 0; $i < $count; ++$i) {
                $f = "{$directory}test_file_{$idx}.txt";
                file_put_contents($f, random_bytes($size));
                self::assertFileExists($f);
                ++$idx;
            }
        }
    }
}
