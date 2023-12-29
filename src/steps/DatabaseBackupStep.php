<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Backup\DatabaseBackupCreator;
use CMS\PhpBackup\Core\FileCrypt;

if (!defined('ABS_PATH')) {
    return;
}

final class DatabaseBackupStep extends AbstractStep
{
    private readonly array $dbConfig;
    private readonly string $backupFolder;
    private readonly string $encryptionKey;

    public function __construct(array $dbConfig, string $backupFolder, string $encryptionKey, int $delay = 0)
    {
        parent::__construct($delay);
        $this->dbConfig = $dbConfig;
        $this->backupFolder = $backupFolder;
        $this->encryptionKey = $encryptionKey;
    }

    protected function _execute(): StepResult
    {
        $this->logger->Info("start database dump of ({$this->dbConfig['host']}, {$this->dbConfig['dbname']})");
        $db = new DatabaseBackupCreator($this->dbConfig['host'], $this->dbConfig['username'], $this->dbConfig['password'], $this->dbConfig['dbname']);

        $result = $db->backupMySql();

        if (!$result) {
            $this->logger->Warning('Database dump could not be created.');
        } else {
            $this->logger->Info("Database dump to '{$result}'");
        }

        FileCrypt::encryptFile($result, $this->encryptionKey);

        $result = $this->copyToTempDir($result, basename($result));

        return new StepResult($result, false);
    }

    private function copyToTempDir(string $file, string $newName): string
    {
        $newFile = $this->backupFolder . $newName;
        rename($file, $newFile);
        $this->logger->Info("Move file from '{$file}' to '{$newFile}'");

        return $newFile;
    }
}
