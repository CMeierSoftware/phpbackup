<?php

declare(strict_types=1);

use CMS\PhpBackup\Backup\DatabaseBackupCreator;
use CMS\PhpBackup\Exceptions\ShellCommandUnavailableException;
use PHPUnit\Framework\TestCase;

class DatabaseBackupCreatorTest extends TestCase
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

    /**
     * @covers \backupMySql
     */
    public function testInvalidCompressionMode()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->backupCreator->backupMySql('INVALID');
    }
    

    /**
     * @covers \backupMySql
     */
    public function testMysqldumpUnavailable()
    {        
        putenv("MYSQLDUMP_EXE=invalid");
        $backupCreator = new DatabaseBackupCreator(self::HOST, self::USERNAME, self::PASSWORD, self::DB);
        $this->expectException(ShellCommandUnavailableException::class);
        $backupCreator->backupMySql();
    }
    
    /**
     * @covers \backupMySql
     */
    public function testBackup()
    {
        try {
            $backupFile = $this->backupCreator->backupMySql('None');
            // Assert that the backup file exists
            $this->assertStringEndsWith('.sql', $backupFile);
            $this->assertFileExists($backupFile);
            $this->assertNotEmpty(file_get_contents($backupFile));
        } finally {
            unlink($backupFile);
        }
    }

    /**
     * @covers \backupMySql
     */
    public function testDatabaseConnectionFailed()
    {
        // Replace these values with incorrect database connection details
        $host = 'incorrect_host';
        $username = 'incorrect_username';
        $password = 'incorrect_password';
        $database = 'incorrect_database';

        $backupCreator = new DatabaseBackupCreator($host, $username, $password, $database);
        $this->expectException(mysqli_sql_exception::class);
        $backupCreator->backupMySql();
    }
}
