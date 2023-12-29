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
    protected FileLogger $logger;

    /**
     * Set the callback for the step with optional arguments.
     *
     * @param int $delay delay between this and the previous step
     */
    public function __construct(int $delay = 0)
    {
        $this->logger = FileLogger::getInstance();
        $this->delay = $delay;
    }
    
    public function __serialize(): array
    {
        return [$this->delay];
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
