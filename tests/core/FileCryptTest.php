<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

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
    private const TEST_WORK_DIR = ABS_PATH . 'tests\\work\\';
    private const TEST_FILE_PLAIN = self::TEST_WORK_DIR . 'file1.txt';
    private const TEST_FILE_ENCRYPTED = self::TEST_WORK_DIR . 'encrypted_file1.txt';

    protected function setUp(): void
    {
        FileHelper::makeDir(self::TEST_WORK_DIR);
        copy(ABS_PATH . 'tests\\fixtures\\zip\\file1.txt', self::TEST_FILE_PLAIN);
        copy(ABS_PATH . 'tests\\fixtures\\encryption\\encrypted_file1.txt', self::TEST_FILE_ENCRYPTED);

        self::assertFileExists(self::TEST_FILE_PLAIN);
        self::assertFileExists(self::TEST_FILE_ENCRYPTED);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_WORK_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Core\FileCrypt::decryptFile
     * @covers \CMS\PhpBackup\Core\FileCrypt::encryptFile
     */
    public function testFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        FileCrypt::encryptFile(self::TEST_FILE_PLAIN . 'invalid', self::TEST_KEY);
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
        self::assertFileNotEquals(self::TEST_FILE_PLAIN, ABS_PATH . 'tests\\fixtures\\zip\\file1.txt');
    }

    /** * @covers \CMS\PhpBackup\Core\FileCrypt::decryptFile
     *
     */
    public function testFileDecryption()
    {
        FileCrypt::decryptFile(self::TEST_FILE_ENCRYPTED, self::TEST_KEY);
        self::assertFileExists(self::TEST_FILE_ENCRYPTED);
        self::assertFileEquals(self::TEST_FILE_PLAIN, self::TEST_FILE_ENCRYPTED);
    }
}
