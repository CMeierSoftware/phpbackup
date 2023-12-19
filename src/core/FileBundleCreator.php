<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

final class FileBundleCreator
{
    /**
     * Create bundles of files from a directory based on a size limit.
     *
     * @param string $directory the path to the directory
     * @param int $sizeLimitInMB the size limit for each bundle in megabytes
     *
     * @return array an array of arrays, where each inner array represents a bundle of files within the size limit
     */
    public static function createFileBundles(string $directory, int $sizeLimitInMB): array
    {
        $sizeLimit = $sizeLimitInMB * 1024 * 1024; // Convert MB to bytes
        $directory = rtrim($directory, '/\\' . DIRECTORY_SEPARATOR);

        FileLogger::getInstance()->info("Calculate bundles for '{$directory}' each {$sizeLimitInMB} MB ({$sizeLimit} bytes).");

        $bundles = self::packDirectory($directory, $sizeLimit);
        $cnt = count($bundles);
        FileLogger::getInstance()->info("Calculated {$cnt} bundles for '{$directory}'.");

        return $bundles;
    }

    private static function packDirectory(string $directory, int $sizeLimit): array
    {
        $content = self::listDirSortedByFileSize($directory);

        $fileBundles = self::packFiles($content[0], $sizeLimit);

        foreach ($content[1] as $dirs) {
            $fileBundles = array_merge($fileBundles, self::packDirectory($dirs, $sizeLimit));
        }

        return $fileBundles;
    }

    private static function packFiles(array $files, int $sizeLimit): array
    {
        $fileBundles = [];
        $currentBundle = [];
        $currentSize = 0;
        $notPackedFiles = $files;

        foreach ($files as $file => $fileSize) {
            if (!array_key_exists($file, $notPackedFiles)) {
                continue;
            }

            if ($fileSize >= $sizeLimit) {
                // file bigger than limit -> own bundle
                $fileBundles[] = [$file];
                unset($notPackedFiles[$file]);
            } elseif ($currentSize + $fileSize <= $sizeLimit) {
                // file fits in current bundle -> add
                $currentBundle[] = $file;
                $currentSize += $fileSize;
                unset($notPackedFiles[$file]);
            } else {
                // file doesn't fit in current bundle -> find other files in this sub dir and start a new bundle with this file
                $currentBundle = self::fillBundle($currentBundle, $sizeLimit - $currentSize, $notPackedFiles);
                $fileBundles[] = $currentBundle;

                $currentSize = $fileSize;
                $currentBundle = [$file];
                unset($notPackedFiles[$file]);
            }
        }

        // last element
        $fileBundles[] = $currentBundle;

        return $fileBundles;
    }

    private static function fillBundle(array $bundle, int $leftSize, array &$notPackedFiles): array
    {
        foreach (array_keys($notPackedFiles) as $file) {
            if ($notPackedFiles[$file] <= $leftSize) {
                // file fits in current bundle -> add
                $bundle[] = $file;
                unset($notPackedFiles[$file]);
                $leftSize -= $notPackedFiles[$file];
            }
        }

        return $bundle;
    }

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
                $files[$filePath] = filesize($filePath);
            }
        }
        asort($files);

        return [array_reverse($files), $folders];
    }
}
