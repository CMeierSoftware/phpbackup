<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

/**
 * The FileBundleCreator class helps create bundles of files from a directory based on a size limit.
 */
final class FileBundleCreator
{
    private static $rootDir = '';

    /**
     * Create bundles of files from a directory based on a size limit.
     *
     * @param string $rootDir the path to the directory
     * @param int $sizeLimitInMB the size limit for each bundle in megabytes
     *
     * @return array an array of arrays, where each inner array represents a bundle of files within the size limit
     */
    public static function createFileBundles(string $rootDir, int $sizeLimitInMB, array &$refBundles): void
    {
        $sizeLimit = $sizeLimitInMB * 1024 * 1024; // Convert MB to bytes
        $rootDir = rtrim($rootDir, '/\\' . DIRECTORY_SEPARATOR);
        self::$rootDir = $rootDir;

        FileLogger::getInstance()->info("Calculating bundles for '{$rootDir}' each {$sizeLimitInMB} MB ({$sizeLimit} bytes).");

        $bundles = [];
        self::packDirectory($rootDir, $sizeLimit, $refBundles);
        $bundleCount = count($refBundles);
        FileLogger::getInstance()->info("Calculated {$bundleCount} bundles for '{$rootDir}'.");
    }

    /**
     * Recursively pack files from a directory into bundles.
     *
     * @param string $directory the path to the directory
     * @param int $sizeLimit the size limit for each bundle in bytes
     * @param array $fileBundles an array to store the file bundles
     */
    private static function packDirectory(string $directory, int $sizeLimit, array &$fileBundles): void
    {
        list($files, $dirs) = self::listDirSortedByFileSize($directory);

        self::packFiles($files, $sizeLimit, $fileBundles);

        foreach ($dirs as $dir) {
            self::packDirectory($dir, $sizeLimit, $fileBundles);
        }
    }

    /**
     * Pack files into bundles based on the size limit.
     *
     * @param array $files an array of files with their sizes
     * @param int $sizeLimit the size limit for each bundle in bytes
     * @param array $fileBundles an array to store the file bundles
     */
    private static function packFiles(array $files, int $sizeLimit, array &$fileBundles): void
    {
        $currentBundle = [];
        $currentSize = 0;
        $notPackedFiles = $files;
        if (end($fileBundles)) {
            $currentBundle = end($fileBundles);
            array_pop($fileBundles);
            foreach ($currentBundle as $f) {
                $currentSize += filesize(self::$rootDir . $f);
            }
        }

        foreach ($files as $file => $fileSize) {
            if (!array_key_exists($file, $notPackedFiles)) {
                continue;
            }

            if ($fileSize >= $sizeLimit) {
                // File is bigger than limit -> own bundle
                $fileBundles[] = [$file];
                unset($notPackedFiles[$file]);
            } elseif ($currentSize + $fileSize <= $sizeLimit) {
                // File fits in the current bundle -> add
                $currentBundle[] = $file;
                $currentSize += $fileSize;
                unset($notPackedFiles[$file]);
            } else {
                // File doesn't fit in the current bundle -> find other files in this sub dir and start a new bundle with this file
                $currentBundle = self::fillBundle($currentBundle, $sizeLimit - $currentSize, $notPackedFiles);
                $fileBundles[] = $currentBundle;

                $currentSize = $fileSize;
                $currentBundle = [$file];
                unset($notPackedFiles[$file]);
            }
        }

        // Last element
        if (!empty($currentBundle)) {
            $fileBundles[] = $currentBundle;
        }
    }

    /**
     * Fill a bundle with files that fit within the remaining size limit.
     *
     * @param array $bundle the current bundle of files
     * @param int $remainingSize the remaining size limit
     * @param array $notPackedFiles an array of files that have not been packed yet
     *
     * @return array the updated bundle
     */
    private static function fillBundle(array $bundle, int $remainingSize, array &$notPackedFiles): array
    {
        foreach (array_keys($notPackedFiles) as $file) {
            if ($notPackedFiles[$file] <= $remainingSize) {
                // File fits in the current bundle -> add
                $bundle[] = $file;
                $remainingSize -= $notPackedFiles[$file];
                unset($notPackedFiles[$file]);
            }
        }

        return $bundle;
    }

    /**
     * List files and folders in a directory, sorted by file size.
     *
     * @param string $dir the path to the directory
     *
     * @return array an array containing two arrays - files sorted by size and folders
     */
    private static function listDirSortedByFileSize(string $dir): array
    {
        $files = [];
        $folders = [];

        foreach (scandir($dir) as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;

            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir($filePath)) {
                $folders[] = $filePath;
            } else {
                $files[self::trimFilePath($filePath)] = filesize($filePath);
            }
        }

        asort($files);

        return [array_reverse($files), $folders];
    }

    private static function trimFilePath(string $filePath): string
    {
        return str_replace(self::$rootDir, '', $filePath);
    }
}
