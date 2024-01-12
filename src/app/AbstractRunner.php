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
/**
 * Class AbstractRunner
 *
 * Represents an abstract runner for executing backup steps.
 *
 * @abstract
 */
abstract class AbstractRunner
{
    /**
     * @var FileLogger $logger The logger for recording backup activities.
     * @readonly
     */
    protected readonly FileLogger $logger;

    /**
     * @var AppConfig $config The application configuration.
     * @readonly
     */
    protected readonly AppConfig $config;

    /**
     * @var array $steps An array of steps to be executed during the backup process.
     */
    protected array $steps = [];

    /**
     * @var StepManager $stepManager The step manager for coordinating the execution of backup steps.
     * @readonly
     */
    protected readonly StepManager $stepManager;

    /**
     * AbstractRunner constructor.
     *
     * @param AppConfig $config The application configuration.
     */
    public function __construct(AppConfig $config)
    {
        $this->config = $config;
        $this->logger = FileLogger::GetInstance();
        $this->configureLogger();
        $this->steps = $this->setupSteps();
        $this->stepManager = new StepManager($this->steps, $this->config);
    }

    /**
     * Destructor to unlock the backup directory when the runner is destroyed.
     */
    public function __destruct()
    {
        $this->unlockBackupDir();
    }

    /**
     * Configures the logger with default settings.
     */
    protected function configureLogger(): void
    {
        $this->logger->setLogFile($this->config->getTempDir() . 'debug.log');
        $this->logger->setLogLevel(LogLevel::INFO);
        $this->logger->activateEchoLogs();
    }

    /**
     * Runs the backup process.
     *
     * @throws \Exception If an error occurs during the backup process.
     */
    public function run(): void
    {
        try {
            $this->lockBackupDir();
            $result = $this->stepManager->executeNextStep();
            $this->logger->Info((string) $result);
        } catch (\Exception $e) {
            $this->logger->Error($e->getMessage());
            throw $e;
        } finally {
            $this->logger->Info('Backup done.');
        }
    }

    /**
     * Locks the backup directory with the system locker.
     */
    public function lockBackupDir(): void
    {
        SystemLocker::lock($this->config->getTempDir());
    }

    /**
     * Unlocks the backup directory with the system locker if it is locked.
     */
    public function unlockBackupDir(): void
    {
        if (SystemLocker::isLocked($this->config->getTempDir())) {
            SystemLocker::unlock($this->config->getTempDir());
        }
    }

    /**
     * Sets up and returns the array of backup steps to be executed.
     *
     * @return array The array of backup steps.
     */
    abstract protected function setupSteps(): array;
}
