<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Remote;

final class Ftp extends AbstractRemoteHandler
{
    private $ftpServer;
    private $ftpPort;

    /**
     * FtpHandler constructor.
     *
     * @param string $ftpServer the FTP server address
     * @param int $ftpPort the FTP server port (default is 21)
     */
    public function __construct(string $ftpServer, int $ftpPort = 21)
    {
        $this->ftpServer = $ftpServer;
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
    public function connect(string $ftpUserName, string $ftpUserPass): void
    {
        if ($this->isConnected()) {
            return;
        }

        // Try to connect
        $this->connection = ftp_ssl_connect($this->ftpServer, $this->ftpPort);

        // Check connection
        if (!$this->connection) {
            throw new \Exception("Can not connect to '{$this->ftpServer}' on Port {$this->ftpPort}");
        }

        // Try to login
        if (!ftp_login($this->connection, $ftpUserName, $ftpUserPass)) {
            throw new \Exception("Can not log into '{$this->ftpServer}'. Check credentials.");
        }

        ftp_pasv($this->connection, true);
    }

    /**
     * Disconnects from the FTP server.
     */
    public function disconnect(): void
    {
        if ($this->isConnected()) {
            ftp_close($this->connection);
        }
        $this->connection = null;
    }


    protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool
    {
        return ftp_put($this->connection, $remoteFilePath, $localFilePath);
    }

    protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool
    {
        return ftp_get($this->connection, $localFilePath, $remoteFilePath);
    }

    protected function _fileDelete(string $remoteFilePath): bool
    {
        return ftp_delete($this->connection, $remoteFilePath);
    }

    protected function _fileExists(string $remoteFilePath): bool
    {
        $content = ftp_nlist($this->connection, $remoteFilePath);
        return in_array($fileName, $content);
    }

    protected function _createDirectory(string $remoteFilePath): bool
    {
        return ftp_mkdir($this->connection, $remoteFilePath);
    }
}
