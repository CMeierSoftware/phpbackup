<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Backup;

use CMS\PhpBackup\Backup\DatabaseBackupCreator;
use CMS\PhpBackup\Exceptions\ShellCommandUnavailableException;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Backup\DatabaseBackupCreator
 */
final class DatabaseBackupCreatorTest extends TestCase
{
    private const HOST = 'localhost';
    private const USERNAME = 'root';
    private const PASSWORD = '';
    private const DB = 'test';
    private $backupCreator;

    protected function setUp(): void
    {
        $this->backupCreator = new DatabaseBackupCreator(self::HOST, self::USERNAME, self::PASSWORD, self::DB);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEMP_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Backup\DatabaseBackupCreator::backupMySql()
     */
    public function testInvalidCompressionMode()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->backupCreator->backupMySql('INVALID');
    }

    /**
     * @covers \CMS\PhpBackup\Backup\DatabaseBackupCreator::backupMySql()
     */
    public function testMysqldumpUnavailable()
    {
        putenv('MYSQLDUMP_EXE=invalid');
        $backupCreator = new DatabaseBackupCreator(self::HOST, self::USERNAME, self::PASSWORD, self::DB);
        $this->expectException(ShellCommandUnavailableException::class);
        $backupCreator->backupMySql();
    }

    /**
     * @covers \CMS\PhpBackup\Backup\DatabaseBackupCreator::backupMySql()
     */
    public function testBackup()
    {
        $backupFile = $this->backupCreator->backupMySql('None');

        $dtPattern = '/^backup_database_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/';
        self::assertMatchesRegularExpression($dtPattern, basename($backupFile));
        self::assertFileExists($backupFile);
        self::assertNotEmpty(file_get_contents($backupFile));
    }

    /**
     * @covers \CMS\PhpBackup\Backup\DatabaseBackupCreator::backupMySql()
     */
    public function testBackupNotExistentCompressionMethod()
    {
        // zlib is not available in path on xampp
        $backupFile = $this->backupCreator->backupMySql('zlib');

        $dtPattern = '/^backup_database_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/';
        self::assertMatchesRegularExpression($dtPattern, basename($backupFile));
        self::assertFileExists($backupFile);
        self::assertNotEmpty(file_get_contents($backupFile));
    }

    /**
     * @covers \CMS\PhpBackup\Backup\DatabaseBackupCreator::backupMySql()
     */
    public function testDatabaseConnectionFailed()
    {
        // Replace these values with incorrect database connection details
        $host = 'incorrect_host';
        $username = 'incorrect_username';
        $password = 'incorrect_password';
        $database = 'incorrect_database';

        $backupCreator = new DatabaseBackupCreator($host, $username, $password, $database);
        $this->expectException(\mysqli_sql_exception::class);
        $backupCreator->backupMySql();
    }
}
