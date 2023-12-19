<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Represents the result of executing a step in a process.
 */
/**
 * Represents the result of executing a step in a process.
 */
final class StepResult
{
    public $returnValue;
    public bool $repeat;

    /**
     * StepResult constructor.
     *
     * @param mixed $returnValue The value returned by the step.
     * @param bool $repeat Indicates whether the step should be repeated. Default is false.
     */
    public function __construct(mixed $returnValue, bool $repeat = false)
    {
        $this->returnValue = $returnValue;
        $this->repeat = $repeat;
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return "ReturnValue: " . $this->returnValue . ", Repeat: " . ($this->repeat ? 'true' : 'false');
    }
}
