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
class FileBackupCreatorTest extends TestCase
{
    private const FIXTURES_DIR = ABS_PATH . '\tests\fixtures';
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
        $this->backupCreator->backupAll('invalid');
        $this->expectException(FileNotFoundException::class);
        $this->backupCreator->backupAll(self::FIXTURES_DIR . '\invalid');
    }

    /**
     * @covers \CMS\PhpBackup\Backup\FileBackupCreator::backupAll()
     */
    public function testBackupIgnoreList(): void
    {
        $backupCreator = new FileBackupCreator(['file1.txt', 'picture1.png']);

        $filename = $backupCreator->backupAll(self::FIXTURES_DIR . '\zip');
        $this->assertFileExists($filename);
        $this->assertStringStartsWith(TEMP_DIR . 'backup_zip', $filename);
        $this->assertStringEndsWith('.zip', $filename);
        // check manually if the files are not included
    }

    /**
     * @covers \CMS\PhpBackup\Backup\FileBackupCreator::backupAll()
     */
    public function testBackupFileName(): void
    {
        try {
            $filename = $this->backupCreator->backupAll(self::FIXTURES_DIR . '\zip');
            $this->assertFileExists($filename);
            $this->assertStringStartsWith(TEMP_DIR . 'backup_zip', $filename);
            $this->assertStringEndsWith('.zip', $filename);
        } finally {
            unlink($filename);
        }
    }

    /**
     * @covers \CMS\PhpBackup\Backup\FileBackupCreator::backupOnly()
     */
    public function testBackupOnly(): void
    {
        $files = ['file1.txt', 'pictures\\pics1.txt', 'pictures\\others\\others.1.txt', 'pictures\\others\\others2.txt'];
        $filename = $this->backupCreator->backupOnly(self::FIXTURES_DIR . '\zip', $files);
        $this->assertFileExists($filename);
        $this->assertStringStartsWith(TEMP_DIR . 'backup_zip', $filename);
        $this->assertStringEndsWith('.zip', $filename);
    }
}
