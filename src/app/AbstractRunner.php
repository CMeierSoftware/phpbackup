<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;

use CMS\PhpBackup\Step\StepConfig;
use CMS\PhpBackup\Step\StepFactory;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Core\LogLevel;
use CMS\PhpBackup\Core\StepManager;
use CMS\PhpBackup\Core\SystemLocker;

/**
 * Class AbstractRunner.
 *
 * Represents an abstract runner for executing backup steps.
 */
abstract class AbstractRunner
{
    /**
     * @var FileLogger the logger for recording backup activities
     *
     * @readonly
     */
    protected readonly FileLogger $logger;

    /**
     * @var AppConfig the application configuration
     *
     * @readonly
     */
    protected readonly AppConfig $config;

    /**
     * @var array an array of steps to be executed during the backup process
     */
    protected array $steps = [];

    /**
     * @var StepManager the step manager for coordinating the execution of backup steps
     *
     * @readonly
     */
    protected readonly StepManager $stepManager;

    /**
     * AbstractRunner constructor.
     */
    public function __construct()
    {
        $this->config = AppConfig::loadAppConfig();
        $this->configureLogger();

        $this->logger->info('Run App "' . $this->config->getAppName() . '"');

        $this->steps = $this->setupSteps();
        $this->stepManager = new StepManager($this->steps);
    }

    /**
     * Destructor to unlock the backup directory when the runner is destroyed.
     */
    public function __destruct()
    {
        $this->unlockBackupDir();
    }

    /**
     * Executes the next backup step.
     *
     * @return bool true if a step was executed, false if there is no next step
     *
     * @throws \Exception if an error occurs during the backup process
     */
    public function run(): bool
    {
        try {
            $this->lockBackupDir();
            $result = $this->stepManager->executeNextStep();

            $stepExecuted = (null !== $result);

            $this->logger->info($stepExecuted ? (string) $result : 'No next step.');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw $e;
        } finally {
            $this->logger->debug('Step done.');
        }

        return $stepExecuted;
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

    protected function getRemoteStepsFor(string $class, int $delay = 0): array
    {
        $remoteHandler = $this->config->getDefinedRemoteHandler();
        $remoteHandler = StepFactory::getRemoteClasses($remoteHandler);

        return array_map(
            static fn (string $handler): StepConfig => new StepConfig($class, $delay, $handler),
            $remoteHandler
        );
    }

    /**
     * Sets up and returns the array of backup steps to be executed.
     *
     * @return array the array of backup steps
     */
    abstract protected function setupSteps(): array;

    /**
     * Configures the logger with default settings.
     */
    private function configureLogger(): void
    {
        $this->logger = FileLogger::getInstance();
        $this->logger->setLogFile($this->config->getTempDir() . 'debug.log');
        $this->logger->setLogLevel(LogLevel::INFO);
        $this->logger->activateEchoLogs();
    }
}
