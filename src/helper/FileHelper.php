<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Helper;

use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

abstract class FileHelper
{
    /**
     * Function moves a file. Throws an Exception if the file cannot be moved.
     *
     * @param string $src source path
     * @param string $dest destination path
     * @throws Exception if the file cannot be moved
     */
    public static function moveFile(string $src, string $dest)
    {
        if (!rename($src, $dest)) {
            throw new Exception("Cannot move $src to $dest.");
        }
    }

    /**
     * Function creates a new directory if it does not exist.
     *
     * @param string $path path to create, e.g. "foo/bar/exists/new_dir". Missing parent path will be also added
     * @throws Exception if the directory cannot be created
     */
    public static function makeDir(string $path, int $mode = 0644): void
    {
        if ($path === '.') {
            return;
        }
        if (!is_dir($path) && !mkdir($path, $mode, true)) {
            throw new Exception("Cannot create $path.");
        }
    }

    /**
     * Deletes a given directory recursively.
     * Deletes all files and directories within the specified directory before deleting the directory itself.
     *
     * @param string $dirname path to the directory which should be deleted
     */
    public static function deleteDirectory(string $dirname)
    {
        if (!self::doesDirExists($dirname)) {
            return;
        }

        $files = iterator_to_array(new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        ));

        foreach (array_reverse($files) as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dirname);
    }

    /**
     * Checks if a directory exists.
     *
     * @param string $dir The path of the directory to check.
     * @return bool TRUE if the directory exists, FALSE otherwise.
     */
    public static function doesDirExists(string $dir): bool
    {
        return file_exists($dir) && is_dir($dir);
    }

    /**
     * Changes the permissions of a file.
     *
     * @param string $file The path of the file to change permissions on.
     * @param int $chmod The new permissions to set on the file.
     * @throws Exception If there is a problem changing the file permissions.
     */
    public static function changePermission(string $file, int $chmod)
    {
        if (!chmod($file, $chmod)) {
            throw new Exception("Failed to set permissions on file $file");
        }
    }
}
