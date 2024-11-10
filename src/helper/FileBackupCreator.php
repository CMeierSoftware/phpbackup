<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Helper;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use PhpZip\Constants\ZipCompressionLevel;
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
    public function __construct(array $ignoreList = [])
    {
        $this->ignoreList = $ignoreList;
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

        $ignoreIterator = new IgnoreFilesRecursiveFilterIterator(
            new \RecursiveDirectoryIterator($srcDir),
            $this->ignoreList
        );

        try {
            $this->archive->addFilesFromIterator($ignoreIterator);
            $this->archive->setCompressionLevel(ZipCompressionLevel::MAXIMUM);
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

        $files = array_map(
            static fn (string $file): string => rtrim($srcDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file,
            $files
        );

        $files = array_filter(
            $files,
            function (string $file): bool {
                foreach ($this->ignoreList as $ignorePath) {
                    if (str_starts_with($file, $ignorePath)) {
                        return false;
                    }
                }

                return file_exists($file);
            }
        );

        try {
            foreach ($files as $file) {
                $this->archive->addFile($file, basename($file));
            }
            $this->archive->setCompressionLevel(ZipCompressionLevel::MAXIMUM);
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
        if (!FileHelper::directoryExists($src)) {
            throw new FileNotFoundException("Can not find '{$src}'.");
        }

        $this->archiveName = TEMP_DIR . 'backup_' . basename($src) . date('_Y-m-d_H-i-s') . '.zip';

        $ignore = [TEMP_DIR, $this->archiveName];
        $this->ignoreList = array_merge($ignore, $this->ignoreList);

        return $src;
    }
}
