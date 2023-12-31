<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Backup\FileBackupCreator;
use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileCrypt;
use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

final class DirectoryBackupStep extends AbstractStep
{
    private array $bundles;
    private array $archives;
    private readonly string $srcDir;
    private readonly string $encryptionKey;
    private readonly string $backupFolder;

    /**
     * DirectoryBackupStep constructor.
     *
     * @param AppConfig $config configuration for this step
     * @param int $delay delay in seconds before executing the remote step (optional, default is 0)
     */
    public function __construct(AppConfig $config, int $delay = 0)
    {
        parent::__construct($config, $delay);

        $this->srcDir = $this->config->getBackupDirectory()['src'];
        $this->encryptionKey = $this->config->getBackupSettings()['encryptionKey'];
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
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

        $idx = count($archives);
        $f = new FileBackupCreator();

        $backupFileName = $f->backupOnly($this->srcDir, $bundles[$idx]);
        $this->logger->Info("Archive files to '{$backupFileName}'");

        FileCrypt::encryptFile($backupFileName, $this->encryptionKey);

        $backupFileName = $this->moveToBackupDirectory($backupFileName, "archive_part_{$idx}.zip");

        $archives[basename($backupFileName)] = $bundles[$idx];

        $cntBundles = count($bundles);
        $cntArchives = count($archives);

        $this->logger->Info("Archived {$cntArchives} of {$cntBundles} bundles.");

        return new StepResult($backupFileName, $cntArchives < $cntBundles);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return ['backupFolder', 'bundles'];
    }

    /**
     * Moves the file to the backup directory and logs the action.
     *
     * @param string $file original file path
     * @param string $newName new name for the file
     *
     * @return string the path to the moved file
     */
    private function moveToBackupDirectory(string $file, string $newName): string
    {
        $backupDirectory = rtrim($this->stepData['backupFolder'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $newFile = $backupDirectory . $newName;
        FileHelper::makeDir($backupDirectory);
        FileHelper::moveFile($file, $newFile);

        return $newFile;
    }
}
