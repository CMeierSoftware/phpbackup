<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Backup\DatabaseBackupCreator;
use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileCrypt;
use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

final class DatabaseBackupStep extends AbstractStep
{
    private readonly array $dbConfig;
    private readonly string $encryptionKey;

    /**
     * DatabaseBackupStep constructor.
     *
     * @param AppConfig $config configuration for this step
     */
    public function __construct(AppConfig $config)
    {
        parent::__construct($config);

        $this->encryptionKey = $this->config->getBackupSettings()['encryptionKey'];
        $this->dbConfig = $this->config->getBackupDatabase();
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

        $bundles = &$this->stepData['bundles'];

        if (!isset($this->stepData['archives'])) {
            $this->stepData['archives'] = [];
        }
        $archives = &$this->stepData['archives'];

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

    protected function getRequiredStepDataKeys(): array
    {
        return ['backupDirectory', 'bundles'];
    }

    /**
     * Moves the file to the backup Directory and logs the action.
     *
     * @param string $file original file path
     *
     * @return string the path to the moved file
     */
    private function moveToBackupDirectory(string $file): string
    {
        $backupDirectory = rtrim($this->stepData['backupDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $newFile = $backupDirectory . basename($file);
        FileHelper::makeDir($backupDirectory);
        FileHelper::moveFile($file, $newFile);
        FileHelper::changePermission($newFile, 0o0400);

        return $newFile;
    }
}
