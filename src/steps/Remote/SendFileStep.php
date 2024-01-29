<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\MaximalAttemptsReachedException;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Step\StepResult;

if (!defined('ABS_PATH')) {
    return;
}

final class SendFileStep extends AbstractRemoteStep
{
    private const FILE_MAPPING_NAME = 'file_mapping.json';
    private readonly string $backupDir;
    private readonly string $backupDirName;
    private array $archives;
    private array $uploadedFiles = [];

    /**
     * SendRemoteStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     */
    public function __construct(AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct($remoteHandler);

        $this->backupDir = $this->stepData['backupDirectory'];
        $this->backupDirName = basename($this->backupDir);
        $this->archives = &$this->stepData['archives'];
    }

    protected function getRequiredDataKeys(): array
    {
        return ['backupDirectory', 'archives'];
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        $this->remote->connect();
        $this->getUploadedFiles();
        $this->createBaseDirectory();

        $filesToUpload = array_diff(array_keys($this->archives), $this->uploadedFiles);
        foreach ($filesToUpload as $archiveFileName) {
            if ($this->isTimeoutClose()) {
                break;
            }

            if ($this->incrementAttemptsCount() > self::MAX_ATTEMPTS) {
                throw new MaximalAttemptsReachedException("Maximal attempts to upload '{$archiveFileName}' reached (max. " . (string) self::MAX_ATTEMPTS . 'attempts)');
            }

            $this->sendArchives($archiveFileName);
            $this->resetAttemptsCount();
        }

        return new StepResult('', count($this->archives) !== count($this->uploadedFiles));
    }

    /**
     * Retrieves the list of files already uploaded to the remote server.
     */
    private function getUploadedFiles(): void
    {
        try {
            $this->uploadedFiles = $this->remote->dirList($this->backupDirName, true);
        } catch (FileNotFoundException $th) {
            $this->uploadedFiles = [];

            return;
        }

        $filesInFileMapping = array_keys($this->downloadFileMapping());
        $diff = array_diff($this->uploadedFiles, $filesInFileMapping, [self::FILE_MAPPING_NAME]);

        foreach ($diff as $remoteFile) {
            $this->remote->fileDelete($this->backupDirName . '/' . $remoteFile);
        }

        $this->uploadedFiles = array_diff($this->remote->dirList($this->backupDirName, true), [self::FILE_MAPPING_NAME]);
    }

    /**
     * Creates the base directory on the remote server if it does not exist.
     */
    private function createBaseDirectory(): void
    {
        if (!$this->remote->fileExists($this->backupDirName)) {
            $this->remote->dirCreate($this->backupDirName);
        }
    }

    /**
     * Sends the backup archives to the remote server.
     */
    private function sendArchives(string $archiveFileName): void
    {
        $localPath = $this->backupDir . $archiveFileName;
        $remotePath = $this->backupDirName . '/' . basename($archiveFileName);
        $this->remote->fileUpload($localPath, $remotePath);
        $this->uploadedFiles[] = $archiveFileName;
        $this->updateFileMapping();
    }

    /**
     * Uploads the file mapping to the remote server.
     *
     * @return bool true if the file mapping upload is successful, false otherwise
     */
    private function updateFileMapping(): bool
    {
        $fileMapping = $this->backupDir . self::FILE_MAPPING_NAME;
        file_put_contents($fileMapping, json_encode($this->archives, JSON_PRETTY_PRINT));

        $remotePath = $this->backupDirName . '/' . basename($fileMapping);
        if ($this->remote->fileExists($remotePath)) {
            $this->remote->fileDelete($remotePath);
        }

        return $this->remote->fileUpload($fileMapping, $remotePath);
    }

    private function downloadFileMapping(): array
    {
        $fileMapping = $this->backupDir . self::FILE_MAPPING_NAME;
        $remotePath = $this->backupDirName . '/' . basename($fileMapping);

        if (file_exists($fileMapping)) {
            FileHelper::deleteFile($fileMapping);
        }

        try {
            $this->remote->fileDownload($fileMapping, $remotePath);
        } catch (FileNotFoundException) {
            return [];
        }

        return json_decode(file_get_contents($fileMapping), true);
    }
}
