<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Step\AbstractStep;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Class StepConfig.
 *
 * Represents a configuration for a step in a workflow, including the step class and an optional delay.
 */
final class StepConfig
{
    /**
     * @var int the delay (in seconds) before executing the step
     *
     * @readonly
     */
    public readonly int $delay;
    public readonly string $stepClass;
    private readonly string $remoteHandler;

    /**
     * StepConfig constructor.
     *
     * @param string $stepClass the fully qualified class name of the step extending AbstractStep
     * @param int $delay (Optional) The delay (in seconds) before executing the step. Defaults to 0.
     * @param string $remote (Optional) the fully qualified class name of a required remote handler
     *
     * @throws \UnexpectedValueException if $stepClass is not a class extending AbstractStep or if $delay is less than 0
     */
    public function __construct(string $stepClass, int $delay = 0, string $remote = '')
    {
        $this->validateClass($stepClass, AbstractStep::class);

        if (!empty($remote)) {
            $this->validateClass($remote, AbstractRemoteHandler::class);
        }

        if ($delay < 0) {
            throw new \UnexpectedValueException('Delay must be an integer greater than or equal to 0.');
        }

        $this->stepClass = $stepClass;
        $this->remoteHandler = $remote;
        $this->delay = $delay;
    }

    /**
     * Creates and returns an instance of the step class.
     *
     * @return AbstractStep the instance of the step class
     */
    public function getStepObject(): AbstractStep
    {
        return StepFactory::build($this->stepClass, $this->remoteHandler);
    }

    private function validateClass(string $class, string $parentClass)
    {
        if (!class_exists($class) || !is_subclass_of($class, $parentClass)) {
            throw new \UnexpectedValueException("The class '{$class}' is not a child of the class '{$parentClass}'.");
        }
    }
}
