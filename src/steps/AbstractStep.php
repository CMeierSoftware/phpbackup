<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Exceptions\FileNotFoundException;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Abstract base class for implementing steps in a process.
 */
abstract class AbstractStep
{
    protected const MAX_ATTEMPTS = 3;
    protected readonly FileLogger $logger;
    protected readonly AppConfig $config;
    protected array $stepData = [];

    /**
     * AbstractStep constructor.
     *
     * @param AppConfig $config configuration for this step
     */
    public function __construct(AppConfig $config)
    {
        $this->logger = FileLogger::getInstance();
        $this->config = $config;

        try {
            $this->stepData = $this->config->readTempData('StepData');
        } catch (FileNotFoundException) {
            $this->logger->info('No StepData found. Starting empty.');
        }
    }

    /**
     * Execute the callback and return the result.
     *
     * @return StepResult the result of the callback execution
     */
    public function execute(): StepResult
    {
        $class = self::class;
        $this->logger->info("Execute {$class}");

        $this->validateStepData();

        $result = $this->_execute();

        $this->config->saveTempData('StepData', $this->stepData);

        return $result;
    }

    /**
     * Abstract method to be implemented by child classes.
     *
     * @return StepResult the result of the callback execution
     */
    abstract protected function _execute(): StepResult;

    /**
     * Get the keys required in the step data array.
     *
     * @return array list of required step data keys
     */
    abstract protected function getRequiredStepDataKeys(): array;

    /**
     * Increment the attempts count in the watchdog data.
     */
    protected function incrementAttemptsCount()
    {
        $attempts = $this->getAttemptCount();
        $this->updateWatchdog(['attempts' => ++$attempts, 'last_attempt_time' => time()]);
    }

    /**
     * Reset the attempts count in the watchdog data.
     */
    protected function resetAttemptsCount()
    {
        $this->updateWatchdog(['attempts' => 0, 'last_attempt_time' => null]);
    }

    protected function getAttemptCount(): int
    {
        try {
            $watchdogData = $this->config->readTempData('send_remote_watchdog');
        } catch (FileNotFoundException) {
            $watchdogData = [];
        }

        return isset($watchdogData['attempts']) ? (int) $watchdogData['attempts'] : 0;
    }

    private function validateStepData()
    {
        $requiredKeys = $this->getRequiredStepDataKeys();

        $missingKeys = array_diff($requiredKeys, array_keys($this->stepData));

        if (!empty($missingKeys)) {
            throw new \InvalidArgumentException('Missing required keys: ' . implode(', ', $missingKeys));
        }
    }

    private function updateWatchdog($data)
    {
        $this->config->saveTempData('send_remote_watchdog', $data);
    }
}
