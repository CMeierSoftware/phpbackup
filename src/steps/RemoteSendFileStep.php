<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

if (!defined('ABS_PATH')) {
    return;
}

final class RemoteSendFileStep extends AbstractStep
{
    private const FILE_MAPPING_NAME = 'file_mapping.json';
    private readonly AbstractRemoteHandler $remote;
    private readonly string $backupDir;
    private readonly string $backupDirName;
    private array $archives;
    private array $uploadedFiles = [];

    /**
     * SendRemoteStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     * @param int $delay delay in seconds before executing the remote step (optional, default is 0)
     */
    public function __construct(AbstractRemoteHandler $remoteHandler, AppConfig $config, int $delay = 0)
    {
        parent::__construct($config, $delay);

        $this->remote = $remoteHandler;
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        $this->backupDir = $this->stepData['backupFolder'];
        $this->backupDirName = basename($this->backupDir);
        $this->archives = &$this->stepData['archives'];

        $this->remote->connect();
        $this->getUploadedFiles();
        $this->createBaseDir();
        $this->sendArchives();
        $this->updateFileMapping();

        return new StepResult('', count($this->archives) !== count($this->uploadedFiles));
    }

    protected function getRequiredStepDataKeys(): array
    {
        return ['backupFolder', 'archives'];
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

        $filesInFileMapping = $this->downloadFileMapping();
        $diff = array_diff($this->uploadedFiles, $filesInFileMapping, [self::FILE_MAPPING_NAME]);

        foreach ($diff as $remoteFile) {
            $this->remote->fileDelete($this->backupDirName . '/' . $remoteFile);
        }

        $this->uploadedFiles = $this->remote->dirList($this->backupDirName, true);
    }

    /**
     * Creates the base directory on the remote server if it does not exist.
     */
    private function createBaseDir(): void
    {
        if (!$this->remote->fileExists($this->backupDirName)) {
            $this->remote->dirCreate($this->backupDirName);
        }
    }

    /**
     * Sends the backup archives to the remote server.
     */
    private function sendArchives(): void
    {
        $notUploadedFiles = array_diff_key($this->archives, array_flip($this->uploadedFiles));
        foreach ($notUploadedFiles as $archiveFileName => $content) {
            $localPath = $this->backupDir . $archiveFileName;
            $remotePath = $this->backupDirName . '/' . basename($archiveFileName);
            $this->remote->fileUpload($localPath, $remotePath);
            $this->uploadedFiles[] = $archiveFileName;
            $this->updateFileMapping();
        }
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
            unlink($fileMapping);
        }

        try {
            $this->remote->fileDownload($fileMapping, $remotePath);
        } catch (FileNotFoundException) {
            return [];
        }

        return json_decode(file_get_contents($fileMapping));
    }
}
