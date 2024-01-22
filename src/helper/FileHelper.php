<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Helper;

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\FileNotReadableException;
use CMS\PhpBackup\Exceptions\FileNotWriteableException;
use Exception;

abstract class FileHelper
{
    /**
     * Function moves a file. Throws an Exception if the file cannot be moved.
     *
     * @param string $src source path
     * @param string $dest destination path
     *
     * @throws \Exception if the file cannot be moved
     */
    public static function moveFile(string $src, string $dest): void
    {
        if (!self::fileExists($src)) {
            throw new FileNotFoundException("Source file not found '{$src}'.");
        }
        if (self::fileExists($dest)) {
            throw new FileAlreadyExistsException("Destination file already exists '{$dest}'.");
        }
        if (!is_readable($src)) {
            throw new FileNotReadableException("Source file is not readable '{$src}'.");
        }

        FileLogger::getInstance()->debug("Move '{$src}' to '{$dest}'.");

        if (!rename($src, $dest)) {
            throw new \Exception("Cannot move '{$src}' to '{$dest}'.");
        }
    }

    /**
     * Deletes a file at the specified source path. Throws exceptions for various error scenarios.
     *
     * @param string $src the source path of the file to be deleted
     *
     * @throws FileNotFoundException if the file does not exist
     * @throws FileNotWriteableException if the file is not writable
     * @throws \Exception if the file cannot be deleted for any other reason
     */
    public static function deleteFile(string $src): void
    {
        if (!file_exists($src) || !is_file($src)) {
            throw new FileNotFoundException("File not found: '{$src}'.");
        }

        if (!is_writable($src)) {
            throw new FileNotWriteableException("File is not writable: '{$src}'.");
        }

        if (!unlink($src)) {
            throw new \Exception("Unable to delete file '{$src}'.");
        }

        FileLogger::getInstance()->debug("File '{$src}' successfully deleted.");
    }

    /**
     * Function creates a new directory if it does not exist.
     *
     * @param string $path path to create, e.g. "foo/bar/exists/new_dir". Missing parent path will be also added
     *
     * @throws \Exception if the directory cannot be created
     */
    public static function makeDir(string $path, int $mode = 0o755): void
    {
        if ('.' === $path || is_dir($path)) {
            FileLogger::getInstance()->debug("Directory already exists {$path}.");

            return;
        }
        FileLogger::getInstance()->debug("Create directory {$path}.");
        if (!mkdir($path, $mode, true)) {
            throw new \Exception("Cannot create {$path}.");
        }
    }

    /**
     * Deletes a given directory recursively.
     * Deletes all files and directories within the specified directory before deleting the directory itself.
     *
     * @param string $dirname path to the directory which should be deleted
     */
    public static function deleteDirectory(string $dirname): void
    {
        if (!self::directoryExists($dirname)) {
            return;
        }

        FileLogger::getInstance()->debug("Delete directory {$dirname} recursively.");

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirname, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                self::deleteFile($file->getPathname());
            }
        }

        rmdir($dirname);
    }

    /**
     * Checks if a file exists.
     *
     * @param string $path the path of the file to check
     *
     * @return bool TRUE if the file exists, FALSE otherwise
     */
    public static function fileExists(string $path): bool
    {
        return file_exists($path) && is_file($path);
    }

    /**
     * Checks if a directory exists.
     *
     * @param string $path the path of the directory to check
     *
     * @return bool TRUE if the directory exists, FALSE otherwise
     */
    public static function directoryExists(string $path): bool
    {
        return file_exists($path) && is_dir($path);
    }

    /**
     * Changes the permissions of a file.
     *
     * @param string $filePath the path of the file to change permissions on
     * @param int $chmod the new permissions to set on the file
     *
     * @throws \Exception if there is a problem changing the file permissions
     */
    public static function changeFilePermission(string $filePath, int $chmod): void
    {
        if (!self::fileExists($filePath)) {
            throw new FileNotFoundException("File not found '{$filePath}'.");
        }

        if (!chmod($filePath, $chmod)) {
            throw new \Exception("Failed to set permissions on file {$filePath}");
        }
    }
}
