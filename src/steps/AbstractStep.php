<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\FileLogger;

if (!defined('ABS_PATH')) {
    return;
}

abstract class AbstractStep
{
    public readonly int $delay;
    protected readonly array $arguments;
    protected FileLogger $logger;

    /**
     * Set the callback for the step with optional arguments.
     *
     * @param array $arguments optional arguments to be passed to the callback
     * @param int $delay delay between this and the previous step
     */
    public function __construct(array $arguments = [], int $delay = 0)
    {
        $this->delay = $delay;
        $this->arguments = $arguments;
        $this->logger = FileLogger::getInstance();
    }

    public function __serialize(): array
    {
        return [$this->delay, $this->arguments];
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

        return $this->_execute();
    }

    abstract protected function _execute(): StepResult;
}
