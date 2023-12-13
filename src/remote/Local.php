<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

use CMS\PhpBackup\Helper\FileHelper;

/**
 * Class Local - Handles local storage operations.
 */
class Local extends AbstractRemoteHandler
{
    private readonly string $remoteRootPath;

    public function __construct(string $remoteRootPath)
    {
        FileHelper::makeDir($remoteRootPath);
        $this->remoteRootPath = realpath($remoteRootPath);
    }

    public function connect(): bool
    {
        $this->connection = true;

        return true;
    }

    public function disconnect(): bool
    {
        $this->connection = false;

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

    protected function _createDirectory(string $remoteFilePath): bool
    {
        $absolutePath = $this->buildAbsPath($remoteFilePath);

        // Ensure the directory structure exists
        if (isset(pathinfo($absolutePath)['extension'])) {
            $absolutePath = pathinfo($absolutePath, PATHINFO_DIRNAME);
        }

        if (!FileHelper::doesDirExists($absolutePath)) {
            // Create the directory and its parents if they don't exist
            FileHelper::makeDir($absolutePath);
        }

        return true;
    }

    protected function _fileExists(string $remoteFilePath): bool
    {
        return file_exists($this->buildAbsPath($remoteFilePath));
    }

    private function buildAbsPath(string $remoteFilePath): string
    {
        return $this->remoteRootPath . DIRECTORY_SEPARATOR . ltrim($remoteFilePath, DIRECTORY_SEPARATOR);
    }
}
