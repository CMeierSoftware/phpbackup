<?php

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

final class StepResult
{
    public $returnValue = null;
    public bool $repeat = false;
    public function __construct(mixed $returnValue, bool $repeat = false)
    {
        $this->returnValue = $returnValue;
        $this->repeat = $repeat;
    }
}

class Step
{
    public readonly int $delay;
    private $callback;
    private readonly array $arguments;

    /**
     * Set the callback for the step with optional arguments.
     *
     * @param callable $callback The callback function or [class, method] array.
     * @param array $arguments Optional arguments to be passed to the callback.
     * @param int $delay Delay between this and the previous step.
     */
    public function __construct(callable $callback, array $arguments = [], int $delay = 0)
    {
        $this->delay = $delay;
        $this->callback = $callback;
        $this->arguments = $arguments;
    }

    /**
     * Execute the callback and return the result.
     *
     * @return StepResult The result of the callback execution.
     * @throws \RuntimeException If the callback is not set.
     */
    public function execute(): StepResult
    {
        if (!$this->callback) {
            throw new \RuntimeException('Callback is not set.');
        }

        $result = call_user_func_array($this->callback, $this->arguments);

        if (!($result instanceof  StepResult)) {
            throw new \RuntimeException("the step result is not of type " . StepResult::class);
        }

        return $result;
    }
}
