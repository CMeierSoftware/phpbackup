<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Exceptions\FileNotFoundException;

if (!defined('ABS_PATH')) {
    return;
}

class FileCrypt
{
    private const METHOD = 'aes-256-cbc';
    private const CHUNK_SIZE = 4096; // 4KB

    /**
     * Encrypts a file and deletes the original file.
     *
     * @param string $inputFile Path to the input file.
     * @param string $outputFile Path to the output (encrypted) file.
     * @param string $key Encryption key.
     * @throws \Exception If the encryption fails or the original file cannot be deleted.
     */
    public static function encryptFile($inputFile, $key)
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException("Module 'openssl' is not loaded.");
        }
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::METHOD));

        self::readWriteFile($inputFile, function ($chunk) use ($key, $iv) {
            return openssl_encrypt($chunk, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
        });
    }

    /**
     * Decrypts an encrypted file and deletes the original encrypted file.
     *
     * @param string $inputFile Path to the encrypted file.
     * @param string $outputFile Path to the output (decrypted) file.
     * @param string $key Decryption key.
     * @throws \Exception If the decryption fails or the original encrypted file cannot be deleted.
     */
    public static function decryptFile($inputFile, $key)
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException("Module 'openssl' is not loaded.");
        }
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::METHOD));

        self::readWriteFile($inputFile, function ($chunk) use ($key, $iv) {
            return openssl_decrypt($chunk, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
        });
    }

    private static function readWriteFile(string $inputFile, callable $fnc)
    {
        if (!file_exists($inputFile)) {
            throw new FileNotFoundException();
        }

        $tempFile = $inputFile . uniqid();

        try {
            $outputHandle = fopen($tempFile, 'wb');
            if ($outputHandle === false) {
                throw new \Exception('Failed to open the output file for writing.');
            }

            $inputHandle = fopen($inputFile, 'rb');
            if ($inputHandle === false) {
                throw new \Exception('Failed to open the input file for reading.');
            }

            while (!feof($inputHandle)) {
                $chunk = fread($inputHandle, self::CHUNK_SIZE);
                if (false === $chunk) {
                    throw new \Exception('Could not read file.');
                }

                $modifiedChunk = $fnc($chunk);
                if ($modifiedChunk === false) {
                    throw new \Exception('Encryption failed.');
                }

                if (false === fwrite($outputHandle, $modifiedChunk)) {
                    throw new \Exception('Could not write file.');
                }
            }
        } finally {
            fclose($inputHandle);
            fclose($outputHandle);
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
