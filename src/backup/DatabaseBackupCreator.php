<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Backup;

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Exceptions\ShellCommandUnavailableException;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Class DatabaseBackupCreator.
 */
class DatabaseBackupCreator
{
    private readonly string $host;
    private readonly string $username;
    private readonly string $password;
    private readonly string $database;
    private readonly string $mysqldumpExe;

    /**
     * Constructs a new Backup object with the specified database connection details.
     *
     * @param string $host The database host
     * @param string $username The database username
     * @param string $password The database password
     * @param string $database The database name
     */
    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->mysqldumpExe = !empty(getenv('MYSQLDUMP_EXE')) ? getenv('MYSQLDUMP_EXE') : 'mysqldump';
    }

    /**
     * Creates a backup of the specified MySQL database using mysqldump.
     *
     * @param string $compression_mode The compression mode (default is 'zlib')
     *
     * @return string The backup filename if the backup was successful
     *
     * @throws ShellCommandUnavailableException If mysqldump is not available
     * @throws \mysqli_sql_exception If the database connection fails
     * @throws \UnexpectedValueException If an invalid compression mode is provided
     * @throws \Exception If the backup fails
     */
    public function backupMySql(string $compression_mode = 'zlib'): string
    {
        if (!$this->isMysqldumpAvailable()) {
            throw new ShellCommandUnavailableException('mysqldump is not available. please provide a correct path.');
        }

        $conn = mysqli_connect($this->host, $this->username, $this->password, $this->database);
        if (!$conn) {
            throw new \mysqli_sql_exception('Database connection failed: ' . mysqli_connect_error());
        }

        // Set the name of the backup file with timestamp
        $backupFile = TEMP_DIR . 'backup_' . date('Y-m-d_H-i-s') . '.sql';

        $compression = '';

        if (extension_loaded('zlib') && 'zlib' === $compression_mode) {
            // Use gzip compression
            $backupFile .= '.gz';
            $compression = ' | gzip ';
        } elseif (extension_loaded('bz2') && 'bz2' === $compression_mode) {
            // Use bzip2 compression
            $backupFile .= '.bz2';
            $compression = ' | bzip2 ';
        } elseif ('None' !== $compression_mode) {
            throw new \UnexpectedValueException('Invalid compression mode or compression mode not available.');
        }

        try {
            // Construct the mysqldump command
            $command = "{$this->mysqldumpExe} --user={$this->username} --password={$this->password} --host={$this->host} {$this->database} {$compression} > {$backupFile}";

            $output = shell_exec($command);

            if (empty($output)) {
                FileLogger::getInstance()->info("MySQL backup created at '{$backupFile}'.");

                return $backupFile;
            }

            throw new \Exception($output);
        } catch (\Exception $e) {
            throw new \Exception('Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Function to check if mysqldump is available.
     *
     * @return bool True if mysqldump is available, false otherwise
     */
    private function isMysqldumpAvailable(): bool
    {
        $output = shell_exec("{$this->mysqldumpExe} --version 2>&1");

        return str_starts_with($output, 'mysqldump.exe  Ver ') || str_starts_with($output, 'mysqldump  Ver ');
    }
}
