<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Core\LogLevel;
use CMS\PhpBackup\Core\StepManager;
use CMS\PhpBackup\Core\SystemLocker;

abstract class AbstractRunner
{
    protected readonly FileLogger $logger;
    protected readonly AppConfig $config;
    protected array $steps = [];
    protected readonly StepManager $stepManager;

    /**
     * Creates a new BaseRunner instance.
     */
    public function __construct(AppConfig $config)
    {
        $this->config = $config;
        $this->logger = FileLogger::GetInstance();
        $this->logger->setLogFile($this->config->getTempDir() . 'debug.log');
        $this->logger->setLogLevel(LogLevel::INFO);
        $this->logger->activateEchoLogs();
        $this->steps = $this->setupSteps();
        $this->stepManager = new StepManager($this->steps, $this->config);
    }

    public function __destruct()
    {
        $this->unlockBackupDir();
    }

    public function run(): void
    {
        try {
            $this->lockBackupDir();

            $result = $this->stepManager->executeNextStep();

            $this->logger->Info((string) $result);
        } catch (\Exception $e) {
            $this->logger->Error($e->getMessage());

            throw $e;
        }

        $this->logger->Info('Backup done.');
    }

    /**
     * Locks the backup directory with the system locker.
     */
    public function lockBackupDir(): void
    {
        SystemLocker::lock($this->config->getTempDir());
    }

    /**
     * Unlocks the backup directory with the system locker.
     */
    public function unlockBackupDir(): void
    {
        if (SystemLocker::isLocked($this->config->getTempDir())) {
            SystemLocker::unlock($this->config->getTempDir());
        }
    }

    abstract protected function setupSteps(): array;
}
