<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Backup\FileBackupCreator;
use CMS\PhpBackup\Core\FileCrypt;
use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

final class CreateDirectoryBackupStep extends AbstractStep
{
    private readonly string $srcDir;
    private array $bundles;
    private array $archives;
    private readonly string $encryptionKey;
    private readonly string $backupFolder;

    /**
     * SendRemoteStep constructor.
     *
     * @param int $delay delay in seconds before executing the remote step (optional, default is 0)
     */
    public function __construct(string $srcDir, string $backupFolder, string $encryptionKey, array &$bundles, array &$archives, int $delay = 0)
    {
        parent::__construct($delay);

        $this->srcDir = $srcDir;
        $this->encryptionKey = $encryptionKey;
        $this->bundles = &$bundles;
        $this->archives = &$archives;
        $this->backupFolder = rtrim($backupFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        $idx = count($this->archives);
        $f = new FileBackupCreator();

        $result = $f->backupOnly($this->srcDir, $this->bundles[$idx]);
        $this->logger->Info("Archive files to '{$result}'");

        FileCrypt::encryptFile($result, $this->encryptionKey);

        $result = $this->moveToBackupFolder($result, "archive_part_{$idx}.zip");

        $this->archives[basename($result)] = $this->bundles[$idx];

        $cntBundles = count($this->bundles);
        $cntArchives = count($this->archives);

        $this->logger->Info("Archived {$cntArchives} of {$cntBundles} bundles.");

        return new StepResult($result, $cntArchives < $cntBundles);
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
        $newFile = $this->backupFolder . $newName;
        FileHelper::moveFile($file, $newFile);

        return $newFile;
    }
}
