<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;
use phpseclib3\Net\SFTP;

final class SecureFtp extends AbstractRemoteHandler
{
    private readonly string $ftpServer;
    private readonly string $ftpUserName;
    private readonly string $ftpUserPass;
    private readonly int $ftpPort;
    private const SKIP_DIRS = ['.', '..'];

    /**
     * FtpHandler constructor.
     *
     * @param string $ftpServer the FTP server address
     * @param int $ftpPort the FTP server port (default is 22)
     */
    public function __construct(string $ftpServer, string $ftpUserName, string $ftpUserPass, int $ftpPort = 22)
    {
        parent::__construct();

        $this->ftpServer = $ftpServer;
        $this->ftpUserName = $ftpUserName;
        $this->ftpUserPass = $ftpUserPass;
        $this->ftpPort = $ftpPort;
    }

    /**
     * Establishes a connection to the FTP server.
     *
     * @param string $ftpUserName the FTP username
     * @param string $ftpUserPass the FTP password
     *
     * @throws \Exception if the connection or login fails
     */
    public function connect(): bool
    {
        if ($this->isConnected()) {
            return false;
        }

        $this->connection = new SFTP($this->ftpServer, $this->ftpPort);
        // Try to login
        if (!$this->connection->login($this->ftpUserName, $this->ftpUserPass)) {
            throw new \Exception("Can not log into '{$this->ftpServer}'. Check credentials.");
        }

        return null !== $this->connection;
    }

    /**
     * Disconnects from the FTP server.
     */
    public function disconnect(): bool
    {
        if ($this->isConnected()) {
            $this->connection->disconnect();
        }
        $this->connection = null;
        return true;
    }

    protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        $this->goto($remoteFilePath);
        return $this->connection->put(basename($remoteFilePath), $localFilePath, SFTP::SOURCE_LOCAL_FILE);
    }

    protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        $this->goto($remoteFilePath);
        return $this->connection->get(basename($remoteFilePath), $localFilePath);
    }

    protected function _fileDelete(string $remoteFilePath): bool
    {
        $this->goto($remoteFilePath);
        return $this->connection->delete(basename($remoteFilePath));
    }

    protected function _fileExists(string $remoteFilePath): bool
    {
        $path = $this->isFilePath($remoteFilePath) ? $remoteFilePath: dirname($remoteFilePath);
        return $this->goto($path, false) && $this->connection->file_exists(basename($remoteFilePath));
    }

    protected function _dirCreate(string $remoteFilePath): bool
    {
        return $this->goto($remoteFilePath) && $this->connection->mkdir(basename($remoteFilePath));
    }

    protected function _dirDelete(string $remotePath): bool
    {
        $this->goto($remotePath);
        // Delete each file in the directory
        foreach ($this->dirList($remotePath) as $item) {
            if (in_array($item, self::SKIP_DIRS)) {
                continue;
            }
            $fullPath = $remotePath . DIRECTORY_SEPARATOR . $item;
            if ($this->isFilePath($fullPath)) {
                $this->_fileDelete($fullPath);
            } else {
                $this->_dirDelete($fullPath); // Recursive call for nested directories
            }
        }
        
        // Delete the directory itself
        return $this->connection->rmdir('.');
    }

    protected function _dirList(string $remotePath): array
    {
        $this->goto($remotePath);
        $items = $this->connection->nlist('.');

        return array_filter($items, static fn($item): bool => !in_array($item, self::SKIP_DIRS));
    }

    private function goto(string $destinationPath, bool $createDirs = true): bool
    {
        // Check if the destination is a file and get the directory path
        if ($this->isFilePath($destinationPath)) {
            $destinationPath = dirname($destinationPath);
        }

        $directories = array_filter(explode(DIRECTORY_SEPARATOR, $destinationPath));

        // Change to root directory
        $this->connection->chdir('/');
        
        // Traverse through directories and create any that don't exist
        foreach ($directories as $dir) {
            if (!$this->connection->file_exists($dir) || !$this->connection->is_dir($dir)) {
                if ($createDirs) {
                    $this->connection->mkdir($dir);
                } else {
                    return false;
                }
            }
            $this->connection->chdir($dir);
        }
        return true;
    }
}
