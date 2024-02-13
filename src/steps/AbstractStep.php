<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Helper\FileLogger;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

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
    protected array $data;
    protected readonly ?AbstractRemoteHandler $remote;
    private float $lastExeTs;
    private float $maxElapsedTime = 0;
    private readonly float $timeoutTs;

    /**
     * AbstractStep constructor.
     */
    public function __construct(?AbstractRemoteHandler $remoteHandler)
    {
        $this->logger = FileLogger::getInstance();
        $this->config = AppConfig::loadAppConfig();
        $this->remote = $remoteHandler;

        $this->lastExeTs = microtime(true);
        $this->timeoutTs = 0 === (int) ini_get('max_execution_time') ? 0 : microtime(true) + ini_get('max_execution_time');
    }

    public function setData(array &$data): void
    {
        $this->data = &$data;
    }

    /**
     * Execute the callback and return the result.
     *
     * @return StepResult the result of the callback execution
     */
    public function execute(): StepResult
    {
        $this->logger->info('Execute ' . $this->classDetails());

        $this->validateData();
        $this->sanitizeData();

        if (null !== $this->remote) {
            $this->remote->connect();
        }

        return $this->_execute();
    }

    /**
     * Checks if it's close to reaching the timeout. Close means 150% of an elapsed time of the longest iteration.
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
        $isClose = $this->timeoutTs <= $currentTime + 1.5 * $this->maxElapsedTime;

        $this->logger->debug(sprintf(
            'Last time check: %.2f --> elapsed time: %.2f --> Timeout: %.2f --> is close: %d',
            $this->lastExeTs,
            $this->maxElapsedTime,
            $this->timeoutTs,
            (int) $isClose
        ));
        $this->lastExeTs = $currentTime;

        return $isClose;
    }

    protected function classDetails(): string
    {
        return $this::class . (null === $this->remote) ? '' : ' Remote: ' . $this->remote::class;
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
    abstract protected function getRequiredDataKeys(): array;

    abstract protected function sanitizeData(): void;

    /**
     * Increment the attempts count in the watchdog data.
     */
    protected function incrementAttemptsCount()
    {
        $attempts = $this->getAttemptCount();
        $this->updateWatchdog(['attempts' => ++$attempts, 'last_attempt_time' => time()]);

        return $attempts;
    }

    /**
     * Reset the attempts count in the watchdog data.
     */
    protected function resetAttemptsCount()
    {
        $this->updateWatchdog(['attempts' => 0, 'last_attempt_time' => null]);
    }

    private function getAttemptCount(): int
    {
        try {
            $watchdogData = $this->config->readTempData('send_remote_watchdog');
        } catch (FileNotFoundException) {
            $watchdogData = [];
        }

        return (int) ($watchdogData['attempts'] ?? 0);
    }

    private function validateData(): bool
    {
        if (!isset($this->data)) {
            throw new \InvalidArgumentException('Data must be set before executing step.');
        }

        $requiredKeys = $this->getRequiredDataKeys();

        $missingKeys = array_diff($requiredKeys, array_keys($this->data));

        if (!empty($missingKeys)) {
            throw new \InvalidArgumentException('Missing required keys: ' . implode(', ', $missingKeys));
        }

        return true;
    }

    private function updateWatchdog($data)
    {
        $this->config->saveTempData('send_remote_watchdog', $data);
    }
}
