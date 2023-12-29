<?php

declare(strict_types=1);

use CMS\PhpBackup\Backup\FileBackupCreator;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Backup\FileBackupCreator
 */
final class FileBackupCreatorTest extends TestCase
{
    private FileBackupCreator $backupCreator;

    protected function setUp(): void
    {
        $this->backupCreator = new FileBackupCreator();
    }

    /**
     * @covers \CMS\PhpBackup\Backup\FileBackupCreator::backupAll()
     */
    public function testBackupInvalidDir(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->backupCreator->backupAll(TEST_FIXTURES_DIR . 'invalid');
    }

    /**
     * @covers \CMS\PhpBackup\Backup\FileBackupCreator::backupAll()
     */
    public function testBackupIgnoreList(): void
    {
        $backupCreator = new FileBackupCreator([basename(TEST_FIXTURES_FILE_1), 'picture1.png']);

        $filename = $backupCreator->backupAll(TEST_FIXTURES_FILE_DIR);
        self::assertFileExists($filename);
        self::assertStringStartsWith(TEMP_DIR . 'backup_' . basename(TEST_FIXTURES_FILE_DIR), $filename);
        self::assertStringEndsWith('.zip', $filename);
        // check manually if the files are not included
    }

    /**
     * @covers \CMS\PhpBackup\Backup\FileBackupCreator::backupAll()
     */
    public function testBackupFileName(): void
    {
        try {
            $filename = $this->backupCreator->backupAll(TEST_FIXTURES_FILE_DIR);
            self::assertFileExists($filename);
            self::assertStringStartsWith(TEMP_DIR . 'backup_' . basename(TEST_FIXTURES_FILE_DIR), $filename);
            self::assertStringEndsWith('.zip', $filename);
        } finally {
            unlink($filename);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Backup\FileBackupCreator::backupOnly()
     */
    public function testBackupOnly(): void
    {
        try {
            $files = ['file1.txt', 'pictures\\pics1.txt', 'pictures\\others\\others.1.txt', 'pictures\\others\\others2.txt'];
            $filename = $this->backupCreator->backupOnly(TEST_FIXTURES_DIR . '\zip', $files);
            self::assertFileExists($filename);
            self::assertStringStartsWith(TEMP_DIR . 'backup_zip', $filename);
            self::assertStringEndsWith('.zip', $filename);
        } finally {
            unlink($filename);
        }
    }
}
