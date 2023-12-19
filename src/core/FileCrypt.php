<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use Defuse\Crypto\File;

/**
 * Class FileCrypt
 * @package CMS\PhpBackup\Core
 */
final class FileCrypt
{
    /**
     * Encrypts or decrypts a file using a given key.
     *
     * @param string $inputFile The path to the input file.
     * @param string $key The encryption or decryption key.
     * @param bool $isEncrypt If true, performs encryption; otherwise, performs decryption.
     * @throws \InvalidArgumentException If the key is empty.
     * @throws FileNotFoundException If the input file is not found.
     * @throws \Exception If any other error occurs during encryption or decryption.
     */
    private static function processFile(string $inputFile, string $key, bool $isEncrypt): void
    {
        if (empty($key)) {
            throw new \InvalidArgumentException(($isEncrypt ? 'Encryption' : 'Decryption') . ' key cannot be empty.');
        }

        if (!file_exists($inputFile)) {
            throw new FileNotFoundException("File not found: $inputFile");
        }

        $tempFile = $inputFile . uniqid();
        $action = $isEncrypt ? 'Encrypt' : 'Decrypt';

        FileLogger::getInstance()->info("$action '{$inputFile}' into temp file '{$tempFile}'.");

        try {
            $method = strtolower($action) . 'FileWithPassword';
            File::$method($inputFile, $tempFile, $key);
        } catch (\Exception $th) {
            unlink($tempFile);
            throw $th;
        }

        if (!unlink($inputFile)) {
            throw new \Exception("Failed to delete the original file after $action.");
        }

        if (!rename($tempFile, $inputFile)) {
            throw new \Exception("Failed to rename the temporary file after $action.");
        }

        FileLogger::getInstance()->info("Renamed tempFile back to inputFile.");
    }

    /**
     * Encrypts a file using a given key.
     *
     * @param string $inputFile The path to the input file.
     * @param string $key The encryption key.
     * @throws \InvalidArgumentException If the encryption key is empty.
     * @throws FileNotFoundException If the input file is not found.
     * @throws \Exception If any other error occurs during encryption.
     */
    public static function encryptFile(string $inputFile, string $key): void
    {
        self::processFile($inputFile, $key, true);
    }

    /**
     * Decrypts a file using a given key.
     *
     * @param string $inputFile The path to the input file.
     * @param string $key The decryption key.
     * @throws \InvalidArgumentException If the decryption key is empty.
     * @throws FileNotFoundException If the input file is not found.
     * @throws \Exception If any other error occurs during decryption.
     */
    public static function decryptFile(string $inputFile, string $key): void
    {
        self::processFile($inputFile, $key, false);
    }
}
