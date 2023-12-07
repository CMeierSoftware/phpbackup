<?php

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

class Step
{
    public int $delay = 0;
    private $callback;
    private $arguments = [];

    public function __construct(int $delay = 0)
    {
        $this->delay = $delay;
    }

    /**
     * Set the callback for the step with optional arguments.
     *
     * @param string|array $callback The callback function or [class, method] array.
     * @param array $arguments Optional arguments to be passed to the callback.
     * @throws \InvalidArgumentException If the callback is not callable or the function does not exist.
     */
    public function setCallback($callback, array $arguments = []): void
    {
        // Check if the callback is callable
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback provided.');
        }

        // If the callback is an array [class, method], check if the method exists
        if (is_array($callback) && !method_exists($callback[0], $callback[1])) {
            throw new \InvalidArgumentException('Method does not exist in the provided class.');
        }

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
