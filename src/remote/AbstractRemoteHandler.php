<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use CMS\PhpBackup\Exceptions\FileNotFoundException;

/**
 * Class AbstractRemoteHandler - Abstract class for handling remote operations.
 */
abstract class AbstractRemoteHandler
{
    /** @var bool|null The connection status. */
    private $connection = null;

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
     * @return bool True if the connection is successful, false otherwise.
     */
    abstract public function connect(): bool;

    /**
     * Disconnects from the remote server.
     *
     * @return bool True if the disconnection is successful, false otherwise.
     */
    abstract public function disconnect(): bool;

    /**
     * Uploads a file to the remote server.
     *
     * @param string $localFilePath - The local file path.
     * @param string $remoteFilePath - The remote destination path.
     *
     * @return bool - True if the upload is successful, false otherwise.
     */
    public function fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        if (!file_exists($localFilePath)) {
            throw new FileNotFoundException("The file '{$localFilePath}' was not found in local storage.");
        } elseif ($this->fileExists($remoteFilePath)) {
            throw new FileAlreadyExistsException("The file '{$remoteFilePath}' already exists on remote storage.");
        } elseif (!$this->createDirectory($remoteFilePath)) {
            throw new FileNotFoundException("Can not create directory for '{$remoteFilePath}' in remote storage.");
        }

        return $this->_fileUpload($localFilePath, $remoteFilePath);
    }

    /**
     * @see AbstractRemoteHandler::fileUpload()
     */
    abstract protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool;

    /**
     * Downloads a file from the remote server.
     *
     * @param string $localFilePath - The local destination path.
     * @param string $remoteFilePath - The remote file path.
     *
     * @return bool - True if the download is successful, false otherwise.
     */
    public function fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        if (!$this->fileExists($remoteFilePath)) {
            throw new FileNotFoundException("The file '{$remoteFilePath}' was not found in remote storage.");
        } elseif (file_exists($localFilePath)) {
            throw new FileAlreadyExistsException("The file '{$localFilePath}' already exists on local storage.");
        }

        return $this->_fileDownload($localFilePath, $remoteFilePath);
    }

    /**
     * @see AbstractRemoteHandler::fileDownload()
     */
    abstract protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool;

    /**
     * Deletes a file from the remote server.
     *
     * @param string $remoteFilePath - The remote file path to delete.
     *
     * @return bool - True if the deletion is successful, false otherwise.
     */
    public function fileDelete(string $remoteFilePath): bool
    {
        if (!$this->fileExists($remoteFilePath)) {
            throw new FileNotFoundException("The file '{$remoteFilePath}' was not found in remote storage.");
        }

        return $this->_fileDelete($remoteFilePath);
    }

    /**
     * @see AbstractRemoteHandler::fileDelete()
     */
    abstract protected function _fileDelete(string $remoteFilePath): bool;

    /**
     * Creates a directory path recursive if not already exists
     */
    abstract public function createDirectory(string $remoteFilePath): bool;

    /**
     * Checks if the remote handler is currently connected to the remote server.
     *
     * @return bool True if connected, false if disconnected or connection status is unknown.
     */
    public function isConnected(): bool
    {
        return $this->connection ?? false;
    }
}
