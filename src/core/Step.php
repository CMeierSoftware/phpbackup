<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

final class Step
{
    public readonly int $delay;
    private $callback;
    private readonly array $arguments;

    /**
     * Set the callback for the step with optional arguments.
     *
     * @param callable $callback the callback function or [class, method] array
     * @param array $arguments optional arguments to be passed to the callback
     * @param int $delay delay between this and the previous step
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
     * @return StepResult the result of the callback execution
     *
     * @throws \RuntimeException if the callback is not set
     */
    public function execute(): StepResult
    {
        if (!$this->callback) {
            throw new \RuntimeException('Callback is not set.');
        }

        FileLogger::getInstance()->info("Execute {$this}");

        $result = call_user_func_array($this->callback, $this->arguments);

        if (!$result instanceof StepResult) {
            throw new \RuntimeException('the step result is not of type ' . StepResult::class);
        }

        return $result;
    }

    public function __toString(): string
    {
        if (is_array($this->callback)) {
            [$class, $method] = $this->callback;
            $cls = is_object($class) ? $class::class : $class;
            return "Callable: [$cls, $method]";
        } elseif (is_object($this->callback)) {
            return "Callable: " . get_class($this->callback);
        } elseif (is_string($this->callback)) {
            return "Callable: {$this->callback}";
        }
        throw new \UnexpectedValueException("Unsupported callable type");
    }
    
}
