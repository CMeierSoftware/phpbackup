<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

if (!defined('ABS_PATH')) {
    return;
}

final class SendRemoteStep extends AbstractStep
{
    private readonly AbstractRemoteHandler $remote;
    private readonly string $backupDir;
    private readonly string $backupDirName;
    private array $archives;
    private array $uploadedFiles = [];

    /**
     * SendRemoteStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     * @param string $dirToSend local directory containing backup files
     * @param array $archives array of backup archives to be sent
     * @param int $delay delay in seconds before executing the remote step (optional, default is 0)
     */
    public function __construct(AbstractRemoteHandler $remoteHandler, string $dirToSend, array &$archives, int $delay = 0)
    {
        parent::__construct($delay);

        $this->remote = $remoteHandler;
        $this->backupDir = rtrim($dirToSend, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->backupDirName = basename($dirToSend);
        $this->archives = &$archives;
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
        $this->createBaseDir();
        $this->sendArchives();
        $this->uploadFileMapping();

        return new StepResult('', count($this->archives) !== count($this->uploadedFiles));
    }

    /**
     * Retrieves the list of files already uploaded to the remote server.
     */
    private function getUploadedFiles(): void
    {
        try {
            $this->uploadedFiles = $this->remote->dirList($this->backupDirName);
        } catch (FileNotFoundException $th) {
            $this->uploadedFiles = [];
        }
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
        }
    }

    /**
     * Uploads the file mapping to the remote server.
     *
     * @return bool true if the file mapping upload is successful, false otherwise
     */
    private function uploadFileMapping(): bool
    {
        $fileMapping = $this->backupDir . 'file_mapping.json';
        file_put_contents($fileMapping, json_encode($this->archives, JSON_PRETTY_PRINT));

        $remotePath = $this->backupDirName . '/' . basename($fileMapping);
        if ($this->remote->fileExists($remotePath)) {
            $this->remote->fileDelete($remotePath);
        }

        return $this->remote->fileUpload($fileMapping, $remotePath);
    }
}
