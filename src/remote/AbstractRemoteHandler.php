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
     * If the target directory does not exist, it will be created recursively.
     *
     * @param string $localPath The local file path
     * @param string $remotePath The remote destination path
     *
     * @return bool True if the upload is successful, false otherwise
     *
     * @throws FileNotFoundException If the local file is not found
     * @throws FileAlreadyExistsException If the remote file already exists
     * @throws FileNotFoundException If the directory for the remote file cannot be created
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
     * @param string $localPath The local destination path
     * @param string $remotePath The remote file path
     *
     * @return bool True if the download is successful, false otherwise
     *
     * @throws FileAlreadyExistsException If the local file already exists
     * @throws FileNotFoundException If the remote file is not found
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
     * @param string $remotePath The remote file path to delete
     *
     * @return bool True if the deletion is successful, false otherwise
     *
     * @throws FileNotFoundException If the remote file is not found
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
     * @param string $remotePath The remote file path to check
     *
     * @return bool True if exists, false otherwise
     *
     * @throws RemoteStorageNotConnectedException If the remote storage is not connected
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
     * Lists a directory.
     *
     * @param string $remotePath The remote directory path
     *
     * @return array An array of file and directory names in the remote directory
     *
     * @throws RemoteStorageNotConnectedException If the remote storage is not connected
     */
    public function dirList(string $remotePath): array
    {
        $this->sanitizeDirCheck($remotePath);

        if (!$this->fileExists($remotePath)) {
            throw new FileNotFoundException("The directory '{$remotePath}' was not found in remote storage.");
        }

        FileLogger::getInstance()->info("Create remote directory '{$remotePath}'.");

        $result = $this->_dirList($remotePath);
        $result = array_values(array_diff($result, ['..', '.']));
        $resultCache = array_map(
            static fn ($path) => rtrim($remotePath, '\\/') . DIRECTORY_SEPARATOR . $path,
            $result
        );
        $this->fileExistsCache += array_fill_keys($resultCache, true);

        return $result;
    }

    /**
     * Creates a directory path recursively if not already exists.
     *
     * @param string $remotePath The remote directory path to create
     *
     * @return bool True if the directory creation is successful, false otherwise
     *
     * @throws RemoteStorageNotConnectedException If the remote storage is not connected
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
     *
     * @param string $remotePath The remote directory path to delete
     *
     * @return bool True if the deletion is successful, false otherwise
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

    public function deleteOld(string $remotePath, int $ageInDays, int $amount): int
    {
        $this->sanitizeDirCheck($remotePath);

        if ($ageInDays <= 0 && $amount <= 0) {
            return 0; // Nothing to do if both parameters are 0 or negative
        }

        $dirs = $this->dirList($remotePath);

        $validDirs = [];

        foreach ($dirs as $dir) {
            if (preg_match('/^backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})$/', $dir, $matches)) {
                $dateTime = \DateTime::createFromFormat('Y-m-d_H-i-s', $matches[1]);
                $dirTimestamp = $dateTime->getTimestamp();
                $validDirs[$dir] = $dirTimestamp;
            }
        }

        // Sort valid directories based on creation time, oldest first
        asort($validDirs);

        // Delete oldest directories until the desired amount is reached
        $deletedCount = 0;

        if ($amount > 0) {
            $countToDelete = max(0, count($validDirs) - $amount);

            foreach (array_slice($validDirs, 0, $countToDelete) as $dir => $timestamp) {
                $this->dirDelete("{$remotePath}/{$dir}");
                unset($validDirs[$dir]);
                ++$deletedCount;
            }
        }

        // Delete directories older than the specified days
        if ($ageInDays > 0) {
            $cutoffTime = time() - ($ageInDays * 24 * 60 * 60);

            foreach ($validDirs as $dir => $timestamp) {
                if ($timestamp < $cutoffTime) {
                    $this->dirDelete("{$remotePath}/{$dir}");
                    unset($validDirs[$dir]);
                    ++$deletedCount;
                }
            }
        }

        return $deletedCount;
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

    /**
     * @see AbstractRemoteHandler::dirList()
     */
    abstract protected function _dirList(string $remotePath): array;

    /**
     * Sanitizes the remote path for file-related checks.
     *
     * @param string $remotePath The remote path to sanitize
     * @param bool $checkFilePath Whether to check if the path belongs to a file (default is true)
     *
     * @throws RemoteStorageNotConnectedException If the remote storage is not connected
     * @throws InvalidArgumentException If the provided path belongs to a file, not a directory
     */
    private function sanitizeFileCheck(string $remotePath, bool $checkFilePath = true): void
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }
        if ($checkFilePath && !$this->isFilePath($remotePath)) {
            throw new \InvalidArgumentException('The provided path belongs to a file, not a directory.');
        }
    }

    /**
     * Sanitizes the remote path for directory-related checks.
     *
     * @param string $remotePath The remote path to sanitize
     * @param bool $allowFilePaths Whether to allow file paths (default is false)
     *
     * @throws RemoteStorageNotConnectedException If the remote storage is not connected
     * @throws InvalidArgumentException If the provided path belongs to a file, not a directory
     */
    private function sanitizeDirCheck(string $remotePath, bool $allowFilePaths = false): void
    {
        if (!$this->isConnected()) {
            throw new RemoteStorageNotConnectedException('The remote storage is not connected. Call connect() function.');
        }
        if (!$allowFilePaths && $this->isFilePath($remotePath)) {
            throw new \InvalidArgumentException('The provided path belongs to a file, not a directory.');
        }
    }

    /**
     * Checks if the provided remote path belongs to a file.
     *
     * @param string $remotePath The remote path to check
     *
     * @return bool True if the path belongs to a file, false if it belongs to a directory
     */
    private function isFilePath(string $remotePath): bool
    {
        // Split the path and file name
        $pathInfo = pathinfo($remotePath);

        // Check if it's a file path, if true, remove the file name
        return !empty($pathInfo['extension']);
    }
}
