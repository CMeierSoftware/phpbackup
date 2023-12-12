<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests;

use CMS\PhpBackup\Core\FileCrypt;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

class FileCryptTest extends TestCase
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

        $this->assertFileExists(self::TEST_FILE_PLAIN);
        $this->assertFileExists(self::TEST_FILE_ENCRYPTED);
    }

    public function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_WORK_DIR);
    }

    /**
     * @covers FileCrypt::encryptFile
     * @covers FileCrypt::decryptFile
     */
    public function testFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        FileCrypt::encryptFile(self::TEST_FILE_PLAIN . 'invalid', self::TEST_KEY);
        $this->expectException(FileNotFoundException::class);
        FileCrypt::decryptFile(self::TEST_FILE_PLAIN . 'invalid', self::TEST_KEY);
    }

    /**
     * @covers FileCrypt::encryptFile
     */
    public function testFileEncryption()
    {
        FileCrypt::encryptFile(self::TEST_FILE_PLAIN, self::TEST_KEY);
        $this->assertFileExists(self::TEST_FILE_PLAIN);
        $this->assertFileNotEquals(self::TEST_FILE_PLAIN, ABS_PATH . 'tests\\fixtures\\zip\\file1.txt');
    }

    /**
     * @covers FileCrypt::decryptFile
     */
    public function testFileDecryption()
    {
        FileCrypt::decryptFile(self::TEST_FILE_ENCRYPTED, self::TEST_KEY);
        $this->assertFileExists(self::TEST_FILE_ENCRYPTED);
        $this->assertFileEquals(self::TEST_FILE_PLAIN, self::TEST_FILE_ENCRYPTED);
    }
}
