<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;

if (!defined('ABS_PATH')) {
    return;
}
/**
 * Class StepConfig
 *
 * Represents a configuration for a step in a workflow, including the step class and an optional delay.
 *
 * @final
 */
final class StepConfig
{
    /**
     * @var int $delay The delay (in seconds) before executing the step.
     * @readonly
     */
    public readonly int $delay;

    private readonly string $stepClass;

    /**
     * StepConfig constructor.
     *
     * @param string $stepClass The fully qualified class name of the step extending AbstractStep.
     * @param int $delay (Optional) The delay (in seconds) before executing the step. Defaults to 0.
     *
     * @throws \UnexpectedValueException If $stepClass is not a class extending AbstractStep or if $delay is less than 0.
     */
    public function __construct(string $stepClass, int $delay = 0)
    {
        if (!class_exists($stepClass) || !is_subclass_of($stepClass, AbstractStep::class)) {
            throw new \UnexpectedValueException("All entries in the array must be classes extending AbstractStep. {$stepClass} is not.");
        }

        if ($delay < 0) {
            throw new \UnexpectedValueException('Delay must be an integer greater than or equal to 0.');
        }

        $this->stepClass = $stepClass;
        $this->delay = $delay;
    }

    /**
     * Creates and returns an instance of the step class with the provided AppConfig and delay.
     *
     * @param AppConfig $appConfig The application configuration.
     *
     * @return AbstractStep The instance of the step class.
     */
    public function getStepObject(AppConfig $appConfig): AbstractStep
    {
        return new $this->stepClass($appConfig, $this->delay);
    }
}
