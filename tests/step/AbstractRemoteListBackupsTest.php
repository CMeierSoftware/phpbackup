<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Steps;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\Remote\AbstractRemoteListBackups;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Step\DeleteOldFilesRemoteStep
 */
final class AbstractRemoteListBackupsTest extends TestCaseWithAppConfig
{
    private const WORK_DIR_REMOTE_BASE = self::TEST_DIR . 'Remote' . DIRECTORY_SEPARATOR;
    private Local $remoteHandler;
    private array $backupFolder = [];

    protected function setUp(): void
    {
        FileHelper::makeDir(self::WORK_DIR_REMOTE_BASE);
        self::assertDirectoryExists(self::WORK_DIR_REMOTE_BASE);

        for ($i = 5; $i < 0; ++$i) {
            $ts = 'backup_' . (new \DateTime())->modify("-{$i} days")->format('Y-m-d_H-i-s') . DIRECTORY_SEPARATOR;
            FileHelper::makeDir(self::WORK_DIR_REMOTE_BASE . $ts);
            self::assertDirectoryExists(self::WORK_DIR_REMOTE_BASE . $ts);
            $this->backupFolder[] = $ts;
        }

        $this->remoteHandler = new Local(self::WORK_DIR_REMOTE_BASE);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testStep()
    {
        $this->setUpAppConfig('config_full_valid', []);
        $mockBuilder = $this->getMockBuilder(AbstractRemoteListBackups::class);
        $mockBuilder->setConstructorArgs([$this->remoteHandler, $this->config]);

        $sendRemoteStep = $mockBuilder->getMockForAbstractClass();

        $result = $sendRemoteStep->execute();
        self::assertFalse($result->repeat);
        self::assertSame($this->backupFolder, $result->returnValue);
    }
}