<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Helper;

use CMS\PhpBackup\Helper\FileLogger;
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
        $this->mysqldumpExe = getenv('MYSQLDUMP_EXE') ?: 'mysqldump';
    }

    /**
     * Creates a backup of the specified MySQL database using mysqldump.
     *
     * @param string $compressionMode The compression mode (default is 'zlib')
     *
     * @return string The backup filename if the backup was successful
     *
     * @throws ShellCommandUnavailableException If mysqldump is not available
     * @throws \mysqli_sql_exception If the database connection fails
     * @throws \UnexpectedValueException If an invalid compression mode is provided
     * @throws \Exception If the backup fails
     */
    public function backupMySql(string $compressionMode = 'zlib'): string
    {
        if (!$this->isMysqldumpAvailable()) {
            throw new ShellCommandUnavailableException('mysqldump is not available. please provide a correct path.');
        }

        if (!mysqli_connect($this->host, $this->username, $this->password, $this->database)) {
            throw new \mysqli_sql_exception('Database connection failed: ' . mysqli_connect_error());
        }

        list($compExt, $compCmd) = $this->getCompressionCommand($compressionMode);

        // Set the name of the backup file with timestamp
        $backupFile = TEMP_DIR . 'backup_database_' . date('Y-m-d_H-i-s') . '.sql' . $compExt;

        $command = "{$this->mysqldumpExe} --user={$this->username} --password={$this->password} --host={$this->host} {$this->database} {$compCmd} > {$backupFile}";

        try {
            $output = shell_exec($command);

            if (empty($output)) {
                FileLogger::getInstance()->info("MySQL backup created at '{$backupFile}'.");

                return $backupFile;
            }

            throw new \mysqli_sql_exception($output);
        } catch (\Exception $e) {
            throw new \Exception('Backup failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Function to check if mysqldump is available.
     *
     * @return bool True if mysqldump is available, false otherwise
     */
    private function isMysqldumpAvailable(): bool
    {
        $output = self::getVersionInfo($this->mysqldumpExe);

        return str_starts_with($output, 'mysqldump.exe  Ver ') || str_starts_with($output, 'mysqldump  Ver ');
    }

    private static function getCompressionCommand(string $mode): array
    {
        $fileExtension = '';
        $command = '';

        if ('zlib' === $mode) {
            $exe = getenv('GZIP_CMD') ?: 'gzip';
            $pattern = '/^gzip \d+(\.\d+)?/';
            if (self::isCompressionAvailable($exe, $pattern)) {
                $backupFile .= '.gz';
                $command = " | {$exe} ";
            }
        } elseif ('None' !== $mode) {
            throw new \UnexpectedValueException("Invalid compression mode '{$mode}'.");
        }

        return [$fileExtension, $command];
    }

    private static function getVersionInfo($command): string
    {
        return shell_exec("{$command} --version 2>&1");
    }

    private static function isCompressionAvailable($cmd, $regex): bool
    {
        $output = self::getVersionInfo($cmd);
        $result = 1 === preg_match($regex, $output);
        if (!$result) {
            FileLogger::getInstance()->info("Compression mode {$cmd} not available.");
        }

        return $result;
    }
}
