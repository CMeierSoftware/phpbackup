<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Core\LogLevel;
use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Core\StepManager;
use CMS\PhpBackup\Core\SystemLocker;
use CMS\PhpBackup\Core\AppConfig;

abstract class AbstractRunner
{
    protected readonly FileLogger $logger;
    protected readonly AppConfig $config;
    protected array $steps = [];
    protected readonly StepManager $stepManager;

    /**
     * Creates a new BaseRunner instance.
     *
     * @param string $log_file The log file to use. Defaults to FileLogger::DEFAULT_LOG_FILE.
     *
     * @return void
     */
    public function __construct(AppConfig $config)
    {
        $this->config = $config;
        $this->logger = FileLogger::GetInstance($this->config->getTempDir() . 'debug.log', LogLevel::INFO, true);
        $this->setupSteps();
        $this->stepManager = new StepManager($this->steps, $this->config->getTempDir());
    }


    public function run(): void
    {
        try {
            $this->lockBackupDir();

            $result = $this->stepManager->executeNextStep();

            $this->logger->Info($result);
        } catch (\Exception $e) {
            $this->logger->Error($e->getMessage());
            throw new \Exception($e->getMessage());
        } finally {
            $this->unlockBackupDir();
        }

        $this->logger->Info("Backup done.");
    }

    /**
     * Locks the backup directory with the system locker.
     *
     * @return void
     */
    public function lockBackupDir(): void
    {
        $this->logger->Info("lock the system");
        SystemLocker::lock($this->config->getBackupDirectory()['src']);
    }

    /**
     * Unlocks the backup directory with the system locker.
     *
     * @return void
     */
    public function unlockBackupDir(): void
    {
        $this->logger->Info("unlock the system");
        SystemLocker::unlock($this->config->getBackupDirectory()['src']);
    }

    abstract protected function setupSteps();

}
