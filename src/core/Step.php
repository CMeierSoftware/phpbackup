<?php

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
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
     * @return mixed The result of the callback execution.
     * @throws \RuntimeException If the callback is not set.
     */
    public function execute(): mixed
    {
        if (!$this->callback) {
            throw new \RuntimeException('Callback is not set.');
        }

        return call_user_func_array($this->callback, $this->arguments);
    }
}
