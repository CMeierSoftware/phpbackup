<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Step;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Step\CleanUpStep;
use CMS\PhpBackup\Step\StepResult;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\CreateBundlesStep
 */
final class CleanUpStepTest extends TestCaseWithAppConfig
{
    private array $data = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAppConfig('config_full_valid');

        $this->data = ['backupDirectory' => self::TEST_DIR];

        FileHelper::makeDir(self::TEST_DIR);
        copy(TEST_FIXTURES_FILE_1, self::TEST_DIR . basename(TEST_FIXTURES_FILE_1));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testReturnValues()
    {
        $step = new CleanUpStep(null);

        self::assertNotEmpty($this->data);
        $step->setData($this->data);
        $result = $step->execute();

        self::assertInstanceOf(StepResult::class, $result);
        self::assertFalse($result->repeat);
        self::assertSame('Backup process done.', $result->returnValue);
    }

    public function testDeleteBackupDirectory()
    {
        self::assertDirectoryExists(self::TEST_DIR);

        $step = new CleanUpStep(null);

        self::assertNotEmpty($this->data);
        $step->setData($this->data);
        $step->execute();

        self::assertDirectoryDoesNotExist(self::TEST_DIR);
    }

    public function testCleanStepData()
    {
        $step = new CleanUpStep(null);

        self::assertNotEmpty($this->data);
        $step->setData($this->data);
        $step->execute();

        self::assertEmpty($this->data);
    }

    /**
     * @covers \CMS\PhpBackup\Step\CleanUpStep::loadData()
     *
     * @dataProvider provideLoadDataMissingDataCases
     */
    public function testLoadDataMissingData(array $keysToSet, string $missingKey)
    {
        $data = array_fill_keys($keysToSet, 'some value');
        $step = new CleanUpStep(null);
        $step->setData($data);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage("Missing required keys: {$missingKey}");
        $step->execute();
    }

    public static function provideLoadDataMissingDataCases(): iterable
    {
        $requiredKeys = ['backupDirectory'];
        $returnValue = [];

        foreach ($requiredKeys as $keyIndex => $key) {
            $rowKeys = array_diff($requiredKeys, [$key]);
            $returnValue[] = [$rowKeys, $key];
        }

        return $returnValue;
    }
}
