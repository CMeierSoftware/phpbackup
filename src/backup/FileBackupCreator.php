<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Backup;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Exceptions\FileNotFoundException;

use PhpZip\ZipFile;
use PhpZip\Util\Iterator\IgnoreFilesRecursiveFilterIterator;

class FileBackupCreator
{
    private ZipFile $archive;
    private string $srcDir;
    private string $archiveName;
    private array $ignoreList = [];

    /**
     * constructor of class.
     *
     * @param array $ignoreList a list of file or directory names to ignore
     */
    public function __construct(array $ignoreList = null)
    {
        if ($ignoreList !== null) {
            $this->ignoreList = $ignoreList;
        }
        $this->archive = new ZipFile();
    }

    /**
     * Function runs the archive and backups everything recursive in the given path.
     *
     * @return string Path of the created zip file
     */
    public function backupAll(string $src): string
    {
        $srcDir = $this->prepareBackup($src);

        $directoryIterator = new \RecursiveDirectoryIterator($srcDir);

        $ignoreIterator = new IgnoreFilesRecursiveFilterIterator(
            $directoryIterator,
            $this->ignoreList
        );

        try {
            $this->archive->addFilesFromIterator($ignoreIterator);
            $this->archive->saveAsFile($this->archiveName);
        } finally {
            $this->archive->close();
        }

        return $this->archiveName;
    }

    public function backupOnly(string $src, array $files): string
    {
        $srcDir = $this->prepareBackup($src);

        try {
            foreach ($files as $file) {
                $srcFilePath = rtrim($srcDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

                if (file_exists($srcFilePath)) {
                    $this->archive->addFile($srcFilePath, $file);
                }
            }
            $this->archive->saveAsFile($this->archiveName);

        } finally {
            $this->archive->close();
        }

        return $this->archiveName;

    }

    private function prepareBackup(string $src): string
    {
        $srcDir = realpath($src);
        if (!$srcDir || !file_exists($srcDir)) {
            throw new FileNotFoundException("Can not find '{$srcDir}'.");
        }

        $this->archiveName = TEMP_DIR . 'backup_' . basename($srcDir) . date('_Y-m-d_H-i-s') . '.zip';

        $ignore = [TEMP_DIR, $this->archiveName];
        $this->ignoreList = array_merge($ignore, $this->ignoreList);

        return $srcDir;
    }
}
