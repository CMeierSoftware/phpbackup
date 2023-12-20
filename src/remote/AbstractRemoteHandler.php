<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\RemoteStorageNotConnectedException;
use CMS\PhpBackup\Helper\FileHelper;

/**
 * Class AbstractRemoteHandler - Abstract class for handling remote operations.
 */
abstract class AbstractRemoteHandler
{
    public array $fileExistsCache = [];
    protected mixed $connection = null;

    /**
     * Destructor to ensure disconnection upon object destruction.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Establishes a connection to the remote server.
     *
     * @return bool true if the connection is successful, false otherwise
     */
    abstract public function connect(): bool;

    /**
     * Disconnects from the remote server.
     *
     * @return bool true if the disconnection is successful, false otherwise
     */
    abstract public function disconnect(): bool;

    /**
     * Uploads a file to the remote server.
     *
     * @param string $localFilePath - The local file path
     * @param string $remoteFilePath - The remote destination path
     *
     * @return bool - True if the upload is successful, false otherwise
     */
    public function fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }
        if (!file_exists($localFilePath)) {
            throw new FileNotFoundException("The file '{$localFilePath}' was not found in local storage.");
        }
        if ($this->fileExists($remoteFilePath)) {
            throw new FileAlreadyExistsException("The file '{$remoteFilePath}' already exists on remote storage.");
        }
        if (!$this->dirCreate($remoteFilePath)) {
            throw new FileNotFoundException("Can not create directory for '{$remoteFilePath}' in remote storage.");
        }

        FileLogger::getInstance()->info("Upload local file '{$localFilePath}' to remote storage '{$remoteFilePath}'");
        $this->fileExistsCache[$remoteFilePath] = $this->_fileUpload($localFilePath, $remoteFilePath);

        return $this->fileExistsCache[$remoteFilePath];
    }

    /**
     * Downloads a file from the remote server.
     *
     * @param string $localFilePath - The local destination path
     * @param string $remoteFilePath - The remote file path
     *
     * @return bool - True if the download is successful, false otherwise
     */
    public function fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }
        if (file_exists($localFilePath)) {
            throw new FileAlreadyExistsException("The file '{$localFilePath}' already exists on local storage.");
        }
        if (!$this->fileExists($remoteFilePath)) {
            throw new FileNotFoundException("The file '{$remoteFilePath}' was not found in remote storage.");
        }

        FileHelper::makeDir(dirname($localFilePath));

        FileLogger::getInstance()->info("Download remote file '{$remoteFilePath}' to local storage '{$localFilePath}'");

        return $this->_fileDownload($localFilePath, $remoteFilePath);
    }

    /**
     * Deletes a file from the remote server.
     *
     * @param string $remoteFilePath - The remote file path to delete
     *
     * @return bool - True if the deletion is successful, false otherwise
     */
    public function fileDelete(string $remoteFilePath): bool
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }
        if (!$this->fileExists($remoteFilePath)) {
            throw new FileNotFoundException("The file '{$remoteFilePath}' was not found in remote storage.");
        }

        FileLogger::getInstance()->info("Delete remote file '{$remoteFilePath}'");

        $result = $this->_fileDelete($remoteFilePath);
        if ($result) {
            $this->fileExistsCache[$remoteFilePath] = false;
        }

        return $result;
    }

    /**
     * Checks if a file or directory exists on the remote server.
     *
     * @param string $remoteFilePath - The remote file path to check.
     *
     * @return bool - True if exists, false otherwise
     */
    public function fileExists(string $remoteFilePath): bool
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }

        if (!isset($this->fileExistsCache[$remoteFilePath])) {
            $result = $this->_fileExists($remoteFilePath);
            $this->fileExistsCache[$remoteFilePath] = $result;
            if ($this->fileExistsCache[$remoteFilePath]) {
                FileLogger::getInstance()->info("Remote file '{$remoteFilePath}' does exist (request).");
            } else {
                FileLogger::getInstance()->info("Remote file '{$remoteFilePath}' doesn't exist (request).");
            }
        } elseif ($this->fileExistsCache[$remoteFilePath]) {
            FileLogger::getInstance()->info("Remote file '{$remoteFilePath}' does exist (cache).");
        } else {
            FileLogger::getInstance()->info("Remote file '{$remoteFilePath}' doesn't exist (cache).");
        }

        return $this->fileExistsCache[$remoteFilePath];
    }

    /**
     * Creates a directory path recursive if not already exists.
     */
    public function dirCreate(string $remoteFilePath): bool
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }

        // Split the path and file name
        $pathInfo = pathinfo($remoteFilePath);
        $directoryPath = $pathInfo['dirname'];

        // Check if it's a file path, if true, remove the file name
        if (!empty($pathInfo['extension'])) {
            $remoteFilePath = $directoryPath;
        }

        if (!$this->fileExists($remoteFilePath)) {
            FileLogger::getInstance()->info("Create remote directory '{$remoteFilePath}'.");

            $this->fileExistsCache[$remoteFilePath] = $this->_dirCreate($remoteFilePath);
        }

        return $this->fileExistsCache[$remoteFilePath];
    }

    /**
     * Checks if the remote handler is currently connected to the remote server.
     *
     * @return bool true if connected, false if disconnected or connection status is unknown
     */
    public function isConnected(): bool
    {
        return !(null === $this->connection || false === $this->connection);
    }

    /**
     * Clears the cache.
     */
    public function clearCache(): void
    {
        $this->fileExistsCache = [];
    }

    /**
     * @see AbstractRemoteHandler::fileUpload()
     */
    abstract protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool;

    /**
     * @see AbstractRemoteHandler::fileDownload()
     */
    abstract protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool;

    /**
     * @see AbstractRemoteHandler::fileDelete()
     */
    abstract protected function _fileDelete(string $remoteFilePath): bool;

    /**
     * @see AbstractRemoteHandler::fileExists()
     */
    abstract protected function _fileExists(string $remoteFilePath): bool;

    /**
     * @see AbstractRemoteHandler::dirCreate()
     */
    abstract protected function _dirCreate(string $remoteFilePath): bool;
}
