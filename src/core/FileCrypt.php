<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use Defuse\Crypto\File;

/**
 * Class FileCrypt.
 */
final class FileCrypt
{
    /**
     * Encrypts a file using a given key.
     *
     * @param string $inputFile the path to the input file
     * @param string $key the encryption key
     *
     * @throws \InvalidArgumentException if the encryption key is empty
     * @throws FileNotFoundException if the input file is not found
     * @throws \Exception if any other error occurs during encryption
     */
    public static function encryptFile(string $inputFile, string $key): void
    {
        self::processFile($inputFile, $key, true);
    }

    /**
     * Decrypts a file using a given key.
     *
     * @param string $inputFile the path to the input file
     * @param string $key the decryption key
     *
     * @throws \InvalidArgumentException if the decryption key is empty
     * @throws FileNotFoundException if the input file is not found
     * @throws \Exception if any other error occurs during decryption
     */
    public static function decryptFile(string $inputFile, string $key): void
    {
        self::processFile($inputFile, $key, false);
    }

    /**
     * Encrypts or decrypts a file using a given key.
     *
     * @param string $inputFile the path to the input file
     * @param string $key the encryption or decryption key
     * @param bool $isEncrypt if true, performs encryption; otherwise, performs decryption
     *
     * @throws \InvalidArgumentException if the key is empty
     * @throws FileNotFoundException if the input file is not found
     * @throws \Exception if any other error occurs during encryption or decryption
     */
    private static function processFile(string $inputFile, string $key, bool $isEncrypt): void
    {
        if (empty($key)) {
            throw new \InvalidArgumentException(($isEncrypt ? 'Encryption' : 'Decryption') . ' key cannot be empty.');
        }

        if (!FileHelper::fileExists($inputFile)) {
            throw new FileNotFoundException("File not found: {$inputFile}");
        }

        $tempFile = $inputFile . uniqid();
        $action = $isEncrypt ? 'Encrypt' : 'Decrypt';

        FileLogger::getInstance()->debug("{$action} '{$inputFile}' into temp file '{$tempFile}'.");

        $method = strtolower($action) . 'FileWithPassword';

        try {
            File::$method($inputFile, $tempFile, $key);

            FileHelper::deleteFile($inputFile);
            FileHelper::moveFile($tempFile, $inputFile);
        } catch (\Throwable $th) {
            FileHelper::deleteFile($tempFile);

            throw new ($th::class)(
                'Failed to replace original file with temporary file: ' . $th->getMessage(),
                $th->getCode(),
                $th
            );
        }

        FileLogger::getInstance()->debug('Renamed tempFile back to inputFile.');
        FileLogger::getInstance()->info("{$action} {$inputFile} successful completed.");
    }
}
