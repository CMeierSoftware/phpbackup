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
        parent::__construct();

        $this->remoteRootPath = $remoteRootPath;
        $this->logger->info("Local base directory '{$this->remoteRootPath}'.");
        FileHelper::makeDir($this->remoteRootPath);
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
        FileHelper::deleteFile($this->buildAbsPath($remoteFilePath));

        return true;
    }

    protected function _dirCreate(string $remoteFilePath): bool
    {
        $absolutePath = $this->buildAbsPath($remoteFilePath);

        // Ensure the directory structure exists
        if (isset(pathinfo($absolutePath)['extension'])) {
            $absolutePath = pathinfo($absolutePath, PATHINFO_DIRNAME);
        }

        if (!FileHelper::directoryExists($absolutePath)) {
            FileHelper::makeDir($absolutePath);
        }

        return true;
    }

    protected function _fileExists(string $remoteFilePath): bool
    {
        return file_exists($this->buildAbsPath($remoteFilePath));
    }

    protected function _dirDelete(string $remoteFilePath): bool
    {
        FileHelper::deleteDirectory($this->buildAbsPath($remoteFilePath));

        return true;
    }

    protected function _dirList(string $remoteFilePath): array
    {
        return scandir($this->buildAbsPath($remoteFilePath));
    }

    private function buildAbsPath(string $remoteFilePath): string
    {
        return $this->remoteRootPath . DIRECTORY_SEPARATOR . ltrim($remoteFilePath, DIRECTORY_SEPARATOR);
    }
}
