<?php

declare(strict_types=1);

// namespace CMS\PhpBackup\Remote;

// final class Ftp extends AbstractRemoteHandler
// {
//     private $ftpServer;
//     private $ftpUserName;
//     private $ftpUserPass;
//     private $ftpPort;

//     /**
//      * FtpHandler constructor.
//      *
//      * @param string $ftpServer the FTP server address
//      * @param int $ftpPort the FTP server port (default is 21)
//      */
//     public function __construct(string $ftpServer, string $ftpUserName, string $ftpUserPass, int $ftpPort = 21)
//     {
//         parent::__construct();

//         $this->ftpServer = $ftpServer;
//         $this->ftpUserName = $ftpUserName;
//         $this->ftpUserPass = $ftpUserPass;
//         $this->ftpPort = $ftpPort;
//     }

//     /**
//      * Establishes a connection to the FTP server.
//      *
//      * @param string $ftpUserName the FTP username
//      * @param string $ftpUserPass the FTP password
//      *
//      * @throws \Exception if the connection or login fails
//      */
//     public function connect(): bool
//     {
//         if ($this->isConnected()) {
//             return false;
//         }

//         // Try to connect
//         $this->connection = ftp_ssl_connect($this->ftpServer, $this->ftpPort);

//         // Check connection
//         if (!$this->connection) {
//             throw new \Exception("Can not connect to '{$this->ftpServer}' on Port {$this->ftpPort}");
//         }

//         // Try to login
//         if (!ftp_login($this->connection, $this->ftpUserName, $this->ftpUserPass)) {
//             throw new \Exception("Can not log into '{$this->ftpServer}'. Check credentials.");
//         }

//         ftp_pasv($this->connection, true);
//         return null !== $this->connection;
//     }

//     /**
//      * Disconnects from the FTP server.
//      */
//     public function disconnect(): bool
//     {
//         if ($this->isConnected()) {
//             ftp_close($this->connection);
//         }
//         $this->connection = null;
//         return null !== $this->connection;
//     }

//     protected function _fileUpload(string $localFilePath, string $remoteFilePath): bool
//     {
//         return ftp_put($this->connection, $remoteFilePath, $localFilePath);
//     }

//     protected function _fileDownload(string $localFilePath, string $remoteFilePath): bool
//     {
//         return ftp_get($this->connection, $localFilePath, $remoteFilePath);
//     }

//     protected function _fileDelete(string $remoteFilePath): bool
//     {
//         return ftp_delete($this->connection, $remoteFilePath);
//     }

//     protected function _fileExists(string $remoteFilePath): bool
//     {
//         $content = ftp_nlist($this->connection, $remoteFilePath);

//         return in_array($remoteFilePath, $content, true);
//     }

//     protected function _dirCreate(string $remoteFilePath): bool
//     {
//         return ftp_mkdir($this->connection, $remoteFilePath);
//     }

//     protected function _dirDelete(string $remotePath): bool
//     {
//         throw new \Exception("Implement me", 1);

//     }

//     protected function _dirList(string $remotePath): array
//     {
//         throw new \Exception("Implement me", 1);
//     }
// }
