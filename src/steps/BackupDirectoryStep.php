<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Backup\FileBackupCreator;
use CMS\PhpBackup\Core\FileCrypt;
use CMS\PhpBackup\Exceptions\MaximalAttemptsReachedException;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

if (!defined('ABS_PATH')) {
    return;
}

final class BackupDirectoryStep extends AbstractStep
{
    private array $bundles;
    private array $archives;
    private readonly string $srcDir;
    private readonly array $excludeDirs;
    private readonly string $encryptionKey;
    private readonly string $backupDirectory;

    /**
     * DirectoryBackupStep constructor.
     */
    public function __construct(?AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct(null);

        $this->srcDir = $this->config->getBackupDirectory()['src'];
        $this->excludeDirs = $this->config->getBackupDirectory()['exclude'];
        $this->encryptionKey = $this->config->getBackupSettings()['encryptionKey'];
    }

    protected function getRequiredDataKeys(): array
    {
        return ['backupDirectory', 'bundles'];
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        if (!isset($this->data['archives'])) {
            $this->data['archives'] = [];
        }

        $cntBundles = count($this->data['bundles']);
        $cntArchives = count($this->data['archives']);

        for ($idx = count($this->data['archives']); $idx < $cntBundles; ++$idx) {
            if ($this->isTimeoutClose()) {
                break;
            }

            if ($this->incrementAttemptsCount() > self::MAX_ATTEMPTS) {
                throw new MaximalAttemptsReachedException("Maximal attempts to backup index '{$idx}' reached (max. " . (string) self::MAX_ATTEMPTS . 'attempts)');
            }

            $this->backupBundle($idx);
            $this->resetAttemptsCount();
            $cntArchives = count($this->data['archives']);

            $this->logger->info("Archived and encrypted bundle {$cntArchives} of {$cntBundles} bundles.");
        }

        return new StepResult('', $cntArchives < $cntBundles);
    }

    protected function sanitizeData(): void {}

    private function backupBundle(int $bundleIndex): void
    {
        $f = new FileBackupCreator($this->excludeDirs);

        $backupFileName = $f->backupOnly($this->srcDir, $this->data['bundles'][$bundleIndex]);

        $this->logger->debug("Archive files to '{$backupFileName}'");

        if (!empty($this->encryptionKey)) {
            FileCrypt::encryptFile($backupFileName, $this->encryptionKey);
        }

        $backupFileName = $this->moveToBackupDirectory($backupFileName, "archive_part_{$bundleIndex}.zip");
        $this->data['archives'][basename($backupFileName)] = $this->data['bundles'][$bundleIndex];
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
        $backupDirectory = rtrim($this->data['backupDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $newFile = $backupDirectory . $newName;
        FileHelper::makeDir($backupDirectory);
        FileHelper::moveFile($file, $newFile);

        return $newFile;
    }
}
