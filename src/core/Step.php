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

    public function __construct(int $delay = 0)
    {
        $this->delay = $delay;
    }

    /**
     * Set the callback for the step with optional arguments.
     *
     * @param callable $callback The callback function or [class, method] array.
     * @param array $arguments Optional arguments to be passed to the callback.
     * @throws \InvalidArgumentException If the callback is not callable or the function does not exist.
     */
    public function setCallback(callable $callback, array $arguments = []): void
    {
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
