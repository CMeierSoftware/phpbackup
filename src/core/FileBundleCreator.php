<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;

/**
 * The FileBundleCreator class helps create bundles of files from a directory based on a size limit.
 */
final class FileBundleCreator
{
    private readonly string $rootDir;
    private readonly int $sizeLimit;
    private array $bundles;
    private readonly array $excludes;

    private function __construct(string $rootDir, int $sizeLimitInMB, array &$refBundles, array $excludedDirs = [])
    {
        $separator = '/\\' . DIRECTORY_SEPARATOR;
        $this->rootDir = rtrim($rootDir, $separator);

        $this->sizeLimit = $sizeLimitInMB * 1024 * 1024; // Convert MB to bytes

        $this->excludes = array_map(fn ($dir): string => $this->rootDir . DIRECTORY_SEPARATOR . ltrim($dir, $separator), $excludedDirs);

        $this->bundles = &$refBundles;
    }

    /**
     * Create bundles of files from a directory based on a size limit.
     *
     * @param string $rootDir the path to the directory
     * @param int $sizeLimitInMB the size limit for each bundle in megabytes
     * @param array $refBundles an array to store the file bundles
     * @param array $excludedDirs an array of directories to exclude from bundling
     */
    public static function createFileBundles(string $rootDir, int $sizeLimitInMB, array &$refBundles, array $excludedDirs = []): void
    {
        $fbc = new self($rootDir, $sizeLimitInMB, $refBundles, $excludedDirs);

        FileLogger::getInstance()->info("Calculating bundles for '{$rootDir}' each {$sizeLimitInMB} MB.");

        $fbc->packDirectory();

        $bundleCount = count($refBundles);
        FileLogger::getInstance()->info("Calculated {$bundleCount} bundles for '{$rootDir}'.");
    }

    /**
     * Recursively pack files from a directory into bundles.
     *
     * @param string $directory the path to the directory
     */
    private function packDirectory(string $directory = ''): void
    {
        if (empty($directory)) {
            $directory = $this->rootDir;
        }

        list($files, $dirs) = $this->listDirSortedByFileSize($directory);

        $this->packFiles($files);

        foreach (array_diff($dirs, $this->excludes) as $dir) {
            $this->packDirectory($dir);
        }
    }

    private function getLastElementFromBundles(): array
    {
        $currentBundle = [];
        if (end($this->bundles)) {
            $currentBundle = end($this->bundles);
            array_pop($this->bundles);
            foreach ($currentBundle as $f) {
                $currentSize += filesize($this->rootDir . $f);
            }
        }

        return $currentBundle;
    }

    /**
     * Pack files into bundles based on the size limit.
     *
     * @param array $files an array of files with their sizes
     */
    private function packFiles(array $files): void
    {
        $currentBundle = [];
        $currentSize = 0;
        $notPackedFiles = $files;
        if (end($this->bundles)) {
            $currentBundle = end($this->bundles);
            array_pop($this->bundles);
            foreach ($currentBundle as $f) {
                $currentSize += filesize($this->rootDir . $f);
            }
        }

        foreach ($files as $file => $fileSize) {
            if (!array_key_exists($file, $notPackedFiles)) {
                continue;
            }

            if ($fileSize >= $this->sizeLimit) {
                // File is bigger than limit -> own bundle
                $this->bundles[] = [$file];
                unset($notPackedFiles[$file]);
            } elseif ($currentSize + $fileSize <= $this->sizeLimit) {
                // File fits in the current bundle -> add
                $currentBundle[] = $file;
                $currentSize += $fileSize;
                unset($notPackedFiles[$file]);
            } else {
                // File doesn't fit in the current bundle -> find other files in this sub dir and start a new bundle with this file
                $currentBundle = self::fillBundle($currentBundle, $this->sizeLimit - $currentSize, $notPackedFiles);
                $this->bundles[] = $currentBundle;

                $currentSize = $fileSize;
                $currentBundle = [$file];
                unset($notPackedFiles[$file]);
            }
        }

        // Last element
        if (!empty($currentBundle)) {
            $this->bundles[] = $currentBundle;
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
    private function listDirSortedByFileSize(string $dir): array
    {
        $files = [];
        $folders = [];

        if (!FileHelper::doesDirExists($dir)) {
            throw new FileNotFoundException("Can not scandir on not existing directory '{$dir}'");
        }

        foreach (scandir($dir) as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;

            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir($filePath)) {
                $folders[] = $filePath;
            } else {
                $files[$this->trimFilePath($filePath)] = filesize($filePath);
            }
        }

        asort($files);

        return [array_reverse($files), $folders];
    }

    private function trimFilePath(string $filePath): string
    {
        return str_replace($this->rootDir, '', $filePath);
    }
}
