<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;

/**
 * Class Local - Handles local storage operations.
 */
class Local extends AbstractRemoteHandler
{
    private readonly string $remoteRootPath;

    public function __construct(string $remoteRootPath)
    {
        mkdir($remoteRootPath, 0644, true);
        $this->remoteRootPath = realpath($remoteRootPath);
    }

    /**
     * @inheritDoc
     */
    public function connect(): bool
    {
        $this->connection = true;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): bool
    {
        $this->connection = false;
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        return copy($localFilePath, $this->buildAbsPath($remoteFilePath));
    }

    /**
     * @inheritDoc
     */
    protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        return copy($this->buildAbsPath($remoteFilePath), $localFilePath);
    }

    /**
     * @inheritDoc
     */
    protected function _fileDelete(string $remoteFilePath): bool
    {
        return unlink($this->buildAbsPath($remoteFilePath));
    }

    /**
     * @inheritDoc
     */
    protected function _createDirectory(string $remoteFilePath): bool
    {
        $absolutePath = $this->buildAbsPath($remoteFilePath);

        // Ensure the directory structure exists
        if(isset(pathinfo($absolutePath)['extension'])) {
            $absolutePath = pathinfo($absolutePath, PATHINFO_DIRNAME);
        }

        if (!is_dir($absolutePath)) {
            // Create the directory and its parents if they don't exist
            return mkdir($absolutePath, 0644, true);
        }
        return true;
    }


    /**
     * @inheritDoc
     */
    protected function _fileExists(string $remoteFilePath): bool
    {
        return file_exists($this->buildAbsPath($remoteFilePath));
    }

    private function buildAbsPath(string $remoteFilePath): string
    {
        return $this->remoteRootPath . DIRECTORY_SEPARATOR . ltrim($remoteFilePath, DIRECTORY_SEPARATOR);
    }
}
