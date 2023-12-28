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
    private readonly array $arguments;
    private FileLogger $logger;

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

    public function __toString(): string
    {
        if (is_array($this->callback)) {
            [$class, $method] = $this->callback;
            $cls = is_object($class) ? $class::class : $class;

            return "Callable: [{$cls}, {$method}]";
        }
        if (is_object($this->callback)) {
            return 'Callable: ' . get_class($this->callback);
        }
        if (is_string($this->callback)) {
            return "Callable: {$this->callback}";
        }

        throw new \UnexpectedValueException('Unsupported callable type');
    }

    public function __serialize(): array
    {
        return [$this->delay, (string) $this, $this->arguments];
    }

    /**
     * Execute the callback and return the result.
     *
     * @return StepResult the result of the callback execution
     */
    public function execute(): StepResult
    {
        $this->logger->info("Execute {$this}");

        return $this->_execute();
    }

    abstract protected function _execute(): StepResult;
}
