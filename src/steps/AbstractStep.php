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
    private float $lastExeTs;
    private float $maxElapsedTime = 0;
    private readonly float $timeoutTs;

    /**
     * AbstractStep constructor.
     */
    public function __construct()
    {
        $this->logger = FileLogger::getInstance();
        $this->config = AppConfig::loadAppConfig();

        try {
            $this->stepData = $this->config->readTempData('StepData');
        } catch (FileNotFoundException) {
            $this->logger->info('No StepData found. Starting empty.');
        }

        $this->lastExeTs = microtime(true);
        $this->timeoutTs = 0 === (int) ini_get('max_execution_time') ? 0 : microtime(true) + ini_get('max_execution_time');
    }

    /**
     * Execute the callback and return the result.
     *
     * @return StepResult the result of the callback execution
     */
    public function execute(): StepResult
    {
        $class = $this::class;
        $this->logger->info("Execute {$class}");

        $this->validateStepData();

        $result = $this->_execute();

        $this->config->saveTempData('StepData', $this->stepData);

        return $result;
    }

    /**
     * Checks if it's close to reaching the timeout. Close means 150% of a elapsed time of the longest iteration.
     * One iteration is measured between two calls of this function.
     *
     * @return bool true if it's close to timeout, false otherwise
     */
    public function isTimeoutClose(): bool
    {
        if (0 === $this->timeoutTs) {
            return false;
        }

        $currentTime = microtime(true);

        $this->maxElapsedTime = max($this->maxElapsedTime, $currentTime - $this->lastExeTs);

        $this->lastExeTs = $currentTime;

        return $this->timeoutTs <= $currentTime + 1.5 * $this->maxElapsedTime;
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

        return (int) ($watchdogData['attempts'] ?? 0);
    }

    abstract protected function getRequiredDataKeys(): array;

    private function validateStepData()
    {
        $requiredKeys = $this->getRequiredDataKeys();

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
