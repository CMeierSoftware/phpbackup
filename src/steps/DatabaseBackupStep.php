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
    private readonly string $backupFolder;
    private readonly string $encryptionKey;

    /**
     * DatabaseBackupStep constructor.
     *
     * @param array $dbConfig database configuration parameters
     * @param string $backupFolder path to the backup folder
     * @param string $encryptionKey encryption key for securing the backup
     * @param int $delay delay in seconds before executing the backup step (optional, default is 0)
     */
    public function __construct(AppConfig $config, int $delay = 0)
    {
        parent::__construct($config, $delay);

        $this->srcDir = $this->config->getBackupDirectory()['src'];
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

        $result = $db->backupMySql('None');

        if (!$result) {
            $this->logger->warning('Database dump could not be created.');

            return new StepResult('', false);
        }

        $this->logger->info("Database dump created at '{$result}'");

        FileCrypt::encryptFile($result, $this->encryptionKey);

        $result = $this->moveToBackupFolder($result, basename($result));
        $archives[basename($result)] = 'Database backup.';

        return new StepResult($result, false);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return ['backupFolder', 'bundles'];
    }

    /**
     * Moves the file to the backup folder and logs the action.
     *
     * @param string $file original file path
     * @param string $newName new name for the file
     *
     * @return string the path to the moved file
     */
    private function moveToBackupFolder(string $file, string $newName): string
    {
        $backupFolder = rtrim($this->stepData['backupFolder'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $newFile = $backupFolder . $newName;
        FileHelper::makeDir($backupFolder);
        FileHelper::moveFile($file, $newFile);

        return $newFile;
    }
}
