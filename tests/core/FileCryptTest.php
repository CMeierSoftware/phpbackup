<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Core;

use CMS\PhpBackup\Core\FileCrypt;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Core\FileCrypt
 */
final class FileCryptTest extends TestCase
{
    private const TEST_KEY = 'your_secret_key';
    private const TEST_FILE_PLAIN = TEST_WORK_DIR . 'file1.txt';
    private const TEST_FILE_ENCRYPTED = TEST_WORK_DIR . 'encrypted_file1.txt';

    protected function setUp(): void
    {
        FileHelper::makeDir(TEST_WORK_DIR);
        self::assertDirectoryExists(TEST_WORK_DIR);

        copy(TEST_FIXTURES_FILE_1, self::TEST_FILE_PLAIN);
        self::assertFileExists(self::TEST_FILE_PLAIN);

        copy(TEST_FIXTURES_ENCRYPTION_DIR . 'encrypted_file1.txt', self::TEST_FILE_ENCRYPTED);
        self::assertFileExists(self::TEST_FILE_ENCRYPTED);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(TEST_WORK_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileCrypt::encryptFile
     */
    public function testEncryptionFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        FileCrypt::encryptFile(self::TEST_FILE_PLAIN . 'invalid', self::TEST_KEY);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileCrypt::decryptFile
     */
    public function testDecryptionFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        FileCrypt::decryptFile(self::TEST_FILE_PLAIN . 'invalid', self::TEST_KEY);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileCrypt::encryptFile
     */
    public function testFileEncryption()
    {
        FileCrypt::encryptFile(self::TEST_FILE_PLAIN, self::TEST_KEY);
        self::assertFileExists(self::TEST_FILE_PLAIN);
        self::assertFileNotEquals(self::TEST_FILE_PLAIN, TEST_FIXTURES_FILE_1);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileCrypt::decryptFile
     */
    public function testFileDecryption()
    {
        FileCrypt::decryptFile(self::TEST_FILE_ENCRYPTED, self::TEST_KEY);
        self::assertFileExists(self::TEST_FILE_ENCRYPTED);
        self::assertFileEquals(self::TEST_FILE_PLAIN, self::TEST_FILE_ENCRYPTED);
    }
}
