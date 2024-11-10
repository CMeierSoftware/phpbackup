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
    private readonly string $remoteRootPath;
    private const SKIP_DIRS = ['.', '..'];
    private const UNIX_SEPARATOR = '/';

    /**
     * FtpHandler constructor.
     *
     * @param string $ftpServer the FTP server address
     * @param int $ftpPort the FTP server port (default is 22)
     */
    public function __construct(string $ftpServer, string $ftpUserName, string $ftpUserPass, string $remoteRootPath = '', int $ftpPort = 22)
    {
        parent::__construct($remoteRootPath);

        $this->ftpServer = $ftpServer;
        $this->ftpUserName = $ftpUserName;
        $this->ftpUserPass = $ftpUserPass;
        $this->ftpPort = $ftpPort;
        $this->logger->info("SFTP Connection to '{$this->ftpServer}' as '{$this->ftpUserName}'.");
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
        return $this->goto($remoteFilePath) && $this->connection->put(basename($remoteFilePath), $localFilePath, SFTP::SOURCE_LOCAL_FILE);
    }

    protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        return $this->goto($remoteFilePath) && $this->connection->get(basename($remoteFilePath), $localFilePath);
    }

    protected function _fileDelete(string $remoteFilePath): bool
    {
        return $this->goto($remoteFilePath) && $this->connection->delete(basename($remoteFilePath));
    }

    protected function _fileExists(string $remoteFilePath): bool
    {
        return $this->goto(dirname($remoteFilePath)) && $this->connection->file_exists(basename($remoteFilePath));
    }

    protected function _dirCreate(string $remoteFilePath): bool
    {
        $directories = array_filter(explode(DIRECTORY_SEPARATOR, $remoteFilePath));
        $currentPath = '';

        foreach ($directories as $dir) {
            $currentPath .= $dir . self::UNIX_SEPARATOR;
    
            if (!$this->_fileExists($currentPath)) {
                $this->goto(dirname($currentPath));
                if (!$this->connection->mkdir($dir)) {
                    return false;
                }
            }
        }
    
        return true;
    }

    protected function _dirDelete(string $remotePath): bool
    {
        foreach ($this->dirList($remotePath) as $item) {
            if (in_array($item, self::SKIP_DIRS)) {
                continue;
            }
            $fullPath = $remotePath . DIRECTORY_SEPARATOR . $item;
            if ($this->isFilePath($fullPath)) {
                $this->fileDelete($fullPath);
            } else {
                $this->dirDelete($fullPath); // Recursive call for nested directories
            }
        }
        
        return $this->goto($remotePath) && $this->connection->rmdir('.');
    }

    protected function _dirList(string $remotePath): array
    {
        $this->goto($remotePath);
        $items = $this->connection->nlist('.');

        return array_filter($items, static fn($item): bool => !in_array($item, self::SKIP_DIRS));
    }

    private function goto(string $destinationPath): bool
    {
        if ($this->isFilePath($destinationPath)) {
            $destinationPath = dirname($destinationPath);
        }
        $destinationPath = $this->buildAbsPath($destinationPath);

        $directories = array_filter(explode(self::UNIX_SEPARATOR, $destinationPath));

        // Change to root directory
        $this->connection->chdir('/');
        
        // Traverse through directories and create any that don't exist
        foreach ($directories as $dir) {
            if (!$this->connection->file_exists($dir) || !$this->connection->is_dir($dir)) {
                return false;
            }

            $this->connection->chdir($dir);
        }
        return true;
        // // Check if the destination is a file and get the directory path
        // $destDirs = array_filter(explode(self::UNIX_SEPARATOR, $destinationPath));
        
        // $currentPath = $this->connection->pwd();
        // $currentDirs = array_filter(explode(self::UNIX_SEPARATOR, $currentPath));

        // $commonDirs = [];
        // $n = reset($destDirs);
        // foreach ($currentDirs as $dir) {
        //     if ($dir === $n) {
        //         $commonDirs[] = $dir;
        //     } else {
        //         break;
        //     }
        //     $n = next($destDirs);
        // }

        // // Change to root directory
        // if (!$this->connection->chdir(implode(self::UNIX_SEPARATOR, $commonDirs))) {
        //     return false;
        // }
        
        // var_dump("chdir: '" .implode(self::UNIX_SEPARATOR, $commonDirs)."', pwd (after chdir): '{$this->connection->pwd()}'");
        // // Traverse through directories and create any that don't exist
        // while($n !== false) {
        //     if (!$this->connection->file_exists($n)) {
        //         return false;
        //     }
        //     $this->connection->chdir($n);
        //     $n = next($destDirs);
        // }

        // return true;
    }   
    protected function buildAbsPath(string $remoteFilePath): string
    {
        $path = ltrim($this->remoteRootPath ?? '', ' /\\' . DIRECTORY_SEPARATOR) . trim($remoteFilePath, ' /\\' . DIRECTORY_SEPARATOR);
        $path = $this->isFilePath($path) ? $path : $path . DIRECTORY_SEPARATOR;
        return str_replace(DIRECTORY_SEPARATOR, self::UNIX_SEPARATOR, $path);
    }

}
