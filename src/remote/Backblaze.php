<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

use CMS\PhpBackup\Helper\FileHelper;
use obregonco\B2\Bucket;
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
        parent::__construct();
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

        $this->bucketId = $this->getBucketIdFromName($this->bucketName);
        if (null === $this->bucketId) {
            $this->connection = null;
        }

        return null !== $this->connection;
    }

    public function disconnect(): bool
    {
        $this->logger->debug('Disconnect.');
        $this->connection = null;

        return null === $this->connection;
    }

    public function _dirCreate(string $remoteDirectoryPath): bool
    {
        $remoteDirectoryPath = $this->sanitizePath($remoteDirectoryPath);
        $emptyFilePath = rtrim(ltrim($remoteDirectoryPath, '/'), '/') . '/.bzEmpty';
        $localEmptyFilePath = TEMP_DIR . 'temp.txt';
        touch($localEmptyFilePath);

        try {
            $result = $this->_fileUpload($localEmptyFilePath, $emptyFilePath);
        } finally {
            FileHelper::deleteFile($localEmptyFilePath);
        }

        return $result;
    }

    protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        $remoteFilePath = $this->sanitizePath($remoteFilePath);
        $options = [
            'FileName' => $remoteFilePath,
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
            'Body' => fopen($localFilePath, 'r'),
        ];
        
        try {
            $file = $this->connection->upload($options);
        } catch (\GuzzleHttp\Exception\ServerException $ex) {
            if ($ex->getCode() === '503') {
                $this->connection->authorizeAccount(true);
                $file = $this->connection->upload($options);
            } else {
                throw $ex;
            }
        }

        return !empty($file);
    }

    protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        $remoteFilePath = $this->sanitizePath($remoteFilePath);
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
        $remoteFilePath = $this->sanitizePath($remoteFilePath);

        $options = [
            'FileName' => $remoteFilePath,
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
        ];

        return $this->connection->deleteFile($options);
    }

    protected function _fileExists(string $remoteFilePath): bool
    {
        $remoteFilePath = $this->sanitizePath($remoteFilePath);
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

    protected function _dirDelete(string $remotePath): bool
    {
        $remotePath = $this->sanitizePath($remotePath);
        $result = true;
        $files = $this->dirList($remotePath . '/', true);
        foreach ($files as $fileId => $fileName) {
            if (str_starts_with($fileName, 'dir_')) {
                $result = $result && $this->dirDelete($remotePath . '/' . $fileName); // , $fileId, false);
            } else {
                $result = $result && $this->fileDelete($remotePath . '/' . $fileName);
            }
        }

        return $result;
    }

    protected function _dirList(string $remotePath): array
    {
        $remotePath = $this->sanitizePath($remotePath);

        $options = [
            'BucketName' => $this->bucketName,
            'BucketId' => $this->bucketId,
        ];

        $fileList = $this->connection->listFiles($options);
        $result = [];

        foreach ($fileList as $file) {
            if (!str_starts_with($file->getFileName(), $remotePath)) {
                continue;
            }

            $name = array_filter(explode('/', str_replace($remotePath, '', $file->getFileName())));
            $name = reset($name);

            if (!in_array($name, $result, true)) {
                $result[] = $name;
            }
        }

        return $result;
    }

    /**
     * Maps the provided bucket name to the appropriate bucket ID.
     *
     * @param mixed $name
     *
     * @return null|string
     */
    private function getBucketIdFromName($name)
    {
        $buckets = $this->connection->listBuckets(true); // we need to list the buckets with force

        $buckets = array_filter(
            $buckets,
            static fn (Bucket $bucket): bool => $name === $bucket->getName()
        );

        return !empty($buckets) ? reset($buckets)->getId() : null;
    }

    private function sanitizePath(string $path): string
    {
        return ltrim($path, '/');
    }
}
