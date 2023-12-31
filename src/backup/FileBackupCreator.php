<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Backup;

use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use PhpZip\Util\Iterator\IgnoreFilesRecursiveFilterIterator;
use PhpZip\ZipFile;

/**
 * Class FileBackupCreator.
 */
class FileBackupCreator
{
    private ZipFile $archive;
    private readonly string $srcDir;
    private string $archiveName;
    private array $ignoreList = [];

    /**
     * Constructor of class.
     *
     * @param null|array $ignoreList A list of file or directory names to ignore
     */
    public function __construct(array $ignoreList = null)
    {
        if (null !== $ignoreList) {
            $this->ignoreList = $ignoreList;
        }
        $this->archive = new ZipFile();
    }

    /**
     * Function runs the archive and backs up everything recursively in the given path.
     *
     * @param string $src Path to the source directory
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
            FileLogger::getInstance()->info("Backup created for '{$src}' at '{$this->archiveName}'.");
        } finally {
            $this->archive->close();
        }

        return $this->archiveName;
    }

    /**
     * Function backs up only specified files from the source directory.
     *
     * @param string $src Path to the source directory
     * @param array $files List of files to be backed up
     *
     * @return string Path of the created zip file
     */
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
            FileLogger::getInstance()->info("Backup created for '{$src}' with specific files at '{$this->archiveName}'.");
        } finally {
            $this->archive->close();
        }

        return $this->archiveName;
    }

    /**
     * Prepares the backup by validating the source directory and setting up necessary parameters.
     *
     * @param string $src Path to the source directory
     *
     * @return string The validated source directory
     *
     * @throws FileNotFoundException If the source directory is not found
     */
    private function prepareBackup(string $src): string
    {
        $srcDir = realpath($src);
        if (!$srcDir || !FileHelper::doesDirExists($srcDir)) {
            throw new FileNotFoundException("Can not find '{$srcDir}'.");
        }

        $this->archiveName = TEMP_DIR . 'backup_' . basename($srcDir) . date('_Y-m-d_H-i-s') . '.zip';

        $ignore = [TEMP_DIR, $this->archiveName];
        $this->ignoreList = array_merge($ignore, $this->ignoreList);

        return $srcDir;
    }
}
