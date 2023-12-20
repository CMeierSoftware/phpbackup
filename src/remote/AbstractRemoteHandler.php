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
     * If the target directory does not exists, it will be created recursively.
     *
     * @param string $localPath - The local file path
     * @param string $remotePath - The remote destination path
     *
     * @return bool - True if the upload is successful, false otherwise
     */
    public function fileUpload(string $localPath, string $remotePath): bool
    {
        $this->sanitizeFileCheck($remotePath);

        if (!is_file($localPath)) {
            throw new FileNotFoundException("The file '{$localPath}' was not found in local storage.");
        }
        if ($this->fileExists($remotePath)) {
            throw new FileAlreadyExistsException("The file '{$remotePath}' already exists on remote storage.");
        }
        if (!$this->dirCreate($remotePath)) {
            throw new FileNotFoundException("Can not create directory for '{$remotePath}' in remote storage.");
        }

        FileLogger::getInstance()->info("Upload local file '{$localPath}' to remote storage '{$remotePath}'");
        $this->fileExistsCache[$remotePath] = $this->_fileUpload($localPath, $remotePath);

        return $this->fileExistsCache[$remotePath];
    }

    /**
     * Downloads a file from the remote server.
     *
     * @param string $localPath - The local destination path
     * @param string $remotePath - The remote file path
     *
     * @return bool - True if the download is successful, false otherwise
     */
    public function fileDownload(string $localPath, string $remotePath): bool
    {
        $this->sanitizeFileCheck($remotePath);

        if (file_exists($localPath)) {
            throw new FileAlreadyExistsException("The file '{$localPath}' already exists on local storage.");
        }
        if (!$this->fileExists($remotePath)) {
            throw new FileNotFoundException("The file '{$remotePath}' was not found in remote storage.");
        }

        FileHelper::makeDir(dirname($localPath));

        FileLogger::getInstance()->info("Download remote file '{$remotePath}' to local storage '{$localPath}'");

        return $this->_fileDownload($localPath, $remotePath);
    }

    /**
     * Deletes a file from the remote server.
     *
     * @param string $remotePath - The remote file path to delete
     *
     * @return bool - True if the deletion is successful, false otherwise
     */
    public function fileDelete(string $remotePath): bool
    {
        $this->sanitizeFileCheck($remotePath);

        if (!$this->fileExists($remotePath)) {
            throw new FileNotFoundException("The file '{$remotePath}' was not found in remote storage.");
        }

        FileLogger::getInstance()->info("Delete remote file '{$remotePath}'");

        $result = $this->_fileDelete($remotePath);
        if ($result) {
            $this->fileExistsCache[$remotePath] = false;
        }

        return $result;
    }

    /**
     * Checks if a file or directory exists on the remote server.
     *
     * @param string $remotePath - The remote file path to check
     *
     * @return bool - True if exists, false otherwise
     */
    public function fileExists(string $remotePath): bool
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }

        if (!isset($this->fileExistsCache[$remotePath])) {
            $result = $this->_fileExists($remotePath);
            $this->fileExistsCache[$remotePath] = $result;
            if ($this->fileExistsCache[$remotePath]) {
                FileLogger::getInstance()->info("Remote file '{$remotePath}' does exist (request).");
            } else {
                FileLogger::getInstance()->info("Remote file '{$remotePath}' doesn't exist (request).");
            }
        } elseif ($this->fileExistsCache[$remotePath]) {
            FileLogger::getInstance()->info("Remote file '{$remotePath}' does exist (cache).");
        } else {
            FileLogger::getInstance()->info("Remote file '{$remotePath}' doesn't exist (cache).");
        }

        return $this->fileExistsCache[$remotePath];
    }

   

    /**
     * Creates a directory path recursive if not already exists.
     */
    public function dirCreate(string $remotePath): bool
    {
        // if its a file, get the directory
        if ($this->isFilePath($remotePath)) {
            $remotePath = dirname($remotePath);
        }

        $this->sanitizeDirCheck($remotePath);

        if (!$this->fileExists($remotePath)) {
            FileLogger::getInstance()->info("Create remote directory '{$remotePath}'.");

            $this->fileExistsCache[$remotePath] = $this->_dirCreate($remotePath);
        }

        return $this->fileExistsCache[$remotePath];
    }

    /**
     * Deletes a directory recursively with all its content.
     */
    public function dirDelete(string $remotePath): bool
    {
        $this->sanitizeDirCheck($remotePath);

        $success = false;

        if ($this->fileExists($remotePath)) {
            FileLogger::getInstance()->info("Delete remote directory recursively '{$remotePath}'.");

            $success = $this->_dirDelete($remotePath);
            $this->fileExistsCache[$remotePath] = !$success;
        }

        if (!$this->fileExistsCache[$remotePath]) {
            foreach (array_keys($this->fileExistsCache) as $file) {
                if (str_starts_with($file, $remotePath)) {
                    $this->fileExistsCache[$file] = false;
                }
            }
        }

        return $success;
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
    abstract protected function _fileUpload(string $localPath, string $remotePath): bool;

    /**
     * @see AbstractRemoteHandler::fileDownload()
     */
    abstract protected function _fileDownload(string $localPath, string $remotePath): bool;

    /**
     * @see AbstractRemoteHandler::fileDelete()
     */
    abstract protected function _fileDelete(string $remotePath): bool;

    /**
     * @see AbstractRemoteHandler::fileExists()
     */
    abstract protected function _fileExists(string $remotePath): bool;

    /**
     * @see AbstractRemoteHandler::dirCreate()
     */
    abstract protected function _dirCreate(string $remotePath): bool;

    /**
     * @see AbstractRemoteHandler::dirDelete()
     */
    abstract protected function _dirDelete(string $remotePath): bool;

    private function sanitizeFileCheck(string $remotePath, bool $checkFilePath = true): void
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }
        if ($checkFilePath && !$this->isFilePath($remotePath)) {
            throw new \InvalidArgumentException('The provided path belongs to a file, not a directory.');
        }
    }

    private function sanitizeDirCheck(string $remotePath, bool $allowFilePaths = false): void
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }
        if (!$allowFilePaths && $this->isFilePath($remotePath)) {
            throw new \InvalidArgumentException('The provided path belongs to a file, not a directory.');
        }
    }

    private function isFilePath(string $remotePath): bool
    {
        // Split the path and file name
        $pathInfo = pathinfo($remotePath);

        // Check if it's a file path, if true, remove the file name
        return !empty($pathInfo['extension']);
    }
}
