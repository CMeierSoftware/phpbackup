<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Remote;

use CMS\PhpBackup\Exceptions\FileAlreadyExistsException;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Exceptions\RemoteStorageNotConnectedException;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler
 */
final class AbstractRemoteHandlerTest extends TestCase
{
    private const WORK_DIR_LOCAL = TEST_WORK_DIR . 'Local' . DIRECTORY_SEPARATOR;
    private const WORK_DIR_REMOTE = TEST_WORK_DIR . 'Remote' . DIRECTORY_SEPARATOR;
    private const TEST_FILE1_SRC = TEST_FIXTURES_FILE_1;
    private const TEST_FILE2_SRC = TEST_FIXTURES_FILE_2;

    private MockObject $mockedHandler;

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_LOCAL);
        self::assertDirectoryExists(self::WORK_DIR_LOCAL);

        FileHelper::makeDir(self::WORK_DIR_REMOTE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE);

        $this->mockedHandler = $this->getMockedHandler();
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::WORK_DIR_LOCAL);
        FileHelper::deleteDirectory(self::WORK_DIR_REMOTE);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::isConnected()
     */
    public function testIsConnected()
    {
        $handler = $this->getMockedHandler(false);
        self::assertFalse($handler->isConnected());

        $handler = $this->getMockedHandler(true);
        self::assertTrue($handler->isConnected());
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirCreate()
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirDelete()
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirList()
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileDelete()
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileDownload()
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileExists()
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileUpload()
     *
     * @uses \CMS\PhpBackup\Remote\AbstractRemoteHandler::isConnected()
     *
     * @dataProvider provideExceptionOnMissingConnectionCases
     */
    public function testExceptionOnMissingConnection(array $data)
    {
        $handler = $this->getMockedHandler(false);
        self::assertFalse($handler->isConnected());

        self::expectException(RemoteStorageNotConnectedException::class);
        $handler->{$data['function']}(...$data['args']);
    }

    public static function provideExceptionOnMissingConnectionCases(): iterable
    {
        return [
            [['function' => 'fileUpload', 'args' => ['', '']]],
            [['function' => 'fileDownload', 'args' => ['', '']]],
            [['function' => 'fileDelete', 'args' => ['']]],
            [['function' => 'fileExists', 'args' => ['']]],
            [['function' => 'dirCreate', 'args' => ['']]],
            [['function' => 'dirList', 'args' => ['', false]]],
            [['function' => 'dirDelete', 'args' => ['']]],
        ];
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileUpload()
     */
    public function testFileUploadSuccess()
    {
        $srcFile = self::TEST_FILE1_SRC;
        $destFile = 'file.txt';

        // two times _fileExists: 1. check if file exists, 2. check if dir exists
        $this->mockedHandler->expects(self::any())->method('_fileExists')->willReturn(false);
        $this->mockedHandler->expects(self::any())->method('_dirCreate')->willReturn(true);
        $this->mockedHandler->expects(self::exactly(1))->method('_fileUpload')->willReturn(true);

        self::assertTrue($this->mockedHandler->fileUpload($srcFile, $destFile));
        self::assertMockedCache([$destFile => true, '.' => true]);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileUpload()
     */
    public function testFileUploadSrcFileNotFound()
    {
        $srcFile = self::TEST_FILE1_SRC . 'invalid';
        $destFile = 'file.txt';
        self::assertFileDoesNotExist($srcFile);

        $this->expectException(FileNotFoundException::class);
        self::expectExceptionMessage("The file '{$srcFile}' was not found in local storage.");
        $this->mockedHandler->fileUpload($srcFile, $destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileUpload()
     */
    public function testFileUploadDestFileAlreadyExists()
    {
        $destFile = 'file.txt';

        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(true);

        $this->expectException(FileAlreadyExistsException::class);
        $this->mockedHandler->fileUpload(self::TEST_FILE2_SRC, $destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileUpload()
     */
    public function testFileUploadCanNotCreateDir()
    {
        $destFile = 'file.txt';

        // two times _fileExists: 1. check if file exists, 2. check if dir exists
        $this->mockedHandler->expects(self::exactly(2))->method('_fileExists')->willReturn(false);
        $this->mockedHandler->expects(self::exactly(1))->method('_dirCreate')->willReturn(false);

        $this->expectException(FileNotFoundException::class);
        self::expectExceptionMessage("Can not create directory for '{$destFile}' in remote storage.");
        $this->mockedHandler->fileUpload(self::TEST_FILE2_SRC, $destFile);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileDelete()
     */
    public function testFileDeleteSuccess()
    {
        $destFile = 'file.txt';

        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(true);
        $this->mockedHandler->expects(self::exactly(1))->method('_fileDelete')->willReturn(true);

        self::assertTrue($this->mockedHandler->fileDelete($destFile));
        self::assertMockedCache([$destFile => false]);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileDelete()
     */
    public function testFileDeleteFileNotFound()
    {
        $file = 'file.txt';

        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(false);

        $this->expectException(FileNotFoundException::class);
        $this->mockedHandler->fileDelete($file . 'invalid');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileDownload()
     */
    public function testFileDownloadSuccess()
    {
        $file = 'file.txt';

        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(true);
        $this->mockedHandler->expects(self::exactly(1))->method('_fileDownload')->willReturn(true);

        self::assertTrue($this->mockedHandler->fileDownload(self::WORK_DIR_LOCAL . $file, $file));
        self::assertMockedCache([$file => true]);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileDownload()
     */
    public function testFileDownloadSrcFileNotFound()
    {
        $file = 'file.txt';

        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(false);

        $this->expectException(FileNotFoundException::class);
        $this->mockedHandler->fileDownload(self::WORK_DIR_LOCAL . $file, $file . 'invalid');
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileDownload()
     */
    public function testFileDownloadDestFileAlreadyExists()
    {
        $file = 'file.txt';

        copy(self::TEST_FILE1_SRC, self::WORK_DIR_LOCAL . $file);
        self::assertFileExists(self::WORK_DIR_LOCAL . $file);

        $this->expectException(FileAlreadyExistsException::class);
        $this->mockedHandler->fileDownload(self::WORK_DIR_LOCAL . $file, $file);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::fileExists()
     */
    public function testFileExists()
    {
        $files = ['test', 'bar\foo.txt', 'six/seven', 'one/two.txt'];
        $expectedCache = [
            'test' => false,
            'bar\foo.txt' => false,
            'six/seven' => false,
            'one/two.txt' => false,
        ];

        $this->mockedHandler->expects(self::exactly(count($files)))->method('_fileExists')->willReturn(false);

        // first iteration for initial check / fill cache
        foreach ($files as $file) {
            self::assertFalse($this->mockedHandler->fileExists($file));
        }
        self::assertMockedCache($expectedCache);

        // second iteration check results and _fileExists in not called
        foreach ($files as $file) {
            self::assertFalse($this->mockedHandler->fileExists($file));
        }

        self::assertMockedCache($expectedCache);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirList()
     */
    public function testDirectoryListSuccess()
    {
        $files = ['test', 'bar', '.', '..', 'foo.txt', 'six.xml', 'two.txt'];
        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(true);
        $this->mockedHandler->expects(self::exactly(1))->method('_dirList')->willReturn($files);

        $expectation = [
            'test',
            'bar',
            'foo.txt',
            'six.xml',
            'two.txt'];

        $expectationCache = [
            self::WORK_DIR_LOCAL => true,
            self::WORK_DIR_LOCAL . 'test' => true,
            self::WORK_DIR_LOCAL . 'bar' => true,
            self::WORK_DIR_LOCAL . 'foo.txt' => true,
            self::WORK_DIR_LOCAL . 'six.xml' => true,
            self::WORK_DIR_LOCAL . 'two.txt' => true,
        ];

        $result = $this->mockedHandler->dirList(self::WORK_DIR_LOCAL);
        self::assertMockedCache($expectationCache);
        self::assertSame($expectation, $result);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirList()
     */
    public function testDirectoryListOnlyFiles()
    {
        $files = ['test', 'bar', '.', '..', 'foo.txt', 'six.xml', 'two.txt'];
        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(true);
        $this->mockedHandler->expects(self::exactly(1))->method('_dirList')->willReturn($files);

        $expectation = [
            'foo.txt',
            'six.xml',
            'two.txt'];

        $expectationCache = [
            self::WORK_DIR_LOCAL => true,
            self::WORK_DIR_LOCAL . 'test' => true,
            self::WORK_DIR_LOCAL . 'bar' => true,
            self::WORK_DIR_LOCAL . 'foo.txt' => true,
            self::WORK_DIR_LOCAL . 'six.xml' => true,
            self::WORK_DIR_LOCAL . 'two.txt' => true,
        ];

        $result = $this->mockedHandler->dirList(self::WORK_DIR_LOCAL, true);
        self::assertMockedCache($expectationCache);
        self::assertSame($expectation, $result);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirCreate()
     */
    public function testDirectoryCreateSuccess()
    {
        $dirs = ['test', 'bar\foo.txt', 'six/seven', 'one/two.txt'];
        $this->mockedHandler->expects(self::exactly(count($dirs)))->method('_fileExists')->willReturn(false);
        $this->mockedHandler->expects(self::exactly(count($dirs)))->method('_dirCreate')->willReturn(true);

        foreach ($dirs as $dir) {
            self::assertTrue($this->mockedHandler->dirCreate(self::WORK_DIR_LOCAL . $dir, $dir));
        }

        self::assertMockedCache([
            self::WORK_DIR_LOCAL . 'test' => true,
            self::WORK_DIR_LOCAL . 'bar' => true,
            self::WORK_DIR_LOCAL . 'six/seven' => true,
            self::WORK_DIR_LOCAL . 'one' => true,
        ]);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirCreate()
     */
    public function testDirectoryCreateDirAlreadyExists()
    {
        $dirs = ['test', 'bar\foo.txt', 'six/seven', 'one/two.txt'];
        $this->mockedHandler->expects(self::exactly(count($dirs)))->method('_fileExists')->willReturn(true);
        $this->mockedHandler->expects(self::never())->method('_dirCreate')->willReturn(true);

        foreach ($dirs as $dir) {
            self::assertTrue($this->mockedHandler->dirCreate(self::WORK_DIR_LOCAL . $dir, $dir));
        }

        self::assertMockedCache([
            self::WORK_DIR_LOCAL . 'test' => true,
            self::WORK_DIR_LOCAL . 'bar' => true,
            self::WORK_DIR_LOCAL . 'six/seven' => true,
            self::WORK_DIR_LOCAL . 'one' => true,
        ]);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirDelete()
     */
    public function testDirectoryDeleteSuccess()
    {
        $dirs = [
            self::WORK_DIR_LOCAL . 'test\foo.txt' => true,
            self::WORK_DIR_LOCAL . 'test/seven' => true,
            self::WORK_DIR_LOCAL . 'test/seven/two.txt' => true,
        ];

        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(true);
        $this->mockedHandler->expects(self::exactly(1))->method('_dirDelete')->willReturn(true);

        $property = new \ReflectionProperty($this->mockedHandler, 'fileExistsCache');
        $property->setAccessible(true);
        $property->setValue($this->mockedHandler, $dirs);

        self::assertTrue($this->mockedHandler->dirDelete(self::WORK_DIR_LOCAL . 'test'));

        self::assertMockedCache([
            self::WORK_DIR_LOCAL . 'test\foo.txt' => false,
            self::WORK_DIR_LOCAL . 'test/seven' => false,
            self::WORK_DIR_LOCAL . 'test/seven/two.txt' => false,
            self::WORK_DIR_LOCAL . 'test' => false,
        ]);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::dirDelete()
     */
    public function testDirectoryDeleteFileNotFound()
    {
        $dirs = [
            self::WORK_DIR_LOCAL . 'test\foo.txt' => true,
            self::WORK_DIR_LOCAL . 'test/seven' => true,
            self::WORK_DIR_LOCAL . 'test/seven/two.txt' => true,
        ];

        $this->mockedHandler->expects(self::exactly(1))->method('_fileExists')->willReturn(false);
        $this->mockedHandler->expects(self::never())->method('_dirDelete');

        $property = new \ReflectionProperty($this->mockedHandler, 'fileExistsCache');
        $property->setAccessible(true);
        $property->setValue($this->mockedHandler, $dirs);

        self::assertFalse($this->mockedHandler->dirDelete(self::WORK_DIR_LOCAL . 'test'));

        self::assertMockedCache([
            self::WORK_DIR_LOCAL . 'test\foo.txt' => false,
            self::WORK_DIR_LOCAL . 'test/seven' => false,
            self::WORK_DIR_LOCAL . 'test/seven/two.txt' => false,
            self::WORK_DIR_LOCAL . 'test' => false,
        ]);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::deleteOld()
     */
    public function testDeleteOldAgeSuccess()
    {
        $days = 7;

        list($expiredDirs, $validDirs) = self::mockDirList($days);

        $this->mockedHandler->expects(self::exactly(1))
            ->method('_dirList')
            ->willReturn(array_merge($expiredDirs, $validDirs))
        ;

        // + 1 because dirList use it as well
        $this->mockedHandler->expects(self::exactly(count($expiredDirs) + 1))
            ->method('_fileExists')
            ->willReturn(true)
        ;

        $this->mockedHandler->expects(self::exactly(count($expiredDirs)))
            ->method('_dirDelete')
            ->willReturnCallback(
                static fn ($remotePath) => in_array(ltrim($remotePath, '/'), $expiredDirs, true) ? true : self::fail("Function wants to delete validDir {$remotePath} " . json_encode($expiredDirs))
            )
        ;

        $deletedCount = $this->mockedHandler->deleteOld('', $days, 0);

        // Assert that the return value is an integer
        self::assertSame(count($expiredDirs), $deletedCount);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::deleteOld()
     */
    public function testDeleteOldCountSuccess()
    {
        $countToKeep = 7;

        list($expiredDirs, $validDirs) = self::mockDirList($countToKeep);
        $allDirs = array_merge($expiredDirs, $validDirs);

        $this->mockedHandler->expects(self::exactly(1))
            ->method('_dirList')
            ->willReturn($allDirs)
        ;

        // + 1 because dirList use it as well
        $this->mockedHandler->expects(self::exactly(count($allDirs) - $countToKeep + 1))
            ->method('_fileExists')
            ->willReturn(true)
        ;

        $this->mockedHandler->expects(self::exactly(count($allDirs) - $countToKeep))
            ->method('_dirDelete')
            ->willReturnCallback(
                static fn ($remotePath) => in_array(ltrim($remotePath, '/'), $expiredDirs, true) ? true : self::fail("Function wants to delete validDir {$remotePath} " . json_encode($expiredDirs))
            )
        ;

        $deletedCount = $this->mockedHandler->deleteOld('', 0, $countToKeep);

        // Assert that the return value is an integer
        self::assertSame(count($allDirs) - $countToKeep, $deletedCount);
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::deleteOld()
     */
    public function testDeleteOldWithZeroParameters()
    {
        self::assertSame(0, $this->mockedHandler->deleteOld('', 0, 0));
    }

    /**
     * @covers \CMS\PhpBackup\Remote\AbstractRemoteHandler::clearCache()
     */
    public function testClearCache()
    {
        $handler = $this->getMockedHandler();

        // Assume some entries in the cache
        $property = new \ReflectionProperty($handler, 'fileExistsCache');
        $property->setAccessible(true);
        $property->setValue($handler, ['/remote/file1.txt' => true]);
        self::assertNotEmpty($property->getValue($handler));

        // Clear the cache
        $handler->clearCache();

        self::assertMockedCache([]);
    }

    public function assertMockedCache(array $excepted)
    {
        $property = new \ReflectionProperty($this->mockedHandler,'fileExistsCache');
        $property->setAccessible(true);
        self::assertSame($excepted, $property->getValue($this->mockedHandler));
    }

    private function getMockedHandler(bool $connect = true): AbstractRemoteHandler
    {
        // Create a partial mock of AbstractRemoteHandler
        $mockBuilder = $this->getMockBuilder(AbstractRemoteHandler::class);
        $mockBuilder->onlyMethods([
            '_fileUpload', '_fileDownload', '_fileExists', '_fileDelete',
            '_dirCreate', '_dirList', '_dirDelete',
            'connect', 'disconnect',
        ]);

        $handler = $mockBuilder->getMockForAbstractClass();

        if ($connect) {
            $property = new \ReflectionProperty($handler, 'connection');
            $property->setAccessible(true);
            $property->setValue($handler, true);
        }

        $handler->expects(self::any())->method('connect')->willReturn(true);

        return $handler;
    }

    private static function mockDirList(int $numberOfDays): array
    {
        $expiredFiles = [];
        $validFiles = [];

        for ($i = 0; $i < 2 * $numberOfDays; ++$i) {
            $timestamp = strtotime("-{$i} days") - 120;
            if ($i < $numberOfDays) {
                $validFiles[] = 'backup_' . date('Y-m-d_H-i-s', $timestamp);
            } else {
                $expiredFiles[] = 'backup_' . date('Y-m-d_H-i-s', $timestamp);
            }
        }

        return [$expiredFiles, $validFiles];
    }
}
