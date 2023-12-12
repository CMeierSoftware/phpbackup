<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use Defuse\Crypto\File;

abstract class FileCrypt
{
    public static function encryptFile(string $inputFile, string $key)
    {
        if (!file_exists($inputFile)) {
            throw new FileNotFoundException();
        }

        $tempFile = $inputFile . uniqid();

        try {
            File::encryptFileWithPassword($inputFile, $tempFile, $key);
        } catch (\Exception $th) {
            unlink($tempFile);
            throw $th;
        }

        // Delete the original file
        if (!unlink($inputFile)) {
            throw new \Exception('Failed to delete the original file after encryption.');
        }

        if (!rename($tempFile, $inputFile)) {
            throw new \Exception('Failed to rename the temporary file after encryption.');
        }
    }

    public static function decryptFile(string $inputFile, string $key)
    {
        if (!file_exists($inputFile)) {
            throw new FileNotFoundException();
        }

        $tempFile = $inputFile . uniqid();
        try {
            File::decryptFileWithPassword($inputFile, $tempFile, $key);
        } catch (\Exception $th) {
            unlink($tempFile);
            throw $th;
        }

        // Delete the original file
        if (!unlink($inputFile)) {
            throw new \Exception('Failed to delete the original file after encryption.');
        }

        if (!rename($tempFile, $inputFile)) {
            throw new \Exception('Failed to rename the temporary file after encryption.');
        }
    }
}
