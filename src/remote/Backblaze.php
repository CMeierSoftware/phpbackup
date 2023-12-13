<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use obregonco\B2\Client;
use obregonco\B2\File;

class Backblaze extends AbstractRemoteHandler
{
    private readonly string $accountId;
    private readonly string $keyId;
    private readonly string $applicationKey;
    private readonly string $bucketName;
    private string $bucketId;

    public function __construct(string $keyId, string $applicationKey, string $bucketName)
    {
        $this->accountId = substr($keyId, 3, 12);
        $this->keyId = $keyId;
        $this->applicationKey = $applicationKey;
        $this->bucketName = $bucketName;
    }

    /**
     * @inheritDoc
     */
    public function connect(): bool
    {
        $this->connection = new Client($this->accountId, ['keyId' => $this->keyId, 'applicationKey' => $this->applicationKey]);
        $this->connection->version = 2;
        // Lower limit for using large files upload support. Default: 3GB
        $this->connection->largeFileLimit = 3000000000;

        $this->bucketId = $this->connection->getBucketIdFromName($this->bucketName);
        if ($this->bucketId === null) {
            $this->connection = null;
        }

        return $this->connection !== null;
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): bool
    {
        // For local storage, disconnection is not applicable
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        $options = [
            'FileName' => $remoteFilePath,
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
            'Body' => fopen($localFilePath, 'rb'),
        ];

        $file = $this->connection->upload($options);

        return !empty($file);
    }

    /**
     * @inheritDoc
     */
    protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        $options = [
            'FileName' => $remoteFilePath,
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
            'SaveAs' => $localFilePath,
        ];

        $this->connection->download($options);

        return file_exists($localFilePath);
    }

    /**
     * @inheritDoc
     */
    protected function _fileDelete(string $remoteFilePath): bool
    {
        $options = [
            'FileName' => $remoteFilePath,
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
        ];

        return $this->connection->deleteFile($options);
    }

    /**
     * @inheritDoc
     */
    public function _createDirectory(string $remoteDirectoryPath): bool
    {
        $emptyFilePath = rtrim(ltrim($remoteDirectoryPath, '/'), '/') . '/.bzEmpty';
        $localEmptyFilePath = TEMP_DIR . 'temp.txt';
        touch($localEmptyFilePath);
        try {
            $result = $this->_fileUpload($localEmptyFilePath, $emptyFilePath);
        } finally {
            unlink($localEmptyFilePath);
        }

        return $result;
    }


    /**
     * @inheritDoc
     */
    protected function _fileExists(string $remoteFilePath): bool
    {
        $options = [
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
        ];

        if ($this->isFilePath($remoteFilePath)) {
            $options['FileName'] = $remoteFilePath;
        }

        $fileList = $this->connection->listFiles($options);

        foreach ($fileList as $file) {
            if (strpos($file->getFileName(), $remoteFilePath) === 0) {
                return true;
            }
        }

        return false;
    }

    private function isFilePath($path): bool
    {
        $lastDotPosition = strrpos($path, '.');

        // Check if a dot was found and it is not the last character in the path
        return $lastDotPosition !== false && $lastDotPosition < strlen($path) - 1;
    }
}
