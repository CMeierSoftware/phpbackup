<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

use pCloud\Sdk\App;

class Pcloud extends AbstractRemoteHandler
{
    private readonly string $access_token;

    public function __construct(string $access_token)
    {
        $this->access_token = $access_token;
    }

    public function connect(): bool
    {
        $this->connection = new App();
        $this->connection->setAccessToken($this->access_token);
        $this->connection->setLocationId(1);
        $this->connection->setCurlExecutionTimeout(10);

        return null !== $this->connection;
    }

    public function disconnect(): bool
    {
        $this->connection = null;

        return null === $this->connection;
    }

    public function _dirCreate(string $remoteFilePath): bool
    {
        $absolutePath = $this->buildAbsPath($remoteFilePath);

        // Ensure the directory structure exists
        if ('' !== pathinfo($absolutePath, FILEINFO_EXTENSION)) {
            $absolutePath = pathinfo($absolutePath, PATHINFO_DIRNAME);
        }

        if (!is_dir($absolutePath)) {
            // Create the directory and its parents if they don't exist
            return mkdir($absolutePath, 0o644, true);
        }

        return true;
    }

    protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        return copy($localFilePath, $this->buildAbsPath($remoteFilePath));
    }

    protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        return copy($this->buildAbsPath($remoteFilePath), $localFilePath);
    }

    protected function _fileDelete(string $remoteFilePath): bool
    {
        return unlink($this->buildAbsPath($remoteFilePath));
    }

    protected function _fileExists(string $remoteFilePath): bool
    {
        return file_exists($this->buildAbsPath($remoteFilePath));
    }

    protected function _dirDelete(string $remotePath): bool {}

    protected function _dirList(string $remotePath): array {}
}
