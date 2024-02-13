<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Backup\DatabaseBackupCreator;
use CMS\PhpBackup\Core\FileCrypt;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

if (!defined('ABS_PATH')) {
    return;
}

final class BackupDatabaseStep extends AbstractStep
{
    private readonly array $dbConfig;
    private readonly string $encryptionKey;

    /**
     * DatabaseBackupStep constructor.
     */
    public function __construct(?AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct($remoteHandler);

        $this->encryptionKey = $this->config->getBackupSettings()['encryptionKey'];
        $this->dbConfig = $this->config->getBackupDatabase();
    }

    protected function getRequiredDataKeys(): array
    {
        return ['backupDirectory', 'bundles'];
    }

    /**
     * Executes the database backup step.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        if (empty($this->dbConfig)) {
            $this->logger->info('No database defined. Skip step.');

            return new StepResult('No database defined. Skip step.', false);
        }

        $bundles = &$this->data['bundles'];

        if (!isset($this->data['archives'])) {
            $this->data['archives'] = [];
        }
        $archives = &$this->data['archives'];

        $this->logger->info("Starting database dump of ({$this->dbConfig['host']}, {$this->dbConfig['dbname']})");

        $db = new DatabaseBackupCreator(
            $this->dbConfig['host'],
            $this->dbConfig['username'],
            $this->dbConfig['password'],
            $this->dbConfig['dbname']
        );

        $backupFileName = $db->backupMySql();

        if (!$backupFileName) {
            $this->logger->warning('Database dump could not be created.');

            return new StepResult('Database dump could not be created.', false);
        }

        if (!empty($this->encryptionKey)) {
            FileCrypt::encryptFile($backupFileName, $this->encryptionKey);
        }

        $backupFileName = $this->moveToBackupDirectory($backupFileName);
        $archives[basename($backupFileName)] = 'Database backup.';

        $this->logger->info('Database dump created and encrypted.');

        return new StepResult($backupFileName, false);
    }

    protected function sanitizeData(): void {}

    /**
     * Moves the file to the backup Directory and logs the action.
     *
     * @param string $file original file path
     *
     * @return string the path to the moved file
     */
    private function moveToBackupDirectory(string $file): string
    {
        $backupDirectory = rtrim($this->data['backupDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $newFile = $backupDirectory . basename($file);
        FileHelper::makeDir($backupDirectory);
        FileHelper::moveFile($file, $newFile);

        return $newFile;
    }
}
