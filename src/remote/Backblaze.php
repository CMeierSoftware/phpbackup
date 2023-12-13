<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

use obregonco\B2\Client;

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

    public function connect(): bool
    {
        $this->connection = new Client($this->accountId, ['keyId' => $this->keyId, 'applicationKey' => $this->applicationKey]);
        $this->connection->version = 2;
        // Lower limit for using large files upload support. Default: 3GB
        $this->connection->largeFileLimit = 3000000000;

        $this->bucketId = $this->connection->getBucketIdFromName($this->bucketName);
        if (null === $this->bucketId) {
            $this->connection = null;
        }

        return null !== $this->connection;
    }

    public function disconnect(): bool
    {
        // For local storage, disconnection is not applicable
        return true;
    }

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

    protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        $options = [
            'FileName' => $remoteFilePath,
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
            'Body' => fopen($localFilePath, 'r'),
        ];

        $file = $this->connection->upload($options);

        return !empty($file);
    }

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

    protected function _fileDelete(string $remoteFilePath): bool
    {
        $options = [
            'FileName' => $remoteFilePath,
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
        ];

        return $this->connection->deleteFile($options);
    }

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
            if (0 === strpos($file->getFileName(), $remoteFilePath)) {
                return true;
            }
        }

        return false;
    }

    private function isFilePath($path): bool
    {
        $lastDotPosition = strrpos($path, '.');

        // Check if a dot was found and it is not the last character in the path
        return false !== $lastDotPosition && $lastDotPosition < strlen($path) - 1;
    }
}
