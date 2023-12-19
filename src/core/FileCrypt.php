<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use Defuse\Crypto\File;

final class FileCrypt
{
    public static function encryptFile(string $inputFile, string $key)
    {
        // Check if the key is empty
        if (empty($key)) {
            throw new \InvalidArgumentException('Encryption key cannot be empty.');
        }

        // Check if the file exists
        if (!file_exists($inputFile)) {
            throw new FileNotFoundException("File not found: $inputFile");
        }

        $tempFile = $inputFile . uniqid();

        FileLogger::getInstance()->Info("Encrypt '{$inputFile}' into temp file '{$tempFile}'.");

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
        FileLogger::getInstance()->Info("Renamed tempFile back to inputFile.");

    }

    public static function decryptFile(string $inputFile, string $key)
    {
        // Check if the key is empty
        if (empty($key)) {
            throw new \InvalidArgumentException('Decryption key cannot be empty.');
        }

        // Check if the file exists
        if (!file_exists($inputFile)) {
            throw new FileNotFoundException("File not found: $inputFile");
        }

        $tempFile = $inputFile . uniqid();

        FileLogger::getInstance()->Info("Decrypt '{$inputFile}' into temp file '{$tempFile}'.");

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
        FileLogger::getInstance()->Info("Renamed tempFile back to inputFile.");
    }
}
